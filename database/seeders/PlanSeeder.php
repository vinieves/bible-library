<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Plan Completo',
                'slug' => 'completo',
                'description' => 'Acceso completo a toda la biblioteca bíblica digital.',
                'level' => 1,
                'is_admin' => false,
                'sort_order' => 1,
            ],
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Acceso administrativo completo.',
                'level' => 99,
                'is_admin' => true,
                'sort_order' => 99,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::query()->updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
