<?php

namespace App\Enums;

enum WhatsAppFlowStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Active => 'Ativo',
            self::Inactive => 'Inativo',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'warning',
            self::Active => 'success',
            self::Inactive => 'danger',
        };
    }
}
