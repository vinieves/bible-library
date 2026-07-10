<?php

namespace App\Enums;

enum EmailDispatchTrigger: string
{
    case ManualTest = 'manual_test';
    case PurchaseWebhook = 'purchase_webhook';

    public function label(): string
    {
        return match ($this) {
            self::ManualTest => 'Teste manual',
            self::PurchaseWebhook => 'Compra (webhook)',
        };
    }
}
