<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audio_categories', function (Blueprint $table) {
            $table->string('badge_color', 20)->default('gold')->after('slug');
        });

        $now = now();

        $generalId = DB::table('audio_categories')->where('slug', 'general')->value('id');

        if (! $generalId) {
            $generalId = DB::table('audio_categories')->insertGetId([
                'name' => 'General',
                'slug' => 'general',
                'badge_color' => 'gold',
                'description' => 'Audios sin categoría específica.',
                'order' => 999,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('audio_tracks')
            ->whereNull('audio_category_id')
            ->update(['audio_category_id' => $generalId]);

        $palette = [
            'introduccion' => 'gold',
            'devocionales' => 'green',
            'salmos' => 'emerald',
            'proverbios' => 'blue',
            'evangelios' => 'purple',
            'oraciones-guiadas' => 'rose',
            'estudios-premium' => 'amber',
            'general' => 'gold',
        ];

        foreach ($palette as $slug => $color) {
            DB::table('audio_categories')
                ->where('slug', $slug)
                ->update(['badge_color' => $color]);
        }
    }

    public function down(): void
    {
        Schema::table('audio_categories', function (Blueprint $table) {
            $table->dropColumn('badge_color');
        });
    }
};
