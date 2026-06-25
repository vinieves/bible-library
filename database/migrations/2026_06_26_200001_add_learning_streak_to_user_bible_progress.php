<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_bible_progress', function (Blueprint $table) {
            $table->unsignedInteger('current_streak')->default(0)->after('monthly_period');
            $table->date('last_activity_date')->nullable()->after('current_streak');
        });
    }

    public function down(): void
    {
        Schema::table('user_bible_progress', function (Blueprint $table) {
            $table->dropColumn(['current_streak', 'last_activity_date']);
        });
    }
};
