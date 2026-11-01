<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_panel_defaults_to_the_current_month_and_summarizes_sales_and_payments(): void
    {
        $user = User::factory()->create();

        $delivered = Order::factory()->delivered()->withChickens(2, 5, 500)->create();
        Payment::factory()->create([
            'order_id' => $delivered->getKey(),
            'amount' => '300.00',
            'method' => PaymentMethod::Efectivo,
            'paid_at' => now(),
        ]);

        $collected = Order::factory()->collected()->withChickens(1, 4, 400)->create();
        Payment::factory()->create([
            'order_id' => $collected->getKey(),
            'amount' => '400.00',
            'method' => PaymentMethod::Transferencia,
            'paid_at' => now(),
        ]);

        $old = Order::factory()->collected()->withChickens(1, 5, 999)->create([
            'delivered_at' => now()->startOfMonth()->subDay(),
        ]);
        Payment::factory()->create([
            'order_id' => $old->getKey(),
            'amount' => '999.00',
            'method' => PaymentMethod::Efectivo,
            'paid_at' => now()->startOfMonth()->subDay(),
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk()->assertSeeText('Dashboard');

        $summary = $response->viewData('summary');

        $this->assertSame('mes', $summary['period']);
        $this->assertSame(700.0, $summary['collected']);
        $this->assertSame(2, $summary['paymentsCount']);
        $this->assertSame(1400.0, $summary['soldTotal']);
        $this->assertSame(2, $summary['soldOrdersCount']);
        $this->assertSame(3, $summary['chickensSold']);
        $this->assertSame(14.0, $summary['poundsSold']);
        $this->assertSame(700.0, $summary['averageTicket']);
        $this->assertSame(100.0, $summary['averagePricePerLb']);
        $this->assertCount(7, $summary['last7Days']);
        $this->assertSame(700.0, (float) $summary['last7Days']->sum('total'));

        $methods = $summary['methods']->keyBy(fn (array $method): string => $method['method']->value);

        $this->assertSame(300.0, $methods['efectivo']['total']);
        $this->assertSame(1, $methods['efectivo']['count']);
        $this->assertSame(400.0, $methods['transferencia']['total']);
        $this->assertSame(1, $methods['transferencia']['count']);
        $this->assertSame(0.0, $methods['tarjeta']['total']);
    }

    public function test_the_panel_period_filter_limits_payments_and_sales(): void
    {
        $user = User::factory()->create();

        $today = Order::factory()->delivered()->withChickens(1, 5, 200)->create();
        Payment::factory()->create([
            'order_id' => $today->getKey(),
            'amount' => '200.00',
            'paid_at' => now(),
        ]);

        $old = Order::factory()->collected()->withChickens(1, 5, 999)->create([
            'delivered_at' => now()->startOfMonth()->subDay(),
        ]);
        Payment::factory()->create([
            'order_id' => $old->getKey(),
            'amount' => '999.00',
            'paid_at' => now()->startOfMonth()->subDay(),
        ]);

        $todaySummary = $this->actingAs($user)->get('/dashboard?periodo=hoy')->viewData('summary');

        $this->assertSame('hoy', $todaySummary['period']);
        $this->assertSame(200.0, $todaySummary['collected']);
        $this->assertSame(200.0, $todaySummary['soldTotal']);
        $this->assertSame(1, $todaySummary['chickensSold']);

        $allSummary = $this->actingAs($user)->get('/dashboard?periodo=todo')->viewData('summary');

        $this->assertSame('todo', $allSummary['period']);
        $this->assertSame(1199.0, $allSummary['collected']);
        $this->assertSame(1199.0, $allSummary['soldTotal']);
        $this->assertSame(2, $allSummary['chickensSold']);
    }

    public function test_the_pending_balance_is_the_current_state_and_does_not_depend_on_the_period(): void
    {
        $user = User::factory()->create();

        Order::factory()->delivered()->withChickens(1, 5, 500)->create([
            'delivered_at' => now()->startOfMonth()->subDay(),
        ]);

        $this->actingAs($user)->get('/dashboard?periodo=hoy')
            ->assertOk()
            ->assertViewHas('summary', fn (array $summary): bool => $summary['pendingBalance'] === 500.0 && $summary['deliveredUnpaid'] === 1);

        $this->actingAs($user)->get('/dashboard?periodo=todo')
            ->assertOk()
            ->assertViewHas('summary', fn (array $summary): bool => $summary['pendingBalance'] === 500.0 && $summary['deliveredUnpaid'] === 1);
    }

    public function test_the_panel_rejects_an_unknown_period(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard?periodo=ayer')
            ->assertSessionHasErrors('periodo');
    }
}
