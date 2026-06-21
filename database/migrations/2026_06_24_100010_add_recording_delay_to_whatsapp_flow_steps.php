<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_flow_steps', function (Blueprint $table) {
            $table->unsignedInteger('recording_delay')->default(0)->after('typing_delay');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_flow_steps', function (Blueprint $table) {
            $table->dropColumn('recording_delay');
        });
    }
};
