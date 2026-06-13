<?php

use App\Enums\WhatsAppMessageEvent;
use App\Models\WhatsAppMessageTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (WhatsAppMessageTemplate::query()->where('event', WhatsAppMessageEvent::PurchaseProtest->value)->exists()) {
            return;
        }

        WhatsAppMessageTemplate::query()->create([
            'event' => WhatsAppMessageEvent::PurchaseProtest->value,
            'body' => WhatsAppMessageEvent::PurchaseProtest->defaultBody(),
            'is_enabled' => true,
            'sort_order' => 8,
        ]);
    }

    public function down(): void
    {
        WhatsAppMessageTemplate::query()
            ->where('event', WhatsAppMessageEvent::PurchaseProtest->value)
            ->delete();
    }
};
