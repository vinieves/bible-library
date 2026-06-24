<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_bible_progress', function (Blueprint $table) {
            $table->unsignedInteger('monthly_verses_read')->default(0)->after('verse');
            $table->string('monthly_period', 7)->nullable()->after('monthly_verses_read');
        });
    }

    public function down(): void
    {
        Schema::table('user_bible_progress', function (Blueprint $table) {
            $table->dropColumn(['monthly_verses_read', 'monthly_period']);
        });
    }
};
