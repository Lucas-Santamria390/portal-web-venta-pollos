<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the application dashboard with the chicken business indicators.
     */
    public function __invoke(): View
    {
        $pendingReservations = Order::query()
            ->where('status', OrderStatus::Reservado->value)
            ->count();

        $chickensReserved = OrderItem::query()
            ->whereHas('order', fn ($query) => $query->where('status', OrderStatus::Reservado->value))
            ->count();

        $deliveredUnpaid = Order::query()
            ->withOutstandingBalance()
            ->count();

        $pendingBalance = (float) Order::query()
            ->withOutstandingBalance()
            ->selectRaw('coalesce(sum(orders.total), 0) - coalesce(sum((select coalesce(sum(payments.amount), 0) from payments where payments.order_id = orders.id)), 0) as pending_balance')
            ->value('pending_balance');

        $collectedThisMonth = (float) Payment::query()
            ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');

        $poundsThisMonth = (float) OrderItem::query()
            ->whereHas('order', function ($query): void {
                $query->whereIn('status', [OrderStatus::Entregado->value, OrderStatus::Cobrado->value])
                    ->whereBetween('delivered_at', [now()->startOfMonth(), now()->endOfMonth()]);
            })
            ->sum('weight_lb');

        $upcomingReservations = Order::query()
            ->where('status', OrderStatus::Reservado->value)
            ->with('customer')
            ->withCount('items')
            ->orderByRaw('delivery_date is null')
            ->orderBy('delivery_date')
            ->orderBy('id')
            ->limit(5)
            ->get();

        $recentOrders = Order::query()
            ->with('customer')
            ->withCount('items')
            ->withSum('payments', 'amount')
            ->latest()
            ->limit(5)
            ->get();

        return view('dashboard', [
            'pendingReservations' => $pendingReservations,
            'chickensReserved' => $chickensReserved,
            'deliveredUnpaid' => $deliveredUnpaid,
            'pendingBalance' => $pendingBalance,
            'collectedThisMonth' => $collectedThisMonth,
            'poundsThisMonth' => $poundsThisMonth,
            'upcomingReservations' => $upcomingReservations,
            'recentOrders' => $recentOrders,
        ]);
    }

    /**
     * Show the sales and payments dashboard for a given period.
     */
    public function panel(Request $request, DashboardService $dashboardService): View
    {
        $filters = $request->validate([
            'periodo' => ['nullable', Rule::in(['hoy', 'semana', 'mes', 'todo'])],
        ]);

        return view('modules.dashboard.index', [
            'summary' => $dashboardService->summary($filters['periodo'] ?? 'mes'),
        ]);
    }
}
