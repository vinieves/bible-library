<?php

namespace App\Enums;

enum MaterialType: string
{
    case Libro = 'libro';
    case Bonus = 'bonus';
    case MapaMental = 'mapa_mental';
    case Devocional = 'devocional';
    case EstudioPremium = 'estudio_premium';

    public function label(): string
    {
        return match ($this) {
            self::Libro => 'Livro',
            self::Bonus => 'Bônus',
            self::MapaMental => 'Mapa mental',
            self::Devocional => 'Devocional',
            self::EstudioPremium => 'Estudo premium',
        };
    }
}
