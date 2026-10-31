<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'price_per_lb' => fake()->randomFloat(2, 1000, 5000),
            'auto_price' => true,
        ];
    }

    public function manualPrice(): static
    {
        return $this->state(fn (array $attributes): array => [
            'auto_price' => false,
        ]);
    }
}
