<?php

namespace App\Services;

use App\Jobs\SendPushNotificationJob;
use App\Models\PushNotification;
use App\Models\PushSubscription;
use App\Models\User;

/**
 * Orquestra o disparo de uma PushNotification: resolve o público (todos os
 * inscritos), enfileira o envio em lotes e atualiza o status/contadores.
 */
class PushNotificationService
{
    /** Tamanho do lote de subscriptions por Job. */
    private const CHUNK_SIZE = 200;

    public function __construct(private readonly WebPushService $webPush) {}

    /**
     * Enfileira o envio de uma notificação para todos os inscritos.
     */
    public function dispatch(PushNotification $notification): void
    {
        $payload = $notification->toPushPayload();

        PushSubscription::query()
            ->select('id')
            ->orderBy('id')
            ->chunk(self::CHUNK_SIZE, function ($subscriptions) use ($payload, $notification): void {
                SendPushNotificationJob::dispatch(
                    subscriptionIds: $subscriptions->pluck('id')->all(),
                    payload: $payload,
                    notificationId: $notification->id,
                );
            });

        // Marca a ocorrência como disparada (once → sent; recurring mantém scheduled).
        $notification->markDispatched();
    }

    /**
     * Envio de teste síncrono para as subscriptions de um usuário específico.
     *
     * @param  array<string, mixed>  $payload
     * @return array{sent: int, failed: int}
     */
    public function sendTestToUser(User $user, array $payload): array
    {
        $subscriptions = $user->pushSubscriptions()->get();

        return $this->webPush->sendMany($subscriptions, $payload);
    }
}
