<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'user_id' => User::factory(),
            'status' => OrderStatus::Reservado,
            'delivery_date' => null,
            'reserved_at' => now(),
            'delivered_at' => null,
            'paid_at' => null,
            'cancelled_at' => null,
            'price_per_lb' => 0,
            'total' => 0,
            'note' => null,
        ];
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OrderStatus::Entregado,
            'delivered_at' => now(),
        ]);
    }

    public function collected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OrderStatus::Cobrado,
            'delivered_at' => now(),
            'paid_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OrderStatus::Anulado,
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Add priced chicken rows and set the total when it is still zero.
     */
    public function withChickens(int $quantity = 1, float $weightLb = 5.0, float $price = 100.0): static
    {
        return $this->afterCreating(function (Order $order) use ($quantity, $weightLb, $price): void {
            for ($position = 1; $position <= $quantity; $position++) {
                $order->items()->create([
                    'position' => $position,
                    'weight_lb' => $weightLb,
                    'price' => $price,
                ]);
            }

            if ((float) $order->total === 0.0) {
                $order->update(['total' => round($quantity * $price, 2)]);
            }
        });
    }

    /**
     * Add chicken rows without weight or price, as a reservation does.
     */
    public function withReservedChickens(int $quantity = 1): static
    {
        return $this->afterCreating(function (Order $order) use ($quantity): void {
            for ($position = 1; $position <= $quantity; $position++) {
                $order->items()->create([
                    'position' => $position,
                    'weight_lb' => null,
                    'price' => null,
                ]);
            }
        });
    }
}
