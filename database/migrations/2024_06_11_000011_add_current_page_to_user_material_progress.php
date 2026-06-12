<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_material_progress', function (Blueprint $table) {
            $table->unsignedSmallInteger('current_page')->default(0)->after('last_page_read');
        });

        DB::table('user_material_progress')
            ->where('last_page_read', '>', 0)
            ->update(['current_page' => DB::raw('last_page_read')]);
    }

    public function down(): void
    {
        Schema::table('user_material_progress', function (Blueprint $table) {
            $table->dropColumn('current_page');
        });
    }
};
