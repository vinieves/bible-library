<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bible_topic_verses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bible_topic_id')->constrained()->cascadeOnDelete();
            $table->string('book_abbr', 12);
            $table->unsignedSmallInteger('chapter');
            $table->unsignedSmallInteger('verse');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bible_topic_verses');
    }
};
