<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminPlan = Plan::query()->where('slug', 'admin')->firstOrFail();
        $planCompleto = Plan::query()->where('slug', 'completo')->firstOrFail();

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@biblioteca.test'],
            [
                'name' => 'Administrador',
                'password' => 'password',
                'is_admin' => true,
                'email_verified_at' => now(),
            ]
        );

        $admin->plans()->syncWithoutDetaching([
            $adminPlan->id => [
                'granted_at' => now(),
                'granted_by' => 'seeder',
            ],
        ]);

        $cliente = User::query()->updateOrCreate(
            ['email' => 'cliente@biblioteca.test'],
            [
                'name' => 'Cliente Demo',
                'password' => 'password',
                'is_admin' => false,
                'email_verified_at' => now(),
            ]
        );

        $cliente->plans()->syncWithoutDetaching([
            $planCompleto->id => [
                'granted_at' => now(),
                'granted_by' => 'seeder',
            ],
        ]);
    }
}
