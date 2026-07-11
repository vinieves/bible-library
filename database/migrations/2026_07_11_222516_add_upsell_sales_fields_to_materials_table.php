<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->string('upsell_title')->nullable()->after('hotmart_product_code');
            $table->text('upsell_subtitle')->nullable()->after('upsell_title');
            $table->json('upsell_gallery')->nullable()->after('upsell_subtitle');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn(['upsell_title', 'upsell_subtitle', 'upsell_gallery']);
        });
    }
};
