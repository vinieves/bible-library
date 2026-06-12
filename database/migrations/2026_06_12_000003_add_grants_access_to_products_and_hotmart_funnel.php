<?php

use App\Models\Plan;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('grants_access')->default(true)->after('plan_id');
        });

        $planCompleto = Plan::query()->where('slug', 'completo')->first();

        if (! $planCompleto) {
            return;
        }

        $funnelProducts = [
            [
                'product_code' => '7773711',
                'title' => 'Plan Completo — Hotmart',
                'grants_access' => true,
                'sort_order' => 0,
            ],
            [
                'product_code' => '7847835',
                'title' => 'Hotmart — Order Bump 1',
                'grants_access' => false,
                'sort_order' => 10,
            ],
            [
                'product_code' => '7896138',
                'title' => 'Hotmart — Order Bump 2',
                'grants_access' => false,
                'sort_order' => 11,
            ],
            [
                'product_code' => '7896115',
                'title' => 'Hotmart — Order Bump 3',
                'grants_access' => false,
                'sort_order' => 12,
            ],
            [
                'product_code' => '7847834',
                'title' => 'Hotmart — Upsell 1',
                'grants_access' => false,
                'sort_order' => 20,
            ],
            [
                'product_code' => '7847833',
                'title' => 'Hotmart — Upsell 2',
                'grants_access' => false,
                'sort_order' => 21,
            ],
            [
                'product_code' => '7900920',
                'title' => 'Hotmart — Upsell 3',
                'grants_access' => false,
                'sort_order' => 22,
            ],
        ];

        foreach ($funnelProducts as $item) {
            Product::query()->updateOrCreate(
                ['product_code' => $item['product_code']],
                [
                    'title' => $item['title'],
                    'slug' => Str::slug($item['title']),
                    'description' => $item['grants_access']
                        ? 'Produto principal da esteira Hotmart. Libera o Plan Completo.'
                        : 'Produto de funil Hotmart (order bump / upsell). Registrado sem liberação de acesso.',
                    'price' => 0,
                    'image' => null,
                    'checkout_url' => null,
                    'plan_id' => $item['grants_access'] ? $planCompleto->id : null,
                    'grants_access' => $item['grants_access'],
                    'is_active' => true,
                    'sort_order' => $item['sort_order'],
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('grants_access');
        });
    }
};
