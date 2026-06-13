<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('video_categories', function (Blueprint $table) {
            $table->string('badge_color', 20)->default('gold')->after('slug');
        });

        $palette = [
            'estudios' => 'purple',
            'devocionales' => 'green',
            'salmos' => 'emerald',
            'evangelios' => 'blue',
            'oracion' => 'rose',
            'general' => 'gold',
        ];

        $fallback = ['gold', 'green', 'emerald', 'blue', 'purple', 'rose', 'amber'];
        $categories = DB::table('video_categories')->orderBy('id')->get(['id', 'slug']);

        foreach ($categories as $index => $category) {
            $color = $palette[$category->slug] ?? $fallback[$index % count($fallback)];

            DB::table('video_categories')
                ->where('id', $category->id)
                ->update(['badge_color' => $color]);
        }
    }

    public function down(): void
    {
        Schema::table('video_categories', function (Blueprint $table) {
            $table->dropColumn('badge_color');
        });
    }
};
