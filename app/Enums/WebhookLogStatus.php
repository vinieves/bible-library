<?php

namespace App\Enums;

enum WebhookLogStatus: string
{
    case Unauthorized = 'unauthorized';
    case Ignored = 'ignored';
    case Processed = 'processed';
    case Duplicate = 'duplicate';
    case Error = 'error';

    public function label(): string
    {
        return match ($this) {
            self::Unauthorized => 'Não autorizado',
            self::Ignored => 'Ignorado',
            self::Processed => 'Processado',
            self::Duplicate => 'Duplicado',
            self::Error => 'Erro',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Unauthorized => 'danger',
            self::Ignored => 'gray',
            self::Processed => 'success',
            self::Duplicate => 'warning',
            self::Error => 'danger',
        };
    }
}
