<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_pending_inbound_responses', function (Blueprint $table) {
            $table->id();
            $table->string('phone_normalized', 32);
            $table->string('instance_name')->nullable();
            $table->string('message_id')->nullable();
            $table->string('remote_jid')->nullable();
            $table->timestamp('received_at');
            $table->timestamps();

            $table->unique(['phone_normalized', 'instance_name'], 'whatsapp_pending_inbound_phone_instance');
            $table->index('received_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_pending_inbound_responses');
    }
};
