<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audio_tracks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audio_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('audio_file')->nullable();
            $table->string('duration')->nullable();
            $table->boolean('is_free')->default(false);
            $table->boolean('is_premium')->default(false);
            $table->foreignId('required_plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->string('external_checkout_url')->nullable();
            $table->unsignedSmallInteger('order')->default(0);
            $table->string('status')->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audio_tracks');
    }
};
