<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * Build the dashboard summary for the given period.
     *
     * @return array{
     *     period: string,
     *     periodLabel: string,
     *     from: Carbon|null,
     *     to: Carbon|null,
     *     collected: float,
     *     paymentsCount: int,
     *     soldTotal: float,
     *     soldOrdersCount: int,
     *     chickensSold: int,
     *     poundsSold: float,
     *     averageTicket: float,
     *     averagePricePerLb: float,
     *     methods: Collection<int, array{method: PaymentMethod, label: string, total: float, count: int, percentage: float}>,
     *     last7Days: Collection<int, array{date: Carbon, label: string, total: float}>,
     *     chartMax: float,
     *     pendingBalance: float,
     *     deliveredUnpaid: int,
     *     pendingReservations: int,
     *     chickensReserved: int,
     *     recentPayments: Collection<int, Payment>,
     *     recentOrders: Collection<int, Order>,
     *     upcomingReservations: Collection<int, Order>
     * }
     */
    public function summary(string $period = 'mes'): array
    {
        $now = now();

        [$from, $to] = match ($period) {
            'hoy' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'semana' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'todo' => [null, null],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };

        $soldOrders = Order::query()
            ->whereIn('status', [OrderStatus::Entregado->value, OrderStatus::Cobrado->value])
            ->when($from && $to, fn ($query) => $query->whereBetween('delivered_at', [$from, $to]));

        $soldOrdersCount = (clone $soldOrders)->count();
        $soldTotal = (float) (clone $soldOrders)->sum('total');

        $soldItems = OrderItem::query()
            ->whereHas('order', function ($query) use ($from, $to): void {
                $query->whereIn('status', [OrderStatus::Entregado->value, OrderStatus::Cobrado->value])
                    ->when($from && $to, fn ($query) => $query->whereBetween('delivered_at', [$from, $to]));
            });

        $chickensSold = (clone $soldItems)->count();
        $poundsSold = (float) (clone $soldItems)->sum('weight_lb');

        $payments = Payment::query()
            ->when($from && $to, fn ($query) => $query->whereBetween('paid_at', [$from, $to]));

        $collected = (float) (clone $payments)->sum('amount');
        $paymentsCount = (clone $payments)->count();

        $methodTotals = (clone $payments)
            ->toBase()
            ->selectRaw('method, count(*) as payments_count, coalesce(sum(amount), 0) as total')
            ->groupBy('method')
            ->get()
            ->keyBy('method');

        $methods = collect(PaymentMethod::cases())
            ->map(function (PaymentMethod $method) use ($methodTotals, $collected): array {
                $row = $methodTotals->get($method->value);
                $total = (float) ($row->total ?? 0);

                return [
                    'method' => $method,
                    'label' => $method->label(),
                    'total' => $total,
                    'count' => (int) ($row->payments_count ?? 0),
                    'percentage' => $collected > 0 ? round($total / $collected * 100, 1) : 0.0,
                ];
            })
            ->values();

        $last7Days = $this->lastSevenDays($now);

        $pendingBalance = (float) Order::query()
            ->withOutstandingBalance()
            ->selectRaw('coalesce(sum(orders.total), 0) - coalesce(sum((select coalesce(sum(payments.amount), 0) from payments where payments.order_id = orders.id)), 0) as pending_balance')
            ->value('pending_balance');

        $upcomingReservations = Order::query()
            ->where('status', OrderStatus::Reservado->value)
            ->with('customer')
            ->withCount('items')
            ->orderByRaw('delivery_date is null')
            ->orderBy('delivery_date')
            ->orderBy('id')
            ->limit(5)
            ->get();

        return [
            'period' => $period,
            'periodLabel' => $this->periodLabel($period),
            'from' => $from,
            'to' => $to,
            'collected' => $collected,
            'paymentsCount' => $paymentsCount,
            'soldTotal' => $soldTotal,
            'soldOrdersCount' => $soldOrdersCount,
            'chickensSold' => $chickensSold,
            'poundsSold' => $poundsSold,
            'averageTicket' => $soldOrdersCount > 0 ? round($soldTotal / $soldOrdersCount, 2) : 0.0,
            'averagePricePerLb' => $poundsSold > 0 ? round($soldTotal / $poundsSold, 2) : 0.0,
            'methods' => $methods,
            'last7Days' => $last7Days,
            'chartMax' => max(1.0, (float) $last7Days->max('total')),
            'pendingBalance' => $pendingBalance,
            'deliveredUnpaid' => Order::query()->withOutstandingBalance()->count(),
            'pendingReservations' => Order::query()->where('status', OrderStatus::Reservado->value)->count(),
            'chickensReserved' => OrderItem::query()
                ->whereHas('order', fn ($query) => $query->where('status', OrderStatus::Reservado->value))
                ->count(),
            'recentPayments' => Payment::query()
                ->with(['order.customer'])
                ->latest('paid_at')
                ->latest('id')
                ->limit(6)
                ->get(),
            'recentOrders' => Order::query()
                ->with('customer')
                ->withCount('items')
                ->withSum('payments', 'amount')
                ->latest()
                ->limit(6)
                ->get(),
            'upcomingReservations' => $upcomingReservations,
        ];
    }

    /**
     * @return Collection<int, array{date: Carbon, label: string, total: float}>
     */
    private function lastSevenDays(Carbon $now): Collection
    {
        $start = $now->copy()->startOfDay()->subDays(6);
        $end = $now->copy()->endOfDay();

        $dailyTotals = Payment::query()
            ->toBase()
            ->whereBetween('paid_at', [$start, $end])
            ->selectRaw('date(paid_at) as day, coalesce(sum(amount), 0) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return collect(range(6, 0))
            ->map(function (int $daysAgo) use ($now, $dailyTotals): array {
                $date = $now->copy()->startOfDay()->subDays($daysAgo);
                $key = $date->toDateString();

                return [
                    'date' => $date,
                    'label' => $date->translatedFormat('D d'),
                    'total' => (float) ($dailyTotals[$key] ?? 0),
                ];
            })
            ->values();
    }

    private function periodLabel(string $period): string
    {
        return match ($period) {
            'hoy' => 'Hoy',
            'semana' => 'Esta semana',
            'todo' => 'Todo el historial',
            default => 'Este mes',
        };
    }
}
