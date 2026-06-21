<?php

namespace App\Enums;

enum EvolutionRegistryEventDirection: string
{
    case Inbound = 'inbound';
    case Outbound = 'outbound';
    case System = 'system';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Inbound => 'Recebida',
            self::Outbound => 'Enviada',
            self::System => 'Instância',
            self::Unknown => 'Outro',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Inbound => 'success',
            self::Outbound => 'info',
            self::System => 'gray',
            self::Unknown => 'warning',
        };
    }
}
