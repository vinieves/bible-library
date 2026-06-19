<?php

namespace App\Enums;

enum WhatsAppFlowTriggerType: string
{
    case Manual = 'manual';
    case Webhook = 'webhook';
    case Scheduled = 'scheduled';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Webhook => 'Webhook (Hotmart)',
            self::Scheduled => 'Agendado',
        };
    }
}
