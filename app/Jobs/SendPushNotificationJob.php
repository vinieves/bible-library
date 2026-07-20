<?php

namespace App\Jobs;

use App\Models\PushNotification;
use App\Models\PushSubscription;
use App\Services\WebPushService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendPushNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    /**
     * @param  array<int, int>  $subscriptionIds
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public array $subscriptionIds,
        public array $payload,
        public ?int $notificationId = null,
    ) {}

    public function handle(WebPushService $webPush): void
    {
        $subscriptions = PushSubscription::query()
            ->whereIn('id', $this->subscriptionIds)
            ->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $result = $webPush->sendMany($subscriptions, $this->payload);

        if ($this->notificationId !== null) {
            PushNotification::query()
                ->whereKey($this->notificationId)
                ->update([
                    'sent_count' => DB::raw('sent_count + '.(int) $result['sent']),
                    'failed_count' => DB::raw('failed_count + '.(int) $result['failed']),
                ]);
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Falha definitiva ao enviar push notification.', [
            'notification_id' => $this->notificationId,
            'subscriptions' => count($this->subscriptionIds),
            'error' => $exception?->getMessage(),
        ]);
    }
}
