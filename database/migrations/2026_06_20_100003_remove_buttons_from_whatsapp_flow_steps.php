<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('whatsapp_flow_steps', 'buttons')) {
            DB::table('whatsapp_flow_steps')
                ->where('type', 'buttons')
                ->update(['type' => 'text']);

            Schema::table('whatsapp_flow_steps', function (Blueprint $table) {
                $table->dropColumn('buttons');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('whatsapp_flow_steps', 'buttons')) {
            Schema::table('whatsapp_flow_steps', function (Blueprint $table) {
                $table->json('buttons')->nullable()->after('media_url');
            });
        }
    }
};
