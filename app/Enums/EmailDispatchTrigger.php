<?php

namespace App\Enums;

enum EmailDispatchTrigger: string
{
    case ManualTest = 'manual_test';
    case PurchaseWebhook = 'purchase_webhook';
    case Broadcast = 'broadcast';

    public function label(): string
    {
        return match ($this) {
            self::ManualTest => 'Teste manual',
            self::PurchaseWebhook => 'Compra (webhook)',
            self::Broadcast => 'Disparo em massa',
        };
    }
}
