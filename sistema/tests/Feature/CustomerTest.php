<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_list_can_be_viewed_and_searched(): void
    {
        Customer::factory()->create(['name' => 'María López', 'phone' => '1145678901']);
        Customer::factory()->create(['name' => 'Juan Pérez', 'phone' => '1198765432']);

        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get('/clientes')
            ->assertOk()
            ->assertSeeText('María López')
            ->assertSeeText('Juan Pérez');

        $this->actingAs($admin)
            ->get('/clientes?buscar=María')
            ->assertOk()
            ->assertSeeText('María López')
            ->assertDontSeeText('Juan Pérez');

        $this->actingAs($admin)
            ->get('/clientes?buscar=1198765432')
            ->assertOk()
            ->assertSeeText('Juan Pérez')
            ->assertDontSeeText('María López');
    }

    public function test_customer_can_be_created_and_viewed(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->post('/clientes', [
                'name' => '  Ana Gómez  ',
                'phone' => ' 1133445566 ',
                'note' => 'Prefiere contacto por WhatsApp',
            ]);

        $customer = Customer::query()->firstOrFail();

        $response->assertRedirect(route('clientes.show', $customer));

        $this->assertSame('Ana Gómez', $customer->name);
        $this->assertSame('1133445566', $customer->phone);

        $this->actingAs(User::factory()->create())
            ->get(route('clientes.show', $customer))
            ->assertOk()
            ->assertSeeText('Ana Gómez')
            ->assertSeeText('1133445566');
    }

    public function test_seller_can_create_customers(): void
    {
        $this->actingAs(User::factory()->vendedor()->create())
            ->post('/clientes', ['name' => 'Cliente del vendedor'])
            ->assertRedirect();

        $this->assertDatabaseHas('customers', ['name' => 'Cliente del vendedor']);
    }

    public function test_customer_can_be_updated(): void
    {
        $customer = Customer::factory()->create(['name' => 'Nombre viejo']);

        $this->actingAs(User::factory()->create())
            ->patch(route('clientes.update', $customer), [
                'name' => 'Nombre nuevo',
                'phone' => '1100000000',
                'note' => 'Nota nueva',
            ])
            ->assertRedirect(route('clientes.show', $customer));

        $customer->refresh();

        $this->assertSame('Nombre nuevo', $customer->name);
        $this->assertSame('1100000000', $customer->phone);
        $this->assertSame('Nota nueva', $customer->note);
    }

    public function test_customer_name_is_required(): void
    {
        $this->actingAs(User::factory()->create())
            ->from('/clientes/nuevo')
            ->post('/clientes', ['name' => '   ', 'phone' => ''])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('customers', 0);
    }

    public function test_blank_phone_is_stored_as_null(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/clientes', ['name' => 'Sin teléfono', 'phone' => '   '])
            ->assertRedirect();

        $this->assertNull(Customer::query()->firstOrFail()->phone);
    }

    public function test_customer_can_be_created_from_a_json_request(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/clientes', ['name' => 'Cliente rápido', 'phone' => '1122334455'])
            ->assertCreated()
            ->assertJson([
                'name' => 'Cliente rápido',
                'phone' => '1122334455',
            ]);

        $this->assertDatabaseHas('customers', ['name' => 'Cliente rápido']);
    }

    public function test_an_admin_can_delete_a_customer_and_its_order_history(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->collected()->create(['customer_id' => $customer->getKey()]);
        Payment::factory()->create(['order_id' => $order->getKey()]);
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->delete(route('clientes.destroy', $customer))
            ->assertRedirect(route('clientes.index'));

        $this->assertDatabaseMissing('customers', ['id' => $customer->getKey()]);
        $this->assertDatabaseMissing('orders', ['id' => $order->getKey()]);
        $this->assertDatabaseMissing('payments', ['order_id' => $order->getKey()]);
    }

    public function test_a_seller_cannot_delete_a_customer(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs(User::factory()->vendedor()->create())
            ->delete(route('clientes.destroy', $customer))
            ->assertForbidden();

        $this->assertDatabaseHas('customers', ['id' => $customer->getKey()]);
    }
}
