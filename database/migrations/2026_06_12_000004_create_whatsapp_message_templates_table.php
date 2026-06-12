<?php

use App\Enums\WhatsAppMessageEvent;
use App\Models\Setting;
use App\Models\WhatsAppMessageTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_message_templates', function (Blueprint $table) {
            $table->id();
            $table->string('event')->unique();
            $table->text('body');
            $table->boolean('is_enabled')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $legacyWelcome = Setting::get('whatsapp_welcome_template');

        foreach (WhatsAppMessageEvent::cases() as $index => $event) {
            $body = $event === WhatsAppMessageEvent::PurchaseApproved && filled($legacyWelcome)
                ? (string) $legacyWelcome
                : $event->defaultBody();

            WhatsAppMessageTemplate::query()->create([
                'event' => $event->value,
                'body' => $body,
                'is_enabled' => $event->defaultEnabled(),
                'sort_order' => $index + 1,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_message_templates');
    }
};
