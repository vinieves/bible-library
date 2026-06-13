<?php

namespace App\Enums;

enum CategoryBadgeColor: string
{
    case Gold = 'gold';
    case Green = 'green';
    case Emerald = 'emerald';
    case Blue = 'blue';
    case Purple = 'purple';
    case Rose = 'rose';
    case Amber = 'amber';

    public function label(): string
    {
        return match ($this) {
            self::Gold => 'Dourado',
            self::Green => 'Verde',
            self::Emerald => 'Esmeralda',
            self::Blue => 'Azul',
            self::Purple => 'Roxo',
            self::Rose => 'Rosa',
            self::Amber => 'Âmbar',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Gold => 'bg-bible-gold/10 text-bible-gold',
            self::Green => 'bg-bible-green/20 text-green-300',
            self::Emerald => 'bg-emerald-900/35 text-emerald-300',
            self::Blue => 'bg-blue-900/35 text-blue-300',
            self::Purple => 'bg-purple-900/35 text-purple-300',
            self::Rose => 'bg-rose-900/35 text-rose-300',
            self::Amber => 'bg-amber-900/35 text-amber-300',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $color) => [$color->value => $color->label()])
            ->all();
    }
}
