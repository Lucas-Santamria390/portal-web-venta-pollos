<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerController extends Controller
{
    /**
     * Display the customer list.
     */
    public function index(Request $request): View
    {
        $search = trim($request->string('buscar')->toString());

        $customers = Customer::query()
            ->addSelect([
                'open_debt' => Order::query()
                    ->selectRaw('coalesce(sum(orders.total), 0) - coalesce(sum((select coalesce(sum(payments.amount), 0) from payments where payments.order_id = orders.id)), 0)')
                    ->whereColumn('orders.customer_id', 'customers.id')
                    ->where('orders.status', OrderStatus::Entregado->value),
                'last_purchase_at' => Order::query()
                    ->select('orders.created_at')
                    ->whereColumn('orders.customer_id', 'customers.id')
                    ->where('orders.status', '!=', OrderStatus::Anulado->value)
                    ->latest()
                    ->limit(1),
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('modules.clientes.index', [
            'customers' => $customers,
            'search' => $search,
        ]);
    }

    /**
     * Show the form for creating a customer.
     */
    public function create(): View
    {
        return view('modules.clientes.create');
    }

    /**
     * Store a newly created customer.
     */
    public function store(StoreCustomerRequest $request): JsonResponse|RedirectResponse
    {
        $customer = Customer::create($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $customer->getKey(),
                'name' => $customer->name,
                'phone' => $customer->phone,
            ], 201);
        }

        return redirect()
            ->route('clientes.show', $customer)
            ->with('status', "Cliente «{$customer->name}» creado.");
    }

    /**
     * Display the customer detail.
     */
    public function show(Customer $customer): View
    {
        $orders = $customer->orders()
            ->withSum('payments', 'amount')
            ->withCount('items')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $summary = $customer->orders()
            ->where('orders.status', OrderStatus::Entregado->value)
            ->whereRaw('orders.total > (select coalesce(sum(payments.amount), 0) from payments where payments.order_id = orders.id)')
            ->selectRaw('count(*) as open_orders_count, coalesce(sum(total), 0) - coalesce(sum((select coalesce(sum(amount), 0) from payments where payments.order_id = orders.id)), 0) as pending_balance')
            ->first();

        return view('modules.clientes.show', [
            'customer' => $customer,
            'orders' => $orders,
            'openOrdersCount' => (int) $summary->open_orders_count,
            'pendingBalance' => (float) $summary->pending_balance,
        ]);
    }

    /**
     * Show the form for editing a customer.
     */
    public function edit(Customer $customer): View
    {
        return view('modules.clientes.edit', [
            'customer' => $customer,
        ]);
    }

    /**
     * Update the specified customer.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return redirect()
            ->route('clientes.show', $customer)
            ->with('status', 'Cliente actualizado.');
    }

    /**
     * Delete a customer and all related orders, items and payments.
     */
    public function destroy(Customer $customer): RedirectResponse
    {
        $name = $customer->name;

        DB::transaction(function () use ($customer): void {
            $customer->delete();
        });

        return redirect()
            ->route('clientes.index')
            ->with('status', "Cliente «{$name}» y su historial fueron eliminados.");
    }
}
