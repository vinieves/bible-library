<?php

namespace App\Models;

use App\Enums\CategoryBadgeColor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AudioCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'badge_color',
        'description',
        'order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function tracks(): HasMany
    {
        return $this->hasMany(AudioTrack::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function badgeColorClasses(): string
    {
        return CategoryBadgeColor::tryFrom((string) ($this->badge_color ?? ''))?->badgeClasses()
            ?? CategoryBadgeColor::Gold->badgeClasses();
    }

    protected static function booted(): void
    {
        static::creating(function (AudioCategory $category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }
}
