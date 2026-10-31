<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_single_settings_row_is_created_with_defaults(): void
    {
        $setting = Setting::current();

        $this->assertSame('0.00', $setting->price_per_lb);
        $this->assertTrue($setting->auto_price);
        $this->assertDatabaseCount('settings', 1);

        $this->assertSame($setting->getKey(), Setting::current()->getKey());
        $this->assertDatabaseCount('settings', 1);
    }

    public function test_an_admin_can_view_the_settings_screen(): void
    {
        Setting::factory()->create(['price_per_lb' => '1500.00', 'auto_price' => true]);

        $this->actingAs(User::factory()->create())
            ->get('/configuracion')
            ->assertOk()
            ->assertSeeText('Configuración')
            ->assertSeeText('Precio por libra')
            ->assertSeeText('Calcular el precio automáticamente');
    }

    public function test_a_seller_cannot_view_or_update_the_settings(): void
    {
        $seller = User::factory()->vendedor()->create();

        $this->actingAs($seller)->get('/configuracion')->assertForbidden();

        $this->actingAs($seller)
            ->patch('/configuracion', [
                'price_per_lb' => '2000.00',
                'auto_price' => '1',
            ])
            ->assertForbidden();
    }

    public function test_an_admin_can_update_the_price_per_pound(): void
    {
        Setting::factory()->create(['price_per_lb' => '1000.00', 'auto_price' => true]);

        $this->actingAs(User::factory()->create())
            ->patch('/configuracion', [
                'price_per_lb' => '1850.50',
                'auto_price' => '1',
            ])
            ->assertRedirect(route('configuracion.edit'))
            ->assertSessionHas('status', 'Configuración actualizada.');

        $setting = Setting::current();

        $this->assertSame('1850.50', $setting->price_per_lb);
        $this->assertTrue($setting->auto_price);
    }

    public function test_an_admin_can_turn_off_the_automatic_price(): void
    {
        Setting::factory()->create(['price_per_lb' => '1000.00', 'auto_price' => true]);

        $this->actingAs(User::factory()->create())
            ->patch('/configuracion', [
                'price_per_lb' => '1000.00',
                'auto_price' => '0',
            ])
            ->assertRedirect(route('configuracion.edit'));

        $this->assertFalse(Setting::current()->auto_price);
    }

    public function test_the_price_per_pound_is_required_and_cannot_be_negative(): void
    {
        Setting::factory()->create();

        $this->actingAs(User::factory()->create())
            ->from('/configuracion')
            ->patch('/configuracion', [
                'price_per_lb' => '-10',
                'auto_price' => '1',
            ])
            ->assertSessionHasErrors('price_per_lb');
    }
}
