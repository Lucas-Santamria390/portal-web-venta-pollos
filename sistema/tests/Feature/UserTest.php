<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_view_the_users_screen(): void
    {
        $admin = User::factory()->create();
        $seller = User::factory()->vendedor()->create(['name' => 'Vendedor Uno']);

        $this->actingAs($admin)
            ->get('/usuarios')
            ->assertOk()
            ->assertSeeText('Usuarios')
            ->assertSeeText('Vendedor Uno')
            ->assertSeeText($seller->email);
    }

    public function test_an_admin_can_open_the_create_and_edit_forms(): void
    {
        $admin = User::factory()->create();
        $seller = User::factory()->vendedor()->create(['name' => 'Vendedor Uno']);

        $this->actingAs($admin)
            ->get('/usuarios/nuevo')
            ->assertOk()
            ->assertSeeText('Nuevo usuario')
            ->assertSeeText('Contraseña')
            ->assertSeeText('Cuenta activa');

        $this->actingAs($admin)
            ->get("/usuarios/{$seller->id}/editar")
            ->assertOk()
            ->assertSeeText('Editar usuario')
            ->assertSeeText('Nueva contraseña (opcional)');

        $this->actingAs($admin)
            ->get("/usuarios/{$admin->id}/editar")
            ->assertOk()
            ->assertSeeText('No puedes desactivar tu propia cuenta.');
    }

    public function test_a_seller_cannot_manage_users(): void
    {
        $seller = User::factory()->vendedor()->create();
        $other = User::factory()->create();

        $this->actingAs($seller)->get('/usuarios')->assertForbidden();
        $this->actingAs($seller)->get('/usuarios/nuevo')->assertForbidden();
        $this->actingAs($seller)->post('/usuarios', [])->assertForbidden();
        $this->actingAs($seller)->get("/usuarios/{$other->id}/editar")->assertForbidden();
        $this->actingAs($seller)->patch("/usuarios/{$other->id}", [])->assertForbidden();
        $this->actingAs($seller)->patch("/usuarios/{$other->id}/estado")->assertForbidden();
    }

    public function test_an_admin_can_create_a_seller(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->post('/usuarios', [
                'name' => 'Vendedor Nuevo',
                'email' => 'vendedor@portal.local',
                'role' => UserRole::Vendedor->value,
                'password' => 'Clave1234',
                'password_confirmation' => 'Clave1234',
                'active' => '1',
            ])
            ->assertRedirect(route('usuarios.index'))
            ->assertSessionHas('status', 'Usuario «Vendedor Nuevo» creado.');

        $user = User::where('email', 'vendedor@portal.local')->firstOrFail();

        $this->assertSame('Vendedor Nuevo', $user->name);
        $this->assertSame(UserRole::Vendedor, $user->role);
        $this->assertTrue($user->active);
        $this->assertTrue(Hash::check('Clave1234', $user->password));
    }

    public function test_user_creation_validates_required_fields_and_unique_email(): void
    {
        $admin = User::factory()->create();
        User::factory()->create(['email' => 'repetido@portal.local']);

        $this->actingAs($admin)
            ->from('/usuarios/nuevo')
            ->post('/usuarios', [
                'name' => '',
                'email' => 'repetido@portal.local',
                'role' => UserRole::Vendedor->value,
                'password' => 'corta',
                'password_confirmation' => 'otra',
            ])
            ->assertSessionHasErrors(['name', 'email', 'password']);

        $this->assertDatabaseMissing('users', ['name' => '']);
    }

    public function test_an_admin_can_update_a_user_without_changing_the_password(): void
    {
        $admin = User::factory()->create();
        $seller = User::factory()->vendedor()->create(['password' => 'Original123']);

        $this->actingAs($admin)
            ->patch("/usuarios/{$seller->id}", [
                'name' => 'Vendedor Renombrado',
                'email' => 'renombrado@portal.local',
                'role' => UserRole::Admin->value,
                'password' => '',
                'password_confirmation' => '',
                'active' => '1',
            ])
            ->assertRedirect(route('usuarios.index'));

        $seller->refresh();

        $this->assertSame('Vendedor Renombrado', $seller->name);
        $this->assertSame('renombrado@portal.local', $seller->email);
        $this->assertSame(UserRole::Admin, $seller->role);
        $this->assertTrue(Hash::check('Original123', $seller->password));
    }

    public function test_an_admin_can_change_another_users_password(): void
    {
        $admin = User::factory()->create();
        $seller = User::factory()->vendedor()->create(['password' => 'Original123']);

        $this->actingAs($admin)
            ->patch("/usuarios/{$seller->id}", [
                'name' => $seller->name,
                'email' => $seller->email,
                'role' => $seller->role->value,
                'password' => 'NuevaClave123',
                'password_confirmation' => 'NuevaClave123',
                'active' => '1',
            ])
            ->assertRedirect(route('usuarios.index'));

        $this->assertTrue(Hash::check('NuevaClave123', $seller->refresh()->password));
    }

    public function test_an_admin_can_deactivate_and_reactivate_another_user(): void
    {
        $admin = User::factory()->create();
        $seller = User::factory()->vendedor()->create();

        $this->actingAs($admin)
            ->patch("/usuarios/{$seller->id}/estado")
            ->assertRedirect(route('usuarios.index'))
            ->assertSessionHas('status', "Usuario «{$seller->name}» desactivado.");

        $this->assertFalse($seller->refresh()->active);

        $this->actingAs($admin)
            ->patch("/usuarios/{$seller->id}/estado")
            ->assertRedirect(route('usuarios.index'))
            ->assertSessionHas('status', "Usuario «{$seller->name}» activado.");

        $this->assertTrue($seller->refresh()->active);
    }

    public function test_an_admin_cannot_deactivate_or_demote_themselves(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->patch("/usuarios/{$admin->id}/estado")
            ->assertRedirect(route('usuarios.index'))
            ->assertSessionHas('error', 'No puedes desactivar tu propia cuenta.');

        $this->assertTrue($admin->refresh()->active);

        $this->actingAs($admin)
            ->from("/usuarios/{$admin->id}/editar")
            ->patch("/usuarios/{$admin->id}", [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => UserRole::Vendedor->value,
                'active' => '0',
            ])
            ->assertSessionHasErrors(['role', 'active']);

        $admin->refresh();

        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue($admin->active);
    }
}
