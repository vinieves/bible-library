<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_inbound_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('phone_normalized', 30)->unique();
            $table->string('remote_jid')->nullable();
            $table->string('push_name')->nullable();
            $table->timestamp('first_message_at');
            $table->string('first_message_id')->nullable();
            $table->foreignId('flow_execution_id')->nullable()->constrained('whatsapp_flow_executions')->nullOnDelete();
            $table->timestamps();

            $table->index('first_message_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_inbound_contacts');
    }
};
