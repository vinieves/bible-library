<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $planPremium = Plan::query()->where('slug', 'premium')->firstOrFail();
        $planCompleto = Plan::query()->where('slug', 'completo')->firstOrFail();

        $products = [
            [
                'title' => 'Plan Completo — Hotmart',
                'product_code' => '7773711',
                'price' => 97.00,
                'plan_id' => $planCompleto->id,
                'checkout_url' => null,
                'sort_order' => 0,
            ],
            [
                'title' => 'Mapas Mentales Bíblicos Premium',
                'product_code' => 'MAPAS_PREMIUM',
                'price' => 29.90,
                'plan_id' => $planPremium->id,
                'checkout_url' => 'https://checkout.ejemplo.com/mapas-premium',
                'sort_order' => 1,
            ],
            [
                'title' => 'Devocional de 30 Días',
                'product_code' => 'DEVOCIONAL_30',
                'price' => 19.90,
                'plan_id' => $planPremium->id,
                'checkout_url' => 'https://checkout.ejemplo.com/devocional-30',
                'sort_order' => 2,
            ],
            [
                'title' => 'Biblioteca Premium de Estudios Bíblicos',
                'product_code' => 'BIBLIOTECA_PREMIUM',
                'price' => 97.00,
                'plan_id' => $planPremium->id,
                'checkout_url' => 'https://checkout.ejemplo.com/biblioteca-premium',
                'sort_order' => 3,
            ],
            [
                'title' => 'Estudios Avanzados de Apocalipsis',
                'product_code' => 'APOCALIPSIS_AVANZADO',
                'price' => 39.90,
                'plan_id' => $planCompleto->id,
                'checkout_url' => 'https://checkout.ejemplo.com/apocalipsis-avanzado',
                'sort_order' => 4,
            ],
            [
                'title' => 'Estudios Avanzados de Proverbios',
                'product_code' => 'PROVERBIOS_AVANZADO',
                'price' => 34.90,
                'plan_id' => $planCompleto->id,
                'checkout_url' => 'https://checkout.ejemplo.com/proverbios-avanzado',
                'sort_order' => 5,
            ],
        ];

        foreach ($products as $product) {
            Product::query()->updateOrCreate(
                ['product_code' => $product['product_code']],
                [
                    'title' => $product['title'],
                    'slug' => Str::slug($product['title']),
                    'description' => 'Producto digital exclusivo para ampliar su biblioteca bíblica.',
                    'price' => $product['price'],
                    'image' => null,
                    'checkout_url' => $product['checkout_url'],
                    'plan_id' => $product['plan_id'],
                    'is_active' => true,
                    'sort_order' => $product['sort_order'],
                ]
            );
        }
    }
}
