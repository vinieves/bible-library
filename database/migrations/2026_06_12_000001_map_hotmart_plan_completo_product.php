<?php

use App\Models\Plan;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $planCompleto = Plan::query()->where('slug', 'completo')->first();

        if (! $planCompleto) {
            return;
        }

        Product::query()->updateOrCreate(
            ['product_code' => '7773711'],
            [
                'title' => 'Plan Completo — Hotmart',
                'slug' => Str::slug('Plan Completo Hotmart'),
                'description' => 'Todos los LIBROS DE LA BIBLIA explicados versículo por versículo (Hotmart).',
                'price' => 97.00,
                'image' => null,
                'checkout_url' => null,
                'plan_id' => $planCompleto->id,
                'is_active' => true,
                'sort_order' => 0,
            ]
        );
    }

    public function down(): void
    {
        Product::query()->where('product_code', '7773711')->delete();
    }
};
