<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_dispatch_logs', function (Blueprint $table) {
            $table->string('message_event')->nullable()->after('trigger');
            $table->string('hotmart_transaction')->nullable()->after('purchase_id');

            $table->index(['purchase_id', 'message_event', 'status']);
            $table->index(['hotmart_transaction', 'message_event', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_dispatch_logs', function (Blueprint $table) {
            $table->dropIndex(['purchase_id', 'message_event', 'status']);
            $table->dropIndex(['hotmart_transaction', 'message_event', 'status']);
            $table->dropColumn(['message_event', 'hotmart_transaction']);
        });
    }
};
