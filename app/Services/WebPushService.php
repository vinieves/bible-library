<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Transporte web push (VAPID) via minishlink/web-push. Responsável apenas por
 * entregar o payload aos endpoints e limpar subscriptions mortas (404/410).
 */
class WebPushService
{
    public function isConfigured(): bool
    {
        return filled(Setting::get('vapid_public_key'))
            && filled(Setting::getEncrypted('vapid_private_key'));
    }

    /**
     * Envia o payload a um conjunto de subscriptions.
     *
     * @param  iterable<PushSubscription>  $subscriptions
     * @param  array<string, mixed>  $payload
     * @return array{sent: int, failed: int}
     */
    public function sendMany(iterable $subscriptions, array $payload): array
    {
        if (! $this->isConfigured()) {
            Log::warning('WebPush: chaves VAPID não configuradas — envio ignorado.');

            return ['sent' => 0, 'failed' => 0];
        }

        $webPush = $this->newClient();
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        /** @var array<string, PushSubscription> $byEndpointHash */
        $byEndpointHash = [];
        $queued = 0;

        foreach ($subscriptions as $subscription) {
            $byEndpointHash[$subscription->endpoint_hash] = $subscription;

            $webPush->queueNotification(
                Subscription::create([
                    'endpoint' => $subscription->endpoint,
                    'keys' => [
                        'p256dh' => $subscription->p256dh,
                        'auth' => $subscription->auth,
                    ],
                    'contentEncoding' => 'aes128gcm',
                ]),
                $json,
            );

            $queued++;
        }

        if ($queued === 0) {
            return ['sent' => 0, 'failed' => 0];
        }

        $sent = 0;
        $failed = 0;

        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                $sent++;

                continue;
            }

            $failed++;

            // Endpoint expirado/inexistente: remover para não tentar de novo.
            if ($report->isSubscriptionExpired()) {
                $hash = PushSubscription::hashEndpoint($report->getEndpoint());
                $subscription = $byEndpointHash[$hash] ?? null;
                $subscription?->delete();

                continue;
            }

            Log::warning('WebPush: falha ao enviar', [
                'endpoint' => $report->getEndpoint(),
                'reason' => $report->getReason(),
            ]);
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    protected function newClient(): WebPush
    {
        return new WebPush([
            'VAPID' => [
                'subject' => Setting::get('vapid_subject') ?: config('app.url'),
                'publicKey' => Setting::get('vapid_public_key'),
                'privateKey' => Setting::getEncrypted('vapid_private_key'),
            ],
        ]);
    }
}
