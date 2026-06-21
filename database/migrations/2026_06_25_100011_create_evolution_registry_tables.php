<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evolution_registry_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('phone_normalized', 32);
            $table->string('instance_name');
            $table->string('contact_name')->nullable();
            $table->string('remote_jid')->nullable();
            $table->unsignedInteger('events_count')->default(0);
            $table->unsignedInteger('inbound_count')->default(0);
            $table->unsignedInteger('outbound_count')->default(0);
            $table->unsignedInteger('flow_executions_count')->default(0);
            $table->boolean('has_inbound_contact')->default(false);
            $table->timestamp('first_seen_at');
            $table->timestamp('last_event_at');
            $table->timestamp('last_inbound_at')->nullable();
            $table->timestamp('last_outbound_at')->nullable();
            $table->string('last_message_preview', 500)->nullable();
            $table->timestamps();

            $table->unique(['phone_normalized', 'instance_name'], 'evolution_registry_contact_phone_instance');
            $table->index('instance_name');
            $table->index('last_event_at');
            $table->index('contact_name');
        });

        Schema::create('evolution_registry_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registry_contact_id')
                ->nullable()
                ->constrained('evolution_registry_contacts')
                ->cascadeOnDelete();
            $table->foreignId('evolution_webhook_log_id')
                ->nullable()
                ->constrained('evolution_webhook_logs')
                ->nullOnDelete();
            $table->string('event');
            $table->string('instance_name')->nullable();
            $table->string('phone_normalized', 32)->nullable();
            $table->string('remote_jid')->nullable();
            $table->string('direction');
            $table->string('contact_name')->nullable();
            $table->string('summary', 500);
            $table->string('message_preview', 500)->nullable();
            $table->boolean('from_me')->nullable();
            $table->string('route_slug')->nullable();
            $table->boolean('flow_triggered')->default(false);
            $table->timestamp('occurred_at');
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index('event');
            $table->index('instance_name');
            $table->index('phone_normalized');
            $table->index('direction');
            $table->index('occurred_at');
            $table->index(['registry_contact_id', 'occurred_at'], 'evolution_registry_events_contact_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evolution_registry_events');
        Schema::dropIfExists('evolution_registry_contacts');
    }
};
