<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('video_file')->nullable();
            $table->string('duration')->nullable();
            $table->boolean('is_free')->default(false);
            $table->boolean('is_premium')->default(false);
            $table->foreignId('required_plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->string('external_checkout_url')->nullable();
            $table->unsignedSmallInteger('order')->default(0);
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Schema::create('user_video_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('video_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('progress_seconds')->default(0);
            $table->boolean('completed')->default(false);
            $table->timestamp('last_played_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'video_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_video_progress');
        Schema::dropIfExists('videos');
        Schema::dropIfExists('video_categories');
    }
};
