<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_cards_show_the_real_business_totals(): void
    {
        $user = User::factory()->create();

        Order::factory()->withReservedChickens(2)->create(['delivery_date' => '2026-11-01']);
        Order::factory()->withReservedChickens(1)->create(['delivery_date' => '2026-11-02']);

        $delivered = Order::factory()->delivered()->withChickens(2, 5, 500)->create();
        Payment::factory()->create([
            'order_id' => $delivered->getKey(),
            'amount' => '300.00',
        ]);

        Order::factory()->delivered()->withChickens(1, 4, 500)->create();

        $collected = Order::factory()->collected()->withChickens(1, 6, 400)->create();
        Payment::factory()->create([
            'order_id' => $collected->getKey(),
            'amount' => '400.00',
        ]);

        $old = Order::factory()->collected()->withChickens(1, 5, 999)->create([
            'delivered_at' => now()->subMonth(),
            'paid_at' => now()->subMonth(),
        ]);
        Payment::factory()->create([
            'order_id' => $old->getKey(),
            'amount' => '999.00',
            'paid_at' => now()->subMonth(),
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertOk()
            ->assertViewHas('pendingReservations', 2)
            ->assertViewHas('chickensReserved', 3)
            ->assertViewHas('deliveredUnpaid', 2)
            ->assertViewHas('pendingBalance', 1200.0)
            ->assertViewHas('collectedThisMonth', 700.0)
            ->assertViewHas('poundsThisMonth', 20.0)
            ->assertSeeText('Reservas pendientes')
            ->assertSeeText('Pollos reservados')
            ->assertSeeText('Entregados sin cobrar')
            ->assertSeeText('1.200,00')
            ->assertSeeText('Cobrado este mes')
            ->assertSeeText('Libras vendidas este mes')
            ->assertSeeText('20,00');
    }

    public function test_dashboard_lists_the_next_five_reservations_by_delivery_date(): void
    {
        $user = User::factory()->create();

        for ($index = 1; $index <= 6; $index++) {
            Order::factory()
                ->withReservedChickens(1)
                ->for(Customer::factory()->create(['name' => "Cliente Reserva {$index}"]))
                ->create([
                    'delivery_date' => now()->addDays($index)->format('Y-m-d'),
                ]);
        }

        Order::factory()
            ->withReservedChickens(1)
            ->for(Customer::factory()->create(['name' => 'Cliente Sin Fecha']))
            ->create(['delivery_date' => null]);

        $response = $this->actingAs($user)->get('/');

        $response->assertOk()->assertSeeText('Próximas reservas');

        $upcoming = $response->viewData('upcomingReservations');

        $this->assertCount(5, $upcoming);
        $this->assertSame([
            'Cliente Reserva 1',
            'Cliente Reserva 2',
            'Cliente Reserva 3',
            'Cliente Reserva 4',
            'Cliente Reserva 5',
        ], $upcoming->pluck('customer.name')->all());
    }

    public function test_dashboard_lists_only_the_five_most_recent_orders(): void
    {
        $user = User::factory()->create();

        for ($index = 1; $index <= 6; $index++) {
            Order::factory()
                ->collected()
                ->for(Customer::factory()->create(['name' => "Cliente Reciente {$index}"]))
                ->create([
                    'created_at' => now()->subMinutes(10 - $index),
                ]);
        }

        $response = $this->actingAs($user)->get('/');

        $response->assertOk()->assertSeeText('Pedidos recientes');

        $recent = $response->viewData('recentOrders');

        $this->assertCount(5, $recent);
        $this->assertSame([
            'Cliente Reciente 6',
            'Cliente Reciente 5',
            'Cliente Reciente 4',
            'Cliente Reciente 3',
            'Cliente Reciente 2',
        ], $recent->pluck('customer.name')->all());
    }

    public function test_dashboard_offers_quick_links_to_sell_reserve_and_collect(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertOk()
            ->assertSee(route('pedidos.create'), false)
            ->assertSee(route('pedidos.sell'), false)
            ->assertSee(route('por-cobrar.index'), false);
    }
}
