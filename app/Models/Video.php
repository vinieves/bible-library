<?php

namespace App\Models;

use App\Enums\VideoStatus;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Video extends Model
{
    protected $fillable = [
        'video_category_id',
        'title',
        'slug',
        'description',
        'cover_image',
        'video_file',
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
            'status' => VideoStatus::class,
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(VideoCategory::class, 'video_category_id');
    }

    public function requiredPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'required_plan_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(UserVideoProgress::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', VideoStatus::Published);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function isPublished(): bool
    {
        return $this->status === VideoStatus::Published;
    }

    public function hasVideoFile(): bool
    {
        return $this->video_file && Storage::disk('private')->exists($this->video_file);
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

    public function streamMimeType(): string
    {
        $extension = strtolower(pathinfo((string) $this->video_file, PATHINFO_EXTENSION));

        return match ($extension) {
            'webm' => 'video/webm',
            'mov' => 'video/quicktime',
            'm4v' => 'video/x-m4v',
            default => 'video/mp4',
        };
    }

    public function streamFilename(): string
    {
        $extension = strtolower(pathinfo((string) $this->video_file, PATHINFO_EXTENSION));

        return $this->slug.'.'.($extension ?: 'mp4');
    }

    protected static function booted(): void
    {
        static::creating(function (Video $video) {
            if (empty($video->slug)) {
                $video->slug = Str::slug($video->title);
            }
        });
    }
}
