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
        $planCompleto = Plan::query()->where('slug', 'completo')->firstOrFail();

        $products = [
            [
                'title' => 'Plan Completo — Hotmart',
                'product_code' => '7773711',
                'price' => 97.00,
                'checkout_url' => null,
                'grants_access' => true,
                'sort_order' => 0,
            ],
            [
                'title' => 'Hotmart — Order Bump 1',
                'product_code' => '7847835',
                'price' => 0,
                'checkout_url' => null,
                'grants_access' => false,
                'sort_order' => 10,
            ],
            [
                'title' => 'Hotmart — Order Bump 2',
                'product_code' => '7896138',
                'price' => 0,
                'checkout_url' => null,
                'grants_access' => false,
                'sort_order' => 11,
            ],
            [
                'title' => 'Hotmart — Order Bump 3',
                'product_code' => '7896115',
                'price' => 0,
                'checkout_url' => null,
                'grants_access' => false,
                'sort_order' => 12,
            ],
            [
                'title' => 'Hotmart — Upsell 1',
                'product_code' => '7847834',
                'price' => 0,
                'checkout_url' => null,
                'grants_access' => false,
                'sort_order' => 20,
            ],
            [
                'title' => 'Hotmart — Upsell 2',
                'product_code' => '7847833',
                'price' => 0,
                'checkout_url' => null,
                'grants_access' => false,
                'sort_order' => 21,
            ],
            [
                'title' => 'Hotmart — Upsell 3',
                'product_code' => '7900920',
                'price' => 0,
                'checkout_url' => null,
                'grants_access' => false,
                'sort_order' => 22,
            ],
            [
                'title' => 'Mapas Mentales Bíblicos Premium',
                'product_code' => 'MAPAS_PREMIUM',
                'price' => 29.90,
                'checkout_url' => 'https://checkout.ejemplo.com/mapas-premium',
                'sort_order' => 1,
            ],
            [
                'title' => 'Devocional de 30 Días',
                'product_code' => 'DEVOCIONAL_30',
                'price' => 19.90,
                'checkout_url' => 'https://checkout.ejemplo.com/devocional-30',
                'sort_order' => 2,
            ],
            [
                'title' => 'Biblioteca Premium de Estudios Bíblicos',
                'product_code' => 'BIBLIOTECA_PREMIUM',
                'price' => 97.00,
                'checkout_url' => 'https://checkout.ejemplo.com/biblioteca-premium',
                'sort_order' => 3,
            ],
            [
                'title' => 'Estudios Avanzados de Apocalipsis',
                'product_code' => 'APOCALIPSIS_AVANZADO',
                'price' => 39.90,
                'checkout_url' => 'https://checkout.ejemplo.com/apocalipsis-avanzado',
                'sort_order' => 4,
            ],
            [
                'title' => 'Estudios Avanzados de Proverbios',
                'product_code' => 'PROVERBIOS_AVANZADO',
                'price' => 34.90,
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
                    'plan_id' => ($product['grants_access'] ?? true) ? $planCompleto->id : null,
                    'grants_access' => $product['grants_access'] ?? true,
                    'is_active' => true,
                    'sort_order' => $product['sort_order'],
                ]
            );
        }
    }
}
