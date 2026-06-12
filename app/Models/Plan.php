<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'level',
        'is_admin',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'is_admin' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_plans')
            ->withPivot(['granted_at', 'expires_at', 'granted_by'])
            ->withTimestamps();
    }

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
