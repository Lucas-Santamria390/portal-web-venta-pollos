<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => config('app.admin.email')],
            [
                'name' => 'Administrador',
                'password' => config('app.admin.password'),
                'role' => UserRole::Admin,
                'active' => true,
            ],
        );

        Setting::current();
    }
}
