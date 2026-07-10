<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_dispatch_logs', function (Blueprint $table) {
            $table->id();
            $table->string('trigger');
            $table->string('message_event')->nullable();
            $table->string('status');
            $table->string('from_address')->nullable();
            $table->string('recipient_email');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('purchase_id')->nullable()->constrained()->nullOnDelete();
            $table->string('hotmart_transaction')->nullable();
            $table->string('subject')->nullable();
            $table->text('body')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedTinyInteger('attempt')->default(1);
            $table->json('mailer_response')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('trigger');
            $table->index('status');
            $table->index('recipient_email');
            $table->index('created_at');
            $table->index(['purchase_id', 'message_event', 'status']);
            $table->index(['hotmart_transaction', 'message_event', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_dispatch_logs');
    }
};
