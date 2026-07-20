<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('url')->nullable();
            $table->string('icon')->nullable();

            // now | once | recurring
            $table->string('schedule_type')->default('now');
            $table->timestamp('scheduled_at')->nullable();

            // daily | weekly
            $table->string('recurrence_frequency')->nullable();
            $table->time('recurrence_time')->nullable();
            $table->json('recurrence_days')->nullable();

            // scheduled | sending | sent | failed | canceled
            $table->string('status')->default('scheduled');
            $table->timestamp('last_sent_at')->nullable();
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('schedule_type');
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_notifications');
    }
};
