<?php

namespace App\Enums;

enum EmailDispatchStatus: string
{
    case Sent = 'sent';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Sent => 'Enviado',
            self::Failed => 'Falhou',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Sent => 'success',
            self::Failed => 'danger',
        };
    }
}
