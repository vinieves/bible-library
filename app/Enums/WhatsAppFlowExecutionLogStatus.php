<?php

namespace App\Enums;

enum WhatsAppFlowExecutionLogStatus: string
{
    case Sent = 'sent';
    case Failed = 'failed';
    case Skipped = 'skipped';
    case Waiting = 'waiting';
    case Received = 'received';

    public function label(): string
    {
        return match ($this) {
            self::Sent => 'Enviado',
            self::Failed => 'Falhou',
            self::Skipped => 'Ignorado',
            self::Waiting => 'Aguardando',
            self::Received => 'Resposta recebida',
        };
    }
}
