<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_flow_executions', function (Blueprint $table) {
            $table->foreignId('waiting_step_id')
                ->nullable()
                ->after('current_step')
                ->constrained('whatsapp_flow_steps')
                ->nullOnDelete();
            $table->timestamp('waiting_since')->nullable()->after('waiting_step_id');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_flow_executions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('waiting_step_id');
            $table->dropColumn('waiting_since');
        });
    }
};
