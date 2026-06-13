<?php

namespace App\Models\Concerns;

use App\Enums\CategoryBadgeColor;

trait InteractsWithCategoryBadgeColor
{
    public function resolvedBadgeColor(): CategoryBadgeColor
    {
        $stored = CategoryBadgeColor::tryFrom((string) ($this->badge_color ?? ''));

        if ($stored) {
            return $stored;
        }

        $colors = CategoryBadgeColor::cases();
        $key = (string) ($this->slug ?? $this->name ?? $this->id ?? 'default');
        $index = abs(crc32($key)) % count($colors);

        return $colors[$index];
    }

    public function badgeColorClasses(): string
    {
        return $this->resolvedBadgeColor()->badgeClasses();
    }

    public function iconThumbClasses(): string
    {
        return $this->resolvedBadgeColor()->iconThumbClasses();
    }
}
