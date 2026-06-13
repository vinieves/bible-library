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

        Product::query()->updateOrCreate(
            ['product_code' => '7773711'],
            [
                'title' => 'Plan Completo — Hotmart',
                'slug' => Str::slug('Plan Completo — Hotmart'),
                'description' => 'Produto principal da esteira Hotmart. Libera o Plan Completo.',
                'price' => 0,
                'image' => null,
                'checkout_url' => null,
                'plan_id' => $planCompleto->id,
                'grants_access' => true,
                'is_active' => true,
                'sort_order' => 0,
            ]
        );
    }
}
