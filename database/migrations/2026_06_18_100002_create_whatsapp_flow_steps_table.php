<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_flow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flow_id')->constrained('whatsapp_flows')->cascadeOnDelete();
            $table->unsignedInteger('order')->default(1);
            $table->string('type');
            $table->text('content')->nullable();
            $table->string('caption', 1000)->nullable();
            $table->string('file_name')->nullable();
            $table->string('media_url', 2000)->nullable();
            $table->unsignedInteger('delay_seconds')->default(0);
            $table->unsignedInteger('typing_delay')->default(3);
            $table->timestamps();

            $table->index(['flow_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_flow_steps');
    }
};
