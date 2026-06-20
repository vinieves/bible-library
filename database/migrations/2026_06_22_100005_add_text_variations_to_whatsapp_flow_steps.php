<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_flow_steps', function (Blueprint $table) {
            $table->text('content_variation_2')->nullable()->after('content');
            $table->text('content_variation_3')->nullable()->after('content_variation_2');
            $table->unsignedInteger('text_variation_cursor')->default(0)->after('content_variation_3');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_flow_steps', function (Blueprint $table) {
            $table->dropColumn([
                'content_variation_2',
                'content_variation_3',
                'text_variation_cursor',
            ]);
        });
    }
};
