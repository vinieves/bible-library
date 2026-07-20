<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'endpoint',
        'endpoint_hash',
        'p256dh',
        'auth',
        'user_agent',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function hashEndpoint(string $endpoint): string
    {
        return hash('sha256', $endpoint);
    }

    /**
     * Cria/atualiza a subscription a partir do JSON enviado pelo navegador
     * (PushSubscription.toJSON()).
     *
     * @param  array<string, mixed>  $payload
     */
    public static function storeFromBrowser(array $payload, ?int $userId, ?string $userAgent): self
    {
        $endpoint = (string) ($payload['endpoint'] ?? '');
        $keys = $payload['keys'] ?? [];

        return static::updateOrCreate(
            ['endpoint_hash' => static::hashEndpoint($endpoint)],
            [
                'user_id' => $userId,
                'endpoint' => $endpoint,
                'p256dh' => (string) ($keys['p256dh'] ?? ''),
                'auth' => (string) ($keys['auth'] ?? ''),
                'user_agent' => $userAgent,
            ],
        );
    }
}
