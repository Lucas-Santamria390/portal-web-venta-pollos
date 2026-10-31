<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_reservation_stores_quantity_and_optional_weights_without_total(): void
    {
        Setting::factory()->create(['price_per_lb' => '1000.00', 'auto_price' => true]);
        $customer = Customer::factory()->create();
        $seller = User::factory()->vendedor()->create();

        $response = $this->actingAs($seller)->post('/pedidos', [
            'customer_id' => $customer->getKey(),
            'quantity' => 3,
            'delivery_date' => '2026-11-01',
            'note' => 'Entregar temprano',
            'weights' => ['5.50', '', '6.25'],
        ]);

        $order = Order::query()->with('items')->firstOrFail();

        $response->assertRedirect(route('pedidos.show', $order));

        $this->assertSame(OrderStatus::Reservado, $order->status);
        $this->assertSame('0.00', $order->total);
        $this->assertSame('1000.00', $order->price_per_lb);
        $this->assertSame($customer->getKey(), $order->customer_id);
        $this->assertSame($seller->getKey(), $order->user_id);
        $this->assertSame('Entregar temprano', $order->note);
        $this->assertSame('2026-11-01', $order->delivery_date->format('Y-m-d'));
        $this->assertCount(3, $order->items);

        $this->assertSame('5.50', $order->items[0]->weight_lb);
        $this->assertNull($order->items[1]->weight_lb);
        $this->assertSame('6.25', $order->items[2]->weight_lb);
        $this->assertNull($order->items[0]->price);
        $this->assertNull($order->items[1]->price);
    }

    public function test_a_reservation_requires_a_customer_and_a_quantity(): void
    {
        $this->actingAs(User::factory()->create())
            ->from('/pedidos/nueva')
            ->post('/pedidos', ['quantity' => 0])
            ->assertSessionHasErrors(['customer_id', 'quantity']);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_a_seller_can_edit_a_reserved_order(): void
    {
        Setting::factory()->create(['price_per_lb' => '1000.00', 'auto_price' => true]);
        $customer = Customer::factory()->create();
        $order = Order::factory()->withReservedChickens(2)->create([
            'customer_id' => $customer->getKey(),
            'price_per_lb' => '1000.00',
        ]);

        $this->actingAs(User::factory()->vendedor()->create())
            ->patch(route('pedidos.update', $order), [
                'customer_id' => $customer->getKey(),
                'quantity' => 3,
                'weights' => ['5.00', '6.00', '7.00'],
                'delivery_date' => '2026-12-01',
                'note' => 'Actualizada',
            ])
            ->assertRedirect(route('pedidos.show', $order));

        $order->refresh()->load('items');

        $this->assertSame(OrderStatus::Reservado, $order->status);
        $this->assertSame('0.00', $order->total);
        $this->assertSame('Actualizada', $order->note);
        $this->assertCount(3, $order->items);
        $this->assertSame('7.00', $order->items[2]->weight_lb);
    }

    public function test_a_delivered_order_can_only_be_edited_by_an_admin_without_payments(): void
    {
        Setting::factory()->create(['price_per_lb' => '1000.00', 'auto_price' => true]);
        $customer = Customer::factory()->create();
        $admin = User::factory()->create();
        $seller = User::factory()->vendedor()->create();
        $order = Order::factory()->delivered()->withChickens(2, 5, 5000)->create([
            'customer_id' => $customer->getKey(),
            'price_per_lb' => '1000.00',
        ]);

        $this->actingAs($seller)->get(route('pedidos.edit', $order))->assertForbidden();
        $this->actingAs($seller)->patch(route('pedidos.update', $order), [
            'customer_id' => $customer->getKey(),
            'quantity' => 2,
            'weights' => ['5.00', '6.00'],
            'price_per_lb' => '1200.00',
        ])->assertForbidden();

        $this->actingAs($admin)->patch(route('pedidos.update', $order), [
            'customer_id' => $customer->getKey(),
            'quantity' => 2,
            'weights' => ['5.00', '6.00'],
            'price_per_lb' => '1200.00',
            'note' => 'Pesos corregidos',
        ])->assertRedirect(route('pedidos.show', $order));

        $order->refresh()->load('items');

        $this->assertSame(OrderStatus::Entregado, $order->status);
        $this->assertSame('13200.00', $order->total);
        $this->assertSame('1200.00', $order->price_per_lb);
        $this->assertSame('6000.00', $order->items[0]->price);
        $this->assertSame('7200.00', $order->items[1]->price);

        Payment::factory()->create([
            'order_id' => $order->getKey(),
            'amount' => '1000.00',
        ]);

        $this->actingAs($admin)->get(route('pedidos.edit', $order))->assertForbidden();
    }

    public function test_delivery_requires_a_weight_for_every_chicken(): void
    {
        Setting::factory()->create(['price_per_lb' => '1000.00', 'auto_price' => true]);
        $order = Order::factory()->withReservedChickens(2)->create(['price_per_lb' => '1000.00']);

        $this->actingAs(User::factory()->create())
            ->from(route('pedidos.deliver.edit', $order))
            ->post(route('pedidos.deliver', $order), [
                'weights' => ['5.00'],
                'price_per_lb' => '1000.00',
            ])
            ->assertSessionHasErrors('weights');

        $this->assertSame(OrderStatus::Reservado, $order->refresh()->status);
        $this->assertSame('0.00', $order->total);
    }

    public function test_delivery_calculates_each_price_from_pounds_and_the_adjusted_price_per_pound(): void
    {
        Setting::factory()->create(['price_per_lb' => '1000.00', 'auto_price' => true]);
        $order = Order::factory()->withReservedChickens(2)->create(['price_per_lb' => '1000.00']);

        $this->actingAs(User::factory()->vendedor()->create())
            ->post(route('pedidos.deliver', $order), [
                'weights' => ['5.00', '6.50'],
                'price_per_lb' => '1200.00',
            ])
            ->assertRedirect(route('pedidos.show', $order));

        $order->refresh()->load('items');

        $this->assertSame(OrderStatus::Entregado, $order->status);
        $this->assertNotNull($order->delivered_at);
        $this->assertSame('1200.00', $order->price_per_lb);
        $this->assertSame('13800.00', $order->total);
        $this->assertSame('6000.00', $order->items[0]->price);
        $this->assertSame('7800.00', $order->items[1]->price);
    }

    public function test_delivery_with_manual_prices_uses_each_chicken_price(): void
    {
        Setting::factory()->create(['price_per_lb' => '1000.00', 'auto_price' => false]);
        $order = Order::factory()->withReservedChickens(2)->create(['price_per_lb' => '1000.00']);

        $this->actingAs(User::factory()->create())
            ->post(route('pedidos.deliver', $order), [
                'weights' => ['5.00', '6.00'],
                'prices' => ['5000.00', '7000.00'],
            ])
            ->assertRedirect(route('pedidos.show', $order));

        $order->refresh()->load('items');

        $this->assertSame(OrderStatus::Entregado, $order->status);
        $this->assertSame('12000.00', $order->total);
        $this->assertSame('5000.00', $order->items[0]->price);
        $this->assertSame('7000.00', $order->items[1]->price);
    }

    public function test_manual_delivery_requires_a_price_for_every_chicken(): void
    {
        Setting::factory()->create(['price_per_lb' => '1000.00', 'auto_price' => false]);
        $order = Order::factory()->withReservedChickens(1)->create(['price_per_lb' => '1000.00']);

        $this->actingAs(User::factory()->create())
            ->from(route('pedidos.deliver.edit', $order))
            ->post(route('pedidos.deliver', $order), [
                'weights' => ['5.00'],
            ])
            ->assertSessionHasErrors('prices');

        $this->assertSame(OrderStatus::Reservado, $order->refresh()->status);
    }

    public function test_a_delivery_cannot_happen_twice(): void
    {
        Setting::factory()->create(['price_per_lb' => '1000.00', 'auto_price' => true]);
        $order = Order::factory()->delivered()->withChickens(1, 5, 5000)->create([
            'price_per_lb' => '1000.00',
        ]);

        $this->actingAs(User::factory()->create())
            ->from(route('pedidos.show', $order))
            ->post(route('pedidos.deliver', $order), [
                'weights' => ['5.00'],
                'price_per_lb' => '1000.00',
            ])
            ->assertSessionHasErrors('order');

        $this->assertSame(OrderStatus::Entregado, $order->refresh()->status);
        $this->assertSame('5000.00', $order->total);
    }

    public function test_a_direct_sale_fully_paid_without_a_customer_is_collected(): void
    {
        Setting::factory()->create(['price_per_lb' => '1000.00', 'auto_price' => true]);
        $seller = User::factory()->vendedor()->create();

        $response = $this->actingAs($seller)->post('/pedidos/vender', [
            'customer_id' => '',
            'quantity' => 2,
            'weights' => ['5.00', '6.00'],
            'price_per_lb' => '1000.00',
            'paid_amount' => '11000.00',
            'method' => PaymentMethod::Efectivo->value,
        ]);

        $order = Order::query()->with('items')->firstOrFail();

        $response->assertRedirect(route('pedidos.show', $order));

        $this->assertNull($order->customer_id);
        $this->assertSame(OrderStatus::Cobrado, $order->status);
        $this->assertSame('11000.00', $order->total);
        $this->assertNotNull($order->delivered_at);
        $this->assertNotNull($order->paid_at);
        $this->assertCount(2, $order->items);
        $this->assertSame('5000.00', $order->items[0]->price);
        $this->assertSame('6000.00', $order->items[1]->price);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->getKey(),
            'user_id' => $seller->getKey(),
            'amount' => '11000.00',
            'method' => 'efectivo',
        ]);
    }

    public function test_a_direct_sale_with_a_balance_requires_a_customer_and_stays_delivered(): void
    {
        Setting::factory()->create(['price_per_lb' => '1000.00', 'auto_price' => true]);
        $customer = Customer::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/pedidos/vender')
            ->post('/pedidos/vender', [
                'customer_id' => '',
                'quantity' => 2,
                'weights' => ['5.00', '6.00'],
                'price_per_lb' => '1000.00',
                'paid_amount' => '5000.00',
                'method' => PaymentMethod::Efectivo->value,
            ])
            ->assertSessionHasErrors('customer_id');

        $this->assertDatabaseCount('orders', 0);

        $this->actingAs($user)
            ->post('/pedidos/vender', [
                'customer_id' => $customer->getKey(),
                'quantity' => 2,
                'weights' => ['5.00', '6.00'],
                'price_per_lb' => '1000.00',
                'paid_amount' => '5000.00',
                'method' => PaymentMethod::Efectivo->value,
            ])
            ->assertRedirect();

        $order = Order::query()->firstOrFail();

        $this->assertSame(OrderStatus::Entregado, $order->status);
        $this->assertSame($customer->getKey(), $order->customer_id);
        $this->assertSame('11000.00', $order->total);
        $this->assertSame(5000.0, $order->paidAmount());
        $this->assertSame(6000.0, $order->balance());
    }

    public function test_a_direct_sale_rejects_a_payment_above_the_total(): void
    {
        Setting::factory()->create(['price_per_lb' => '1000.00', 'auto_price' => true]);

        $this->actingAs(User::factory()->create())
            ->from('/pedidos/vender')
            ->post('/pedidos/vender', [
                'customer_id' => Customer::factory()->create()->getKey(),
                'quantity' => 1,
                'weights' => ['5.00'],
                'price_per_lb' => '1000.00',
                'paid_amount' => '6000.00',
                'method' => PaymentMethod::Efectivo->value,
            ])
            ->assertSessionHasErrors('paid_amount');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_a_direct_sale_can_use_manual_prices(): void
    {
        Setting::factory()->create(['price_per_lb' => '1000.00', 'auto_price' => false]);

        $this->actingAs(User::factory()->create())
            ->post('/pedidos/vender', [
                'customer_id' => '',
                'quantity' => 2,
                'weights' => ['5.00', '6.00'],
                'prices' => ['4000.00', '5000.00'],
                'paid_amount' => '9000.00',
                'method' => PaymentMethod::Transferencia->value,
            ])
            ->assertRedirect();

        $order = Order::query()->with('items')->firstOrFail();

        $this->assertSame(OrderStatus::Cobrado, $order->status);
        $this->assertSame('9000.00', $order->total);
        $this->assertSame('4000.00', $order->items[0]->price);
        $this->assertSame('5000.00', $order->items[1]->price);
    }

    public function test_an_admin_can_cancel_a_reservation_without_payments(): void
    {
        $order = Order::factory()->withReservedChickens(1)->create();

        $this->actingAs(User::factory()->create())
            ->post(route('pedidos.cancel', $order))
            ->assertRedirect(route('pedidos.show', $order));

        $order->refresh();

        $this->assertSame(OrderStatus::Anulado, $order->status);
        $this->assertNotNull($order->cancelled_at);
    }

    public function test_a_seller_cannot_cancel_an_order(): void
    {
        $order = Order::factory()->withReservedChickens(1)->create();

        $this->actingAs(User::factory()->vendedor()->create())
            ->post(route('pedidos.cancel', $order))
            ->assertForbidden();

        $this->assertSame(OrderStatus::Reservado, $order->refresh()->status);
    }

    public function test_an_order_with_payments_cannot_be_cancelled(): void
    {
        $order = Order::factory()->delivered()->withChickens(1, 5, 5000)->create([
            'price_per_lb' => '1000.00',
        ]);
        Payment::factory()->create([
            'order_id' => $order->getKey(),
            'amount' => '1000.00',
        ]);

        $this->actingAs(User::factory()->create())
            ->from(route('pedidos.show', $order))
            ->post(route('pedidos.cancel', $order))
            ->assertSessionHasErrors('order');

        $this->assertSame(OrderStatus::Entregado, $order->refresh()->status);
    }

    public function test_an_admin_can_delete_an_order_with_its_items_and_payments(): void
    {
        $order = Order::factory()->collected()->withChickens(1, 5, 1000)->create();
        Payment::factory()->create(['order_id' => $order->getKey()]);
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->delete(route('pedidos.destroy', $order))
            ->assertRedirect(route('pedidos.index'));

        $this->assertDatabaseMissing('orders', ['id' => $order->getKey()]);
        $this->assertDatabaseMissing('order_items', ['order_id' => $order->getKey()]);
        $this->assertDatabaseMissing('payments', ['order_id' => $order->getKey()]);
    }

    public function test_a_seller_cannot_delete_an_order(): void
    {
        $order = Order::factory()->create();

        $this->actingAs(User::factory()->vendedor()->create())
            ->delete(route('pedidos.destroy', $order))
            ->assertForbidden();

        $this->assertDatabaseHas('orders', ['id' => $order->getKey()]);
    }

    public function test_orders_list_can_be_filtered_and_searched(): void
    {
        $admin = User::factory()->create();
        $ana = Customer::factory()->create(['name' => 'Ana Torres']);
        $luis = Customer::factory()->create(['name' => 'Luis Díaz']);

        Order::factory()->withReservedChickens(1)->for($ana)->create(['user_id' => $admin->getKey()]);
        Order::factory()->delivered()->withChickens(1, 5, 1000)->for($luis)->create(['user_id' => $admin->getKey()]);
        Order::factory()->collected()->withChickens(1, 5, 1000)->for($ana)->create(['user_id' => $admin->getKey()]);
        Order::factory()->cancelled()->withReservedChickens(1)->for($luis)->create(['user_id' => $admin->getKey()]);

        $this->actingAs($admin)
            ->get('/pedidos')
            ->assertOk()
            ->assertSeeText('Ana Torres')
            ->assertSeeText('Luis Díaz');

        $this->actingAs($admin)
            ->get('/pedidos?estado=reservado')
            ->assertOk()
            ->assertSeeText('Ana Torres')
            ->assertDontSeeText('Luis Díaz');

        $this->actingAs($admin)
            ->get('/pedidos?estado=cobrado')
            ->assertOk()
            ->assertSeeText('Ana Torres')
            ->assertDontSeeText('Luis Díaz');

        $this->actingAs($admin)
            ->get('/pedidos?buscar=Ana')
            ->assertOk()
            ->assertSeeText('Ana Torres')
            ->assertDontSeeText('Luis Díaz');
    }

    public function test_order_detail_shows_chickens_pounds_and_money(): void
    {
        $order = Order::factory()->delivered()->withChickens(1, 5, 6000)->create([
            'price_per_lb' => '1200.00',
        ]);
        $order->customer->update(['name' => 'Cliente Detalle']);

        $this->actingAs(User::factory()->create())
            ->get(route('pedidos.show', $order))
            ->assertOk()
            ->assertSeeText('Pedido #'.$order->getKey())
            ->assertSeeText('Cliente Detalle')
            ->assertSeeText('Pollo 1')
            ->assertSeeText('5,00 lb')
            ->assertSeeText('6.000,00')
            ->assertSeeText('Entregado');
    }

    public function test_the_edit_and_delivery_forms_render_for_a_reserved_order(): void
    {
        Setting::factory()->create(['price_per_lb' => '1000.00', 'auto_price' => true]);
        $order = Order::factory()->withReservedChickens(2)->create([
            'price_per_lb' => '1000.00',
        ]);
        $seller = User::factory()->vendedor()->create();

        $this->actingAs($seller)
            ->get(route('pedidos.edit', $order))
            ->assertOk()
            ->assertSeeText('Editar pedido #'.$order->getKey())
            ->assertSeeText('Guardar cambios');

        $this->actingAs($seller)
            ->get(route('pedidos.deliver.edit', $order))
            ->assertOk()
            ->assertSeeText('Entregar pedido #'.$order->getKey())
            ->assertSeeText('Confirmar entrega');
    }

    public function test_changing_the_settings_does_not_alter_a_delivered_order_snapshot(): void
    {
        $setting = Setting::factory()->create(['price_per_lb' => '1000.00', 'auto_price' => true]);
        $order = Order::factory()->delivered()->withChickens(1, 5, 5000)->create([
            'price_per_lb' => '1000.00',
        ]);

        $setting->update(['price_per_lb' => '2000.00']);

        $order->refresh();

        $this->assertSame('1000.00', $order->price_per_lb);
        $this->assertSame('5000.00', $order->total);
    }
}
