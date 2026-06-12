<?php

use App\Enums\WhatsAppMessageEvent;
use App\Models\WhatsAppMessageTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach (WhatsAppMessageEvent::cases() as $event) {
            WhatsAppMessageTemplate::query()->firstOrCreate(
                ['event' => $event->value],
                [
                    'body' => $event->defaultBody(),
                    'is_enabled' => $event->defaultEnabled(),
                    'sort_order' => array_search($event, WhatsAppMessageEvent::cases(), true) + 1,
                ]
            );
        }
    }

    public function down(): void
    {
        //
    }
};
