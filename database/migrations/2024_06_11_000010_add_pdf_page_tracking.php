<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->unsignedSmallInteger('pdf_page_count')->nullable()->after('pdf_path');
        });

        Schema::table('user_material_progress', function (Blueprint $table) {
            $table->unsignedSmallInteger('last_page_read')->default(0)->after('is_favorite');
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn('pdf_page_count');
        });

        Schema::table('user_material_progress', function (Blueprint $table) {
            $table->dropColumn('last_page_read');
        });
    }
};
