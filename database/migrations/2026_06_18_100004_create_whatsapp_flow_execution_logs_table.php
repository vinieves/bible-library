<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_flow_execution_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('execution_id')->constrained('whatsapp_flow_executions')->cascadeOnDelete();
            $table->foreignId('step_id')->nullable()->constrained('whatsapp_flow_steps')->nullOnDelete();
            $table->unsignedInteger('step_order');
            $table->string('step_type', 20);
            $table->string('status');
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->text('error_message')->nullable();
            $table->json('evolution_response')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('execution_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_flow_execution_logs');
    }
};
