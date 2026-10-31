<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeliverOrderRequest;
use App\Http\Requests\RegisterPaymentRequest;
use App\Http\Requests\SellNowRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    /**
     * Display the orders list.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'estado' => ['nullable', Rule::in(['todas', 'reservado', 'entregado', 'cobrado', 'anulado'])],
            'buscar' => ['nullable', 'string', 'max:150'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
        ]);

        $status = $filters['estado'] ?? 'todas';
        $search = trim($filters['buscar'] ?? '');
        $from = $filters['desde'] ?? null;
        $to = $filters['hasta'] ?? null;

        $orders = Order::query()
            ->with(['customer', 'user'])
            ->withCount('items')
            ->withSum('payments', 'amount')
            ->when($status !== 'todas', fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->whereHas('customer', function ($query) use ($search): void {
                        $query->where('name', 'like', "%{$search}%");
                    });

                    if (ctype_digit($search)) {
                        $query->orWhere('orders.id', (int) $search);
                    }
                });
            })
            ->when($from, fn ($query) => $query->whereDate('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('created_at', '<=', $to))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('modules.pedidos.index', [
            'orders' => $orders,
            'status' => $status,
            'search' => $search,
            'from' => $from,
            'to' => $to,
        ]);
    }

    /**
     * Show the form for creating a reservation.
     */
    public function create(Request $request): View
    {
        return view('modules.pedidos.create', [
            'customers' => $this->customers(),
            'setting' => Setting::current(),
            'selectedCustomerId' => $request->integer('cliente') ?: null,
        ]);
    }

    /**
     * Store a newly created reservation.
     */
    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $order = $this->orderService->reserve($request->validated(), $request->user());

        return redirect()
            ->route('pedidos.show', $order)
            ->with('status', "Reserva #{$order->getKey()} registrada.");
    }

    /**
     * Show the form for a direct sale.
     */
    public function sell(Request $request): View
    {
        return view('modules.pedidos.sell', [
            'customers' => $this->customers(),
            'setting' => Setting::current(),
            'selectedCustomerId' => $request->integer('cliente') ?: null,
        ]);
    }

    /**
     * Register a direct sale, deliver it and charge the initial payment.
     */
    public function storeSell(SellNowRequest $request): RedirectResponse
    {
        $order = $this->orderService->sellNow($request->validated(), $request->user());

        $message = $order->isCollected()
            ? "Venta #{$order->getKey()} registrada y cobrada."
            : "Venta #{$order->getKey()} registrada con saldo pendiente de $".number_format($order->balance(), 2, ',', '.').'.';

        return redirect()
            ->route('pedidos.show', $order)
            ->with('status', $message);
    }

    /**
     * Display the order detail.
     */
    public function show(Order $order): View
    {
        $order->load([
            'customer',
            'user',
            'items',
            'payments' => fn ($query) => $query->with('user')->latest('paid_at')->latest('id'),
        ]);

        return view('modules.pedidos.show', [
            'order' => $order,
        ]);
    }

    /**
     * Show the form for editing an order.
     */
    public function edit(Order $order, Request $request): View
    {
        abort_unless($order->canBeEditedBy($request->user()), 403, 'Este pedido ya no se puede editar.');

        $order->load('items');

        return view('modules.pedidos.edit', [
            'order' => $order,
            'customers' => $this->customers(),
            'setting' => Setting::current(),
        ]);
    }

    /**
     * Update an order.
     */
    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        $this->orderService->update($order, $request->validated(), $request->user());

        return redirect()
            ->route('pedidos.show', $order)
            ->with('status', 'Pedido actualizado.');
    }

    /**
     * Show the delivery form.
     */
    public function deliverForm(Order $order): View
    {
        abort_unless($order->isReserved(), 403, 'Este pedido ya fue entregado.');

        $order->load('items');

        return view('modules.pedidos.deliver', [
            'order' => $order,
            'setting' => Setting::current(),
        ]);
    }

    /**
     * Confirm the delivery of a reservation.
     */
    public function deliver(DeliverOrderRequest $request, Order $order): RedirectResponse
    {
        $this->orderService->deliver($order, $request->validated(), $request->user());

        return redirect()
            ->route('pedidos.show', $order)
            ->with('status', 'Entrega confirmada. El pedido quedó con saldo pendiente.');
    }

    /**
     * Register a payment for a delivered order.
     */
    public function registerPayment(RegisterPaymentRequest $request, Order $order): RedirectResponse
    {
        try {
            $payment = $this->orderService->registerPayment($order, $request->validated(), $request->user());
        } catch (ValidationException $exception) {
            return redirect()
                ->route('pedidos.show', $order)
                ->withErrors($exception->errors())
                ->withInput();
        }

        $order->refresh();

        return redirect()
            ->route('pedidos.show', $order)
            ->with('status', $order->isCollected()
                ? 'Pago de $'.number_format((float) $payment->amount, 2, ',', '.').' registrado. El pedido quedó cobrado.'
                : 'Pago de $'.number_format((float) $payment->amount, 2, ',', '.').' registrado.');
    }

    /**
     * Cancel a reservation or a delivered order without payments.
     */
    public function cancel(Order $order, Request $request): RedirectResponse
    {
        $this->orderService->cancel($order, $request->user());

        return redirect()
            ->route('pedidos.show', $order)
            ->with('status', 'Pedido anulado.');
    }

    /**
     * Delete a payment and recalculate the order.
     */
    public function deletePayment(Order $order, Payment $payment): RedirectResponse
    {
        $this->orderService->deletePayment($order, $payment);

        return redirect()
            ->route('pedidos.show', $order)
            ->with('status', 'Cobro eliminado y saldo recalculado.');
    }

    /**
     * Delete an order and its related records.
     */
    public function destroy(Order $order): RedirectResponse
    {
        $this->orderService->delete($order);

        return redirect()
            ->route('pedidos.index')
            ->with('status', "Pedido #{$order->getKey()} eliminado.");
    }

    /**
     * Get the customer options used by the order forms.
     *
     * @return Collection<int, array{id: int, name: string, phone: string|null}>
     */
    private function customers(): Collection
    {
        return Customer::query()
            ->orderBy('name')
            ->get(['id', 'name', 'phone'])
            ->map(fn (Customer $customer): array => [
                'id' => $customer->getKey(),
                'name' => $customer->name,
                'phone' => $customer->phone,
            ])
            ->values();
    }
}
