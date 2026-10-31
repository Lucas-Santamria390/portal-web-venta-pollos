<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * Register a reservation. Weights are optional until delivery and the total stays at zero.
     *
     * @param  array{customer_id: int|string, quantity: int|string, delivery_date?: string|null, note?: string|null, weights?: array<int, int|float|string|null>}  $data
     */
    public function reserve(array $data, User $user): Order
    {
        return DB::transaction(function () use ($data, $user): Order {
            $setting = Setting::current();
            $quantity = (int) $data['quantity'];

            $order = Order::create([
                'customer_id' => $data['customer_id'],
                'user_id' => $user->getKey(),
                'status' => OrderStatus::Reservado,
                'delivery_date' => $data['delivery_date'] ?? null,
                'reserved_at' => now(),
                'price_per_lb' => $setting->price_per_lb,
                'total' => 0,
                'note' => $data['note'] ?? null,
            ]);

            $this->replaceItems($order, $this->buildReservationItems($quantity, $data['weights'] ?? []));

            return $order;
        });
    }

    /**
     * Register a direct sale, deliver it and register the initial payment in one step.
     *
     * @param  array{customer_id?: int|string|null, quantity: int|string, note?: string|null, weights: array<int, int|float|string|null>, price_per_lb?: int|float|string|null, prices?: array<int, int|float|string|null>, paid_amount: int|float|string, method: string}  $data
     */
    public function sellNow(array $data, User $user): Order
    {
        return DB::transaction(function () use ($data, $user): Order {
            $setting = Setting::current();
            $autoPrice = (bool) $setting->auto_price;
            $quantity = (int) $data['quantity'];
            $pricePerLb = round((float) ($data['price_per_lb'] ?? $setting->price_per_lb), 2);

            $items = $this->buildPricedItems(
                $quantity,
                $data['weights'],
                $data['prices'] ?? [],
                $pricePerLb,
                $autoPrice,
            );

            $total = $this->itemsTotal($items);
            $paid = round((float) $data['paid_amount'], 2);
            $customerId = filled($data['customer_id'] ?? null) ? (int) $data['customer_id'] : null;

            if ($paid > $total) {
                throw ValidationException::withMessages([
                    'paid_amount' => 'El pago no puede superar el total de $'.number_format($total, 2, ',', '.').'.',
                ]);
            }

            if ($paid < $total && $customerId === null) {
                throw ValidationException::withMessages([
                    'customer_id' => 'Selecciona un cliente para dejar saldo pendiente (fiado).',
                ]);
            }

            $collected = $paid >= $total;

            $order = Order::create([
                'customer_id' => $customerId,
                'user_id' => $user->getKey(),
                'status' => $collected ? OrderStatus::Cobrado : OrderStatus::Entregado,
                'delivery_date' => null,
                'reserved_at' => now(),
                'delivered_at' => now(),
                'paid_at' => $collected ? now() : null,
                'price_per_lb' => $pricePerLb,
                'total' => $total,
                'note' => $data['note'] ?? null,
            ]);

            $order->items()->createMany($items);

            if ($paid > 0) {
                Payment::create([
                    'order_id' => $order->getKey(),
                    'user_id' => $user->getKey(),
                    'amount' => $paid,
                    'method' => $data['method'],
                    'paid_at' => now(),
                    'note' => null,
                ]);
            }

            return $order->refresh();
        });
    }

    /**
     * Update a reservation, or a delivered order when it has no payments and the user is an admin.
     *
     * @param  array{customer_id: int|string, quantity: int|string, delivery_date?: string|null, note?: string|null, weights?: array<int, int|float|string|null>, price_per_lb?: int|float|string|null, prices?: array<int, int|float|string|null>}  $data
     */
    public function update(Order $order, array $data, User $user): Order
    {
        return DB::transaction(function () use ($order, $data, $user): Order {
            $lockedOrder = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedOrder->canBeEditedBy($user)) {
                throw ValidationException::withMessages([
                    'order' => 'Este pedido ya no se puede editar.',
                ]);
            }

            $setting = Setting::current();
            $quantity = (int) $data['quantity'];

            if ($lockedOrder->isDelivered()) {
                $autoPrice = (bool) $setting->auto_price;
                $pricePerLb = round((float) ($data['price_per_lb'] ?? $lockedOrder->price_per_lb ?? $setting->price_per_lb), 2);
                $items = $this->buildPricedItems(
                    $quantity,
                    $data['weights'] ?? [],
                    $data['prices'] ?? [],
                    $pricePerLb,
                    $autoPrice,
                );
                $total = $this->itemsTotal($items);
            } else {
                $autoPrice = (bool) $setting->auto_price;
                $pricePerLb = round((float) ($lockedOrder->price_per_lb ?? $setting->price_per_lb), 2);
                $items = $this->buildReservationItems($quantity, $data['weights'] ?? []);
                $total = 0;
            }

            $this->replaceItems($lockedOrder, $items);

            $lockedOrder->update([
                'customer_id' => $data['customer_id'],
                'delivery_date' => $data['delivery_date'] ?? null,
                'note' => $data['note'] ?? null,
                'price_per_lb' => $pricePerLb,
                'total' => $total,
            ]);

            return $lockedOrder->refresh();
        });
    }

    /**
     * Confirm the delivery of a reservation using the weight and price of every chicken.
     *
     * @param  array{weights: array<int, int|float|string|null>, price_per_lb?: int|float|string|null, prices?: array<int, int|float|string|null>}  $data
     */
    public function deliver(Order $order, array $data, User $user): Order
    {
        return DB::transaction(function () use ($order, $data): Order {
            $lockedOrder = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedOrder->isReserved()) {
                throw ValidationException::withMessages([
                    'order' => 'Solo se pueden entregar pedidos reservados.',
                ]);
            }

            $setting = Setting::current();
            $autoPrice = (bool) $setting->auto_price;
            $quantity = $lockedOrder->items()->count();
            $pricePerLb = round((float) ($data['price_per_lb'] ?? $lockedOrder->price_per_lb ?? $setting->price_per_lb), 2);

            $items = $this->buildPricedItems(
                $quantity,
                $data['weights'],
                $data['prices'] ?? [],
                $pricePerLb,
                $autoPrice,
            );

            $this->replaceItems($lockedOrder, $items);

            $lockedOrder->update([
                'status' => OrderStatus::Entregado,
                'delivered_at' => now(),
                'price_per_lb' => $pricePerLb,
                'total' => $this->itemsTotal($items),
            ]);

            return $lockedOrder->refresh();
        });
    }

    /**
     * Register a payment for a delivered order and close it when the balance reaches zero.
     *
     * @param  array{amount: int|float|string, method: string, paid_at?: string|null, note?: string|null}  $data
     */
    public function registerPayment(Order $order, array $data, User $user): Payment
    {
        return DB::transaction(function () use ($order, $data, $user): Payment {
            $lockedOrder = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->isCollected()) {
                throw ValidationException::withMessages([
                    'amount' => 'Este pedido ya está cobrado.',
                ]);
            }

            if (! $lockedOrder->isDelivered()) {
                throw ValidationException::withMessages([
                    'amount' => 'Solo se pueden cobrar pedidos entregados.',
                ]);
            }

            $paid = round((float) $lockedOrder->payments()->sum('amount'), 2);
            $balance = round((float) $lockedOrder->total - $paid, 2);
            $amount = round((float) $data['amount'], 2);

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'El monto debe ser mayor a cero.',
                ]);
            }

            if ($amount > $balance) {
                throw ValidationException::withMessages([
                    'amount' => 'El monto supera el saldo de $'.number_format($balance, 2, ',', '.').'.',
                ]);
            }

            $payment = Payment::create([
                'order_id' => $lockedOrder->getKey(),
                'user_id' => $user->getKey(),
                'amount' => $amount,
                'method' => $data['method'],
                'paid_at' => $data['paid_at'] ?? now(),
                'note' => $data['note'] ?? null,
            ]);

            if (round($balance - $amount, 2) <= 0) {
                $lockedOrder->update([
                    'status' => OrderStatus::Cobrado,
                    'paid_at' => now(),
                ]);
            }

            return $payment;
        });
    }

    /**
     * Cancel a reservation or a delivered order without payments.
     */
    public function cancel(Order $order, User $user): Order
    {
        return DB::transaction(function () use ($order): Order {
            $lockedOrder = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->isCancelled()) {
                throw ValidationException::withMessages([
                    'order' => 'Este pedido ya está anulado.',
                ]);
            }

            if (! $lockedOrder->canBeCancelled()) {
                throw ValidationException::withMessages([
                    'order' => 'No se puede anular un pedido cobrado o con pagos registrados.',
                ]);
            }

            $lockedOrder->update([
                'status' => OrderStatus::Anulado,
                'cancelled_at' => now(),
            ]);

            return $lockedOrder->refresh();
        });
    }

    /**
     * Delete a payment and recalculate the order state.
     */
    public function deletePayment(Order $order, Payment $payment): Order
    {
        return DB::transaction(function () use ($order, $payment): Order {
            $lockedOrder = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedPayment = $lockedOrder->payments()
                ->whereKey($payment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedPayment->delete();

            $paid = round((float) $lockedOrder->payments()->sum('amount'), 2);
            $balance = round((float) $lockedOrder->total - $paid, 2);

            if ($lockedOrder->isCollected() && $balance > 0) {
                $lockedOrder->update([
                    'status' => OrderStatus::Entregado,
                    'paid_at' => null,
                ]);
            } elseif (! $lockedOrder->isCollected() && $balance <= 0) {
                $lockedOrder->update([
                    'status' => OrderStatus::Cobrado,
                    'paid_at' => now(),
                ]);
            }

            return $lockedOrder->refresh();
        });
    }

    /**
     * Delete an order and its related items and payments.
     */
    public function delete(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $lockedOrder = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedOrder->delete();
        });
    }

    /**
     * Build the chicken rows for a reservation, where only the optional weights are known.
     *
     * @param  array<int, int|float|string|null>  $weights
     * @return array<int, array{position: int, weight_lb: float|null, price: null}>
     */
    private function buildReservationItems(int $quantity, array $weights): array
    {
        $items = [];

        for ($index = 0; $index < $quantity; $index++) {
            $weight = $this->resolveWeight($weights[$index] ?? null, $index + 1);

            $items[] = [
                'position' => $index + 1,
                'weight_lb' => $weight,
                'price' => null,
            ];
        }

        return $items;
    }

    /**
     * Build the priced chicken rows for a delivery or a direct sale.
     *
     * @param  array<int, int|float|string|null>  $weights
     * @param  array<int, int|float|string|null>  $prices
     * @return array<int, array{position: int, weight_lb: float, price: float}>
     */
    private function buildPricedItems(int $quantity, array $weights, array $prices, float $pricePerLb, bool $autoPrice): array
    {
        $items = [];

        for ($index = 0; $index < $quantity; $index++) {
            $weight = $this->resolveWeight($weights[$index] ?? null, $index + 1);

            if ($weight === null) {
                throw ValidationException::withMessages([
                    'weights' => 'Registra el peso del pollo '.($index + 1).'.',
                ]);
            }

            if ($autoPrice) {
                if ($pricePerLb <= 0) {
                    throw ValidationException::withMessages([
                        'price_per_lb' => 'Configura un precio por libra mayor a cero antes de vender.',
                    ]);
                }

                $price = round($weight * $pricePerLb, 2);
            } else {
                $price = filled($prices[$index] ?? null) ? round((float) $prices[$index], 2) : null;

                if ($price === null || $price <= 0) {
                    throw ValidationException::withMessages([
                        'prices' => 'Indica el precio del pollo '.($index + 1).'.',
                    ]);
                }
            }

            $items[] = [
                'position' => $index + 1,
                'weight_lb' => $weight,
                'price' => $price,
            ];
        }

        return $items;
    }

    /**
     * Validate and normalize an optional weight in pounds.
     */
    private function resolveWeight(int|float|string|null $weight, int $position): ?float
    {
        if (! filled($weight)) {
            return null;
        }

        $value = round((float) $weight, 2);

        if ($value <= 0) {
            throw ValidationException::withMessages([
                'weights' => 'El peso del pollo '.$position.' debe ser mayor a cero.',
            ]);
        }

        return $value;
    }

    /**
     * Replace all chicken rows of an order.
     *
     * @param  array<int, array{position: int, weight_lb: float|null, price: float|null}>  $items
     */
    private function replaceItems(Order $order, array $items): void
    {
        $order->items()->delete();
        $order->items()->createMany($items);
    }

    /**
     * Sum the prices of the given chicken rows.
     *
     * @param  array<int, array{position: int, weight_lb: float, price: float}>  $items
     */
    private function itemsTotal(array $items): float
    {
        return round((float) collect($items)->sum('price'), 2);
    }
}
