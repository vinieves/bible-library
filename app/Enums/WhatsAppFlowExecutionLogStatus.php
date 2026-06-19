<?php

namespace App\Enums;

enum WhatsAppFlowExecutionLogStatus: string
{
    case Sent = 'sent';
    case Failed = 'failed';
    case Skipped = 'skipped';

    public function label(): string
    {
        return match ($this) {
            self::Sent => 'Enviado',
            self::Failed => 'Falhou',
            self::Skipped => 'Ignorado',
        };
    }
}
