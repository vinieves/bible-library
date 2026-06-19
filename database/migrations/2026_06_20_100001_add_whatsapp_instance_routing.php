<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_flows', function (Blueprint $table) {
            $table->string('instance_name')->nullable()->after('is_active');
            $table->index('instance_name');
        });

        Schema::table('whatsapp_dispatch_logs', function (Blueprint $table) {
            $table->string('instance_name')->nullable()->after('message_event');
            $table->index('instance_name');
        });

        Schema::table('whatsapp_flow_executions', function (Blueprint $table) {
            $table->string('instance_name')->nullable()->after('trigger');
            $table->index('instance_name');
        });

        $legacyInstance = Setting::get('evolution_instance');

        if (filled($legacyInstance)) {
            if (blank(Setting::get('evolution_instance_messages'))) {
                Setting::set('evolution_instance_messages', $legacyInstance);
            }

            if (blank(Setting::get('evolution_instance_flows'))) {
                Setting::set('evolution_instance_flows', $legacyInstance);
            }
        }
    }

    public function down(): void
    {
        Schema::table('whatsapp_flow_executions', function (Blueprint $table) {
            $table->dropIndex(['instance_name']);
            $table->dropColumn('instance_name');
        });

        Schema::table('whatsapp_dispatch_logs', function (Blueprint $table) {
            $table->dropIndex(['instance_name']);
            $table->dropColumn('instance_name');
        });

        Schema::table('whatsapp_flows', function (Blueprint $table) {
            $table->dropIndex(['instance_name']);
            $table->dropColumn('instance_name');
        });
    }
};
