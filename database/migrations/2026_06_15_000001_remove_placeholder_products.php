<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const PLACEHOLDER_CODES = [
        'MAPAS_PREMIUM',
        'DEVOCIONAL_30',
        'BIBLIOTECA_PREMIUM',
        'APOCALIPSIS_AVANZADO',
        'PROVERBIOS_AVANZADO',
    ];

    public function up(): void
    {
        Product::query()
            ->whereIn('product_code', self::PLACEHOLDER_CODES)
            ->delete();
    }

    public function down(): void
    {
        // Produtos placeholder removidos de propósito; não recriar no rollback.
    }
};
