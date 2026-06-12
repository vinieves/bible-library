<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PURCHASE_EVENT_INDEX = 'wa_dl_purchase_evt_status_idx';

    private const TRANSACTION_EVENT_INDEX = 'wa_dl_tx_evt_status_idx';

    public function up(): void
    {
        Schema::table('whatsapp_dispatch_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('whatsapp_dispatch_logs', 'message_event')) {
                $table->string('message_event')->nullable()->after('trigger');
            }

            if (! Schema::hasColumn('whatsapp_dispatch_logs', 'hotmart_transaction')) {
                $table->string('hotmart_transaction')->nullable()->after('purchase_id');
            }
        });

        Schema::table('whatsapp_dispatch_logs', function (Blueprint $table) {
            if (! Schema::hasIndex('whatsapp_dispatch_logs', self::PURCHASE_EVENT_INDEX)) {
                $table->index(['purchase_id', 'message_event', 'status'], self::PURCHASE_EVENT_INDEX);
            }

            if (! Schema::hasIndex('whatsapp_dispatch_logs', self::TRANSACTION_EVENT_INDEX)) {
                $table->index(['hotmart_transaction', 'message_event', 'status'], self::TRANSACTION_EVENT_INDEX);
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_dispatch_logs', function (Blueprint $table) {
            if (Schema::hasIndex('whatsapp_dispatch_logs', self::PURCHASE_EVENT_INDEX)) {
                $table->dropIndex(self::PURCHASE_EVENT_INDEX);
            }

            if (Schema::hasIndex('whatsapp_dispatch_logs', self::TRANSACTION_EVENT_INDEX)) {
                $table->dropIndex(self::TRANSACTION_EVENT_INDEX);
            }

            if (Schema::hasColumn('whatsapp_dispatch_logs', 'message_event')) {
                $table->dropColumn('message_event');
            }

            if (Schema::hasColumn('whatsapp_dispatch_logs', 'hotmart_transaction')) {
                $table->dropColumn('hotmart_transaction');
            }
        });
    }
};
