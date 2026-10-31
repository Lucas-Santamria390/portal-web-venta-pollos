<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_partial_payment_keeps_the_order_delivered_and_updates_the_balance(): void
    {
        $order = Order::factory()->delivered()->withChickens(1, 5, 1000)->create([
            'price_per_lb' => '1000.00',
        ]);
        $seller = User::factory()->vendedor()->create();

        $this->actingAs($seller)
            ->post(route('pedidos.payments.store', $order), [
                'amount' => '400.00',
                'method' => PaymentMethod::Efectivo->value,
                'paid_at' => now()->format('Y-m-d'),
                'note' => 'Entrega parcial',
            ])
            ->assertRedirect(route('pedidos.show', $order));

        $order->refresh();

        $this->assertSame(OrderStatus::Entregado, $order->status);
        $this->assertNull($order->paid_at);
        $this->assertSame(400.0, $order->paidAmount());
        $this->assertSame(600.0, $order->balance());

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->getKey(),
            'user_id' => $seller->getKey(),
            'amount' => '400.00',
            'method' => PaymentMethod::Efectivo->value,
            'note' => 'Entrega parcial',
        ]);
    }

    public function test_a_full_payment_closes_the_order_as_collected(): void
    {
        $order = Order::factory()->delivered()->withChickens(1, 5, 1000)->create([
            'price_per_lb' => '1000.00',
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('pedidos.payments.store', $order), [
                'amount' => '1000.00',
                'method' => PaymentMethod::Transferencia->value,
            ])
            ->assertRedirect(route('pedidos.show', $order));

        $order->refresh();

        $this->assertSame(OrderStatus::Cobrado, $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertSame(0.0, $order->balance());
    }

    public function test_several_installments_close_the_order_when_the_balance_reaches_zero(): void
    {
        $order = Order::factory()->delivered()->withChickens(1, 5, 1000)->create([
            'price_per_lb' => '1000.00',
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('pedidos.payments.store', $order), [
            'amount' => '300.00',
            'method' => PaymentMethod::Efectivo->value,
        ]);

        $this->assertSame(OrderStatus::Entregado, $order->refresh()->status);

        $this->actingAs($user)->post(route('pedidos.payments.store', $order), [
            'amount' => '700.00',
            'method' => PaymentMethod::Efectivo->value,
        ]);

        $order->refresh();

        $this->assertSame(OrderStatus::Cobrado, $order->status);
        $this->assertSame(0.0, $order->balance());
        $this->assertSame(2, $order->payments()->count());
    }

    public function test_a_payment_above_the_balance_is_rejected(): void
    {
        $order = Order::factory()->delivered()->withChickens(1, 5, 1000)->create([
            'price_per_lb' => '1000.00',
        ]);

        $this->actingAs(User::factory()->create())
            ->from(route('pedidos.show', $order))
            ->post(route('pedidos.payments.store', $order), [
                'amount' => '1200.00',
                'method' => PaymentMethod::Efectivo->value,
            ])
            ->assertSessionHasErrors('amount');

        $this->assertSame(OrderStatus::Entregado, $order->refresh()->status);
        $this->assertSame(0, $order->payments()->count());
    }

    public function test_a_reserved_order_cannot_receive_payments(): void
    {
        $order = Order::factory()->withReservedChickens(1)->create();

        $this->actingAs(User::factory()->create())
            ->from(route('pedidos.show', $order))
            ->post(route('pedidos.payments.store', $order), [
                'amount' => '100.00',
                'method' => PaymentMethod::Efectivo->value,
            ])
            ->assertSessionHasErrors('amount');

        $this->assertSame(OrderStatus::Reservado, $order->refresh()->status);
        $this->assertSame(0, $order->payments()->count());
    }

    public function test_a_collected_order_cannot_receive_more_payments(): void
    {
        $order = Order::factory()->collected()->withChickens(1, 5, 1000)->create([
            'price_per_lb' => '1000.00',
        ]);

        $this->actingAs(User::factory()->create())
            ->from(route('pedidos.show', $order))
            ->post(route('pedidos.payments.store', $order), [
                'amount' => '100.00',
                'method' => PaymentMethod::Efectivo->value,
            ])
            ->assertSessionHasErrors('amount');

        $this->assertSame(OrderStatus::Cobrado, $order->refresh()->status);
    }

    public function test_the_receivables_list_shows_only_delivered_orders_with_a_balance(): void
    {
        $user = User::factory()->create();

        $pending = Order::factory()->delivered()->withChickens(1, 5, 1000)->create([
            'customer_id' => Customer::factory()->create(['name' => 'Cliente Fiado'])->getKey(),
        ]);
        Payment::factory()->create([
            'order_id' => $pending->getKey(),
            'amount' => '300.00',
        ]);

        Order::factory()->collected()->withChickens(1, 5, 1000)->create([
            'customer_id' => Customer::factory()->create(['name' => 'Cliente Al Dia'])->getKey(),
        ]);

        Order::factory()->withReservedChickens(1)->create([
            'customer_id' => Customer::factory()->create(['name' => 'Cliente Reserva'])->getKey(),
        ]);

        $this->actingAs($user)
            ->get('/por-cobrar')
            ->assertOk()
            ->assertSeeText('Cliente Fiado')
            ->assertSeeText('700,00')
            ->assertDontSeeText('Cliente Al Dia')
            ->assertDontSeeText('Cliente Reserva');
    }

    public function test_the_customer_detail_shows_the_pending_debt_and_orders(): void
    {
        $customer = Customer::factory()->create(['name' => 'María Deudora']);
        $order = Order::factory()->delivered()->withChickens(1, 5, 1000)->create([
            'customer_id' => $customer->getKey(),
        ]);
        Payment::factory()->create([
            'order_id' => $order->getKey(),
            'amount' => '250.00',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('clientes.show', $customer))
            ->assertOk()
            ->assertSeeText('María Deudora')
            ->assertSeeText('750,00')
            ->assertSeeText('Pedido #'.$order->getKey());
    }

    public function test_the_order_detail_lists_the_payment_history(): void
    {
        $order = Order::factory()->delivered()->withChickens(1, 5, 1000)->create([
            'price_per_lb' => '1000.00',
        ]);
        $seller = User::factory()->vendedor()->create(['name' => 'Vendedor Turno']);

        Payment::factory()->create([
            'order_id' => $order->getKey(),
            'user_id' => $seller->getKey(),
            'amount' => '400.00',
            'method' => PaymentMethod::Transferencia,
            'note' => 'Primer abono',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('pedidos.show', $order))
            ->assertOk()
            ->assertSeeText('Primer abono')
            ->assertSeeText('Transferencia')
            ->assertSeeText('Vendedor Turno')
            ->assertSeeText('400,00')
            ->assertSeeText('600,00');
    }

    public function test_an_admin_can_delete_a_payment_and_reopen_a_collected_order(): void
    {
        $order = Order::factory()->collected()->withChickens(1, 5, 1000)->create([
            'price_per_lb' => '1000.00',
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->getKey(),
            'amount' => '1000.00',
        ]);
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->delete(route('pedidos.payments.destroy', [$order, $payment]))
            ->assertRedirect(route('pedidos.show', $order))
            ->assertSessionHas('status', 'Cobro eliminado y saldo recalculado.');

        $this->assertDatabaseMissing('payments', ['id' => $payment->getKey()]);
        $this->assertSame(OrderStatus::Entregado, $order->refresh()->status);
        $this->assertNull($order->paid_at);
        $this->assertSame(1000.0, $order->balance());
    }

    public function test_a_seller_cannot_delete_a_payment(): void
    {
        $order = Order::factory()->collected()->withChickens(1, 5, 1000)->create();
        $payment = Payment::factory()->create(['order_id' => $order->getKey()]);

        $this->actingAs(User::factory()->vendedor()->create())
            ->delete(route('pedidos.payments.destroy', [$order, $payment]))
            ->assertForbidden();

        $this->assertDatabaseHas('payments', ['id' => $payment->getKey()]);
    }
}
