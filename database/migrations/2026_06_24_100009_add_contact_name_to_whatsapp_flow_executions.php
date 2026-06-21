<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_flow_executions', function (Blueprint $table) {
            $table->string('contact_name')->nullable()->after('phone_normalized');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_flow_executions', function (Blueprint $table) {
            $table->dropColumn('contact_name');
        });
    }
};
