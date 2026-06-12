<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAudioProgress extends Model
{
    protected $table = 'user_audio_progress';

    protected $fillable = [
        'user_id',
        'audio_track_id',
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

    public function audioTrack(): BelongsTo
    {
        return $this->belongsTo(AudioTrack::class);
    }

    public function completionPercent(?AudioTrack $track = null): int
    {
        if ($this->completed) {
            return 100;
        }

        $track ??= $this->audioTrack;
        $total = $track?->durationSeconds();

        if ($total && $total > 0 && $this->progress_seconds > 0) {
            return min(99, (int) round(($this->progress_seconds / $total) * 100));
        }

        return 0;
    }

    public function statusLabel(?AudioTrack $track = null): string
    {
        if ($this->completed) {
            return 'Escuchado';
        }

        $track ??= $this->audioTrack;
        $total = $track?->durationSeconds();

        if ($total && $this->progress_seconds > 0) {
            return 'En progreso';
        }

        return 'Sin iniciar';
    }
}
