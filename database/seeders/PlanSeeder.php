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
                'name' => 'Plan Básico',
                'slug' => 'basico',
                'description' => 'Acceso a materiales fundamentales de la biblioteca.',
                'level' => 1,
                'is_admin' => false,
                'sort_order' => 1,
            ],
            [
                'name' => 'Plan Completo',
                'slug' => 'completo',
                'description' => 'Acceso a la mayoría de libros y estudios bíblicos.',
                'level' => 2,
                'is_admin' => false,
                'sort_order' => 2,
            ],
            [
                'name' => 'Biblioteca Premium',
                'slug' => 'premium',
                'description' => 'Acceso total a bonos, mapas mentales y estudios premium.',
                'level' => 3,
                'is_admin' => false,
                'sort_order' => 3,
            ],
            [
                'name' => 'Acceso Vitalicio',
                'slug' => 'vitalicio',
                'description' => 'Acceso permanente a toda la biblioteca.',
                'level' => 4,
                'is_admin' => false,
                'sort_order' => 4,
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
