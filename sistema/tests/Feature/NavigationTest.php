<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_access_main_sections(): void
    {
        Setting::factory()->create();
        $admin = User::factory()->create();

        foreach (['/', '/dashboard', '/pedidos', '/pedidos/nueva', '/pedidos/vender', '/por-cobrar', '/clientes', '/clientes/nuevo', '/configuracion', '/usuarios'] as $path) {
            $this->actingAs($admin)->get($path)->assertOk();
        }
    }

    public function test_the_settings_link_is_only_visible_for_admins(): void
    {
        $admin = User::factory()->create();
        $seller = User::factory()->vendedor()->create();

        $this->actingAs($admin)
            ->get('/')
            ->assertOk()
            ->assertSeeText('Pedidos')
            ->assertSeeText('Por cobrar')
            ->assertSeeText('Clientes')
            ->assertSeeText('Usuarios')
            ->assertSeeText('Configuración');

        $this->actingAs($seller)
            ->get('/')
            ->assertOk()
            ->assertSeeText('Pedidos')
            ->assertDontSeeText('Usuarios')
            ->assertDontSeeText('Configuración');

        $this->actingAs($seller)->get('/configuracion')->assertForbidden();
        $this->actingAs($seller)->get('/usuarios')->assertForbidden();
    }

    public function test_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
    }
}
