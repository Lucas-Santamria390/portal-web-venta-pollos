<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReceivableController extends Controller
{
    /**
     * Display the delivered orders with a pending balance, oldest first.
     */
    public function index(Request $request): View
    {
        $search = trim($request->string('buscar')->toString());

        $orders = Order::query()
            ->withOutstandingBalance()
            ->with(['customer', 'user'])
            ->withCount('items')
            ->withSum('payments', 'amount')
            ->when($search !== '', function ($query) use ($search): void {
                $query->whereHas('customer', function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%");
                });
            })
            ->orderBy('delivered_at')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        $summary = Order::query()
            ->withOutstandingBalance()
            ->selectRaw('count(*) as orders_count, coalesce(sum(orders.total), 0) - coalesce(sum((select coalesce(sum(amount), 0) from payments where payments.order_id = orders.id)), 0) as pending_balance')
            ->first();

        return view('modules.por-cobrar.index', [
            'orders' => $orders,
            'search' => $search,
            'ordersCount' => (int) $summary->orders_count,
            'pendingBalance' => (float) $summary->pending_balance,
        ]);
    }
}
