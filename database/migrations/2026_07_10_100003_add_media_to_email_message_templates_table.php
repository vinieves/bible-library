<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_message_templates', function (Blueprint $table) {
            $table->json('inline_images')->nullable()->after('body');
            $table->json('attachments')->nullable()->after('inline_images');
        });
    }

    public function down(): void
    {
        Schema::table('email_message_templates', function (Blueprint $table) {
            $table->dropColumn(['inline_images', 'attachments']);
        });
    }
};
