<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'is_admin'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'user_plans')
            ->withPivot(['granted_at', 'expires_at', 'granted_by'])
            ->withTimestamps();
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function materialProgress(): HasMany
    {
        return $this->hasMany(UserMaterialProgress::class);
    }

    public function audioProgress(): HasMany
    {
        return $this->hasMany(UserAudioProgress::class);
    }

    public function hasPlan(string|Plan $plan): bool
    {
        if ($this->is_admin) {
            return true;
        }

        $slug = $plan instanceof Plan ? $plan->slug : $plan;

        return $this->plans()
            ->where('slug', $slug)
            ->where(function ($query) {
                $query->whereNull('user_plans.expires_at')
                    ->orWhere('user_plans.expires_at', '>', now());
            })
            ->exists();
    }

    public function hasAccessToMaterial(Material $material): bool
    {
        if ($this->is_admin) {
            return true;
        }

        $requiredPlan = $material->plan;

        if (! $requiredPlan) {
            return false;
        }

        $userMaxLevel = $this->plans()
            ->where(function ($query) {
                $query->whereNull('user_plans.expires_at')
                    ->orWhere('user_plans.expires_at', '>', now());
            })
            ->max('level');

        return $userMaxLevel !== null && $userMaxLevel >= $requiredPlan->level;
    }

    public function hasAccessToAudioTrack(AudioTrack $track): bool
    {
        if ($this->is_admin) {
            return true;
        }

        if ($track->is_free) {
            return true;
        }

        if (! $track->is_premium) {
            return true;
        }

        $requiredPlan = $track->requiredPlan;

        if (! $requiredPlan) {
            return false;
        }

        $userMaxLevel = $this->plans()
            ->where(function ($query) {
                $query->whereNull('user_plans.expires_at')
                    ->orWhere('user_plans.expires_at', '>', now());
            })
            ->max('level');

        return $userMaxLevel !== null && $userMaxLevel >= $requiredPlan->level;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }

    public function studiedMaterialsCount(): int
    {
        return $this->materialProgress()->where('is_studied', true)->count();
    }
}
