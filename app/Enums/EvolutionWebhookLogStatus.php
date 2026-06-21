<?php

namespace App\Enums;

enum EvolutionWebhookLogStatus: string
{
    case Received = 'received';
    case Parsed = 'parsed';
    case Ignored = 'ignored';
    case Unauthorized = 'unauthorized';

    public function label(): string
    {
        return match ($this) {
            self::Received => 'Recebido',
            self::Parsed => 'Mensagem válida',
            self::Ignored => 'Ignorado',
            self::Unauthorized => 'Não autorizado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Received => 'info',
            self::Parsed => 'success',
            self::Ignored => 'warning',
            self::Unauthorized => 'danger',
        };
    }
}
