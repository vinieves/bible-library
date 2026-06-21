<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evolution_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event')->nullable();
            $table->string('instance')->nullable();
            $table->string('route_slug')->nullable();
            $table->string('phone_normalized', 32)->nullable();
            $table->string('remote_jid')->nullable();
            $table->boolean('from_me')->nullable();
            $table->string('message_preview', 500)->nullable();
            $table->unsignedTinyInteger('inbound_count')->default(0);
            $table->string('processing_status');
            $table->text('processing_message')->nullable();
            $table->json('payload')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('event');
            $table->index('instance');
            $table->index('phone_normalized');
            $table->index('processing_status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evolution_webhook_logs');
    }
};
