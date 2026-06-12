<?php

namespace App\Enums;

enum WebhookPlatform: string
{
    case Hotmart = 'hotmart';
    case Generic = 'generic';

    public function label(): string
    {
        return match ($this) {
            self::Hotmart => 'Hotmart',
            self::Generic => 'Genérico',
        };
    }

    public static function tryFromRoute(?string $platform): ?self
    {
        return self::tryFrom(strtolower((string) $platform));
    }
}
