<?php

namespace App\Models;

use App\Enums\AudioTrackStatus;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AudioTrack extends Model
{
    protected $fillable = [
        'audio_category_id',
        'title',
        'slug',
        'description',
        'cover_image',
        'audio_file',
        'duration',
        'is_free',
        'is_premium',
        'required_plan_id',
        'external_checkout_url',
        'order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_free' => 'boolean',
            'is_premium' => 'boolean',
            'order' => 'integer',
            'status' => AudioTrackStatus::class,
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AudioCategory::class, 'audio_category_id');
    }

    public function requiredPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'required_plan_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(UserAudioProgress::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', AudioTrackStatus::Published);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function isPublished(): bool
    {
        return $this->status === AudioTrackStatus::Published;
    }

    public function hasAudioFile(): bool
    {
        return $this->audio_file && Storage::disk('private')->exists($this->audio_file);
    }

    public function coverUrl(): ?string
    {
        if (! $this->cover_image) {
            return null;
        }

        $disk = Storage::disk('public');

        if ($disk->exists($this->cover_image)) {
            return $disk->url($this->cover_image);
        }

        foreach (['jpg', 'jpeg', 'png', 'webp'] as $extension) {
            $path = $this->cover_image.'.'.$extension;

            if ($disk->exists($path)) {
                return $disk->url($path);
            }
        }

        return null;
    }

    public function durationSeconds(): ?int
    {
        if (! $this->duration || ! str_contains($this->duration, ':')) {
            return null;
        }

        $parts = array_map('intval', explode(':', $this->duration));

        if (count($parts) === 2) {
            return ($parts[0] * 60) + $parts[1];
        }

        if (count($parts) === 3) {
            return ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
        }

        return null;
    }

    public function checkoutUrl(): string
    {
        if (filled($this->external_checkout_url)) {
            return $this->external_checkout_url;
        }

        $url = Setting::get('audio_subscription_checkout_url', '#');

        return filled($url) ? $url : '#';
    }

    public function accessLabel(): string
    {
        if ($this->is_free) {
            return 'Gratuito';
        }

        return 'Premium';
    }

    protected static function booted(): void
    {
        static::creating(function (AudioTrack $track) {
            if (empty($track->slug)) {
                $track->slug = Str::slug($track->title);
            }
        });
    }
}
