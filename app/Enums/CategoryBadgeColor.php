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
        return 'badge-tone-'.$this->value;
    }

    public function iconThumbClasses(): string
    {
        return 'icon-thumb-'.$this->value;
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
