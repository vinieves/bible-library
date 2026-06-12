<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_audio_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('audio_track_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('progress_seconds')->default(0);
            $table->boolean('completed')->default(false);
            $table->timestamp('last_played_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'audio_track_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_audio_progress');
    }
};
