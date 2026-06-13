<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserVideoProgress extends Model
{
    protected $table = 'user_video_progress';

    protected $fillable = [
        'user_id',
        'video_id',
        'progress_seconds',
        'completed',
        'last_played_at',
    ];

    protected function casts(): array
    {
        return [
            'progress_seconds' => 'integer',
            'completed' => 'boolean',
            'last_played_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function completionPercent(?Video $video = null): int
    {
        if ($this->completed) {
            return 100;
        }

        $video ??= $this->video;
        $total = $video?->durationSeconds();

        if ($total && $total > 0 && $this->progress_seconds > 0) {
            return min(99, (int) round(($this->progress_seconds / $total) * 100));
        }

        return 0;
    }

    public function statusLabel(?Video $video = null): string
    {
        if ($this->completed) {
            return 'Visto';
        }

        $video ??= $this->video;
        $total = $video?->durationSeconds();

        if ($total && $this->progress_seconds > 0) {
            return 'En progreso';
        }

        return 'Sin iniciar';
    }
}
