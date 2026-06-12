<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('platform')->default('generic')->after('id');
            $table->string('phone')->nullable()->after('name');
            $table->unique(['platform', 'external_reference']);
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropUnique(['platform', 'external_reference']);
            $table->dropColumn(['platform', 'phone']);
        });
    }
};
