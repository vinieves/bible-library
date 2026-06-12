<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_dispatch_logs', function (Blueprint $table) {
            $table->id();
            $table->string('trigger');
            $table->string('status');
            $table->string('phone');
            $table->string('phone_normalized')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('purchase_id')->nullable()->constrained()->nullOnDelete();
            $table->text('message')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->unsignedTinyInteger('attempt')->default(1);
            $table->json('evolution_response')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('trigger');
            $table->index('status');
            $table->index('phone');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_dispatch_logs');
    }
};
