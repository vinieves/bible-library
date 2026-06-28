<?php

namespace App\Models;

use App\Enums\ForumPostStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ForumPost extends Model
{
    protected $fillable = [
        'forum_persona_id',
        'title',
        'body',
        'images',
        'youtube_url',
        'audio_file',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => ForumPostStatus::class,
            'images' => 'array',
        ];
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(ForumPersona::class, 'forum_persona_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(ForumPostReaction::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ForumPostStatus::Published);
    }

    public function isPublished(): bool
    {
        return $this->status === ForumPostStatus::Published;
    }

    public function hasAudioFile(): bool
    {
        return $this->audio_file && Storage::disk('private')->exists($this->audio_file);
    }

    public function imageUrls(): array
    {
        return collect($this->images ?? [])
            ->map(fn (string $path) => Storage::disk('public')->url($path))
            ->all();
    }

    public function youtubeEmbedUrl(): ?string
    {
        if (! $this->youtube_url) {
            return null;
        }

        $pattern = '/(?:youtube(?:-nocookie)?\.com\/(?:watch\?v=|embed\/|shorts\/|v\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/i';

        if (! preg_match($pattern, $this->youtube_url, $matches)) {
            return null;
        }

        return 'https://www.youtube.com/embed/'.$matches[1];
    }
}
