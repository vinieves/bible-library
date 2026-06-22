<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_message_triggers', function (Blueprint $table) {
            $table->id();
            $table->string('public_code', 20)->unique();
            $table->string('name');
            $table->text('message');
            $table->text('message_normalized');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('message_normalized');
            $table->index('is_active');
        });

        Schema::table('whatsapp_flows', function (Blueprint $table) {
            $table->foreignId('message_trigger_id')
                ->nullable()
                ->after('trigger_event')
                ->constrained('whatsapp_message_triggers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_flows', function (Blueprint $table) {
            $table->dropConstrainedForeignId('message_trigger_id');
        });

        Schema::dropIfExists('whatsapp_message_triggers');
    }
};
