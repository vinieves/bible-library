<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forum_persona_id')->constrained()->restrictOnDelete();
            $table->string('title')->nullable();
            $table->longText('body');
            $table->json('images')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('audio_file')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_posts');
    }
};
