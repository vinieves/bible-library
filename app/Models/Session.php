<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Read-only model over the framework `sessions` table (SESSION_DRIVER=database).
 * Used only to derive online presence — never written to by the app.
 */
class Session extends Model
{
    protected $table = 'sessions';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'last_activity' => 'integer',
        ];
    }

    /**
     * Sessions belonging to a logged-in user active within the last few minutes.
     */
    public function scopeOnline(Builder $query, int $minutes = 5): Builder
    {
        return $query
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', now()->subMinutes($minutes)->getTimestamp());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
