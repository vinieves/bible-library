<?php

namespace App\Services;

use App\Enums\EmailBroadcastStatus;
use App\Jobs\SendBroadcastEmailJob;
use App\Models\EmailBroadcast;
use App\Models\User;
use App\Support\IntegrationSettings;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Throwable;

class EmailBroadcastDispatcher
{
    public function __construct(
        private readonly EmailBroadcastAudienceService $audience,
    ) {}

    /**
     * Segundos entre cada e-mail, conforme o ritmo seguro configurado no admin.
     */
    public function intervalSeconds(): float
    {
        return 60 / max(1, IntegrationSettings::broadcastRatePerMinute());
    }

    /**
     * Enfileira o disparo de uma campanha em rascunho. Retorna o total de destinatários.
     */
    public function dispatch(EmailBroadcast $broadcast): int
    {
        if (! $broadcast->isDraft()) {
            return 0;
        }

        $jobs = [];
        $index = 0;
        $interval = $this->intervalSeconds();
        $jitterMax = (int) ceil($interval * 0.4);

        $this->audience->query($broadcast)
            ->select(['id', 'name', 'email'])
            ->chunkById(500, function ($users) use (&$jobs, &$index, $broadcast, $interval, $jitterMax) {
                foreach ($users as $user) {
                    // Cada e-mail sai espaçado do anterior (um a um), com jitter para não parecer robótico.
                    $delaySeconds = (int) round($index * $interval) + random_int(0, $jitterMax);

                    $jobs[] = (new SendBroadcastEmailJob(
                        broadcastId: $broadcast->id,
                        userId: $user->id,
                        recipientEmail: $user->email,
                        recipientName: (string) $user->name,
                    ))->delay(now()->addSeconds($delaySeconds));

                    $index++;
                }
            });

        $total = count($jobs);

        if ($total === 0) {
            $broadcast->update([
                'status' => EmailBroadcastStatus::Sent,
                'total_recipients' => 0,
                'sent_at' => now(),
            ]);

            return 0;
        }

        $broadcast->update([
            'status' => EmailBroadcastStatus::Queued,
            'total_recipients' => $total,
            'sent_count' => 0,
            'failed_count' => 0,
        ]);

        $broadcastId = $broadcast->id;

        $batch = Bus::batch($jobs)
            ->name('email-broadcast:'.$broadcastId)
            ->allowFailures()
            ->finally(function (Batch $batch) use ($broadcastId) {
                $broadcast = EmailBroadcast::query()->find($broadcastId);

                if (! $broadcast) {
                    return;
                }

                // Se o admin cancelou o disparo, preserva o status Cancelado.
                if ($broadcast->status === EmailBroadcastStatus::Cancelled) {
                    return;
                }

                $broadcast->update([
                    'status' => $broadcast->failed_count > 0
                        ? ($broadcast->sent_count > 0 ? EmailBroadcastStatus::Partial : EmailBroadcastStatus::Failed)
                        : EmailBroadcastStatus::Sent,
                    'sent_at' => now(),
                ]);
            })
            ->dispatch();

        $broadcast->update(['batch_id' => $batch->id]);

        return $total;
    }

    /**
     * Envia um único e-mail de teste (não altera contadores da campanha).
     */
    public function sendTest(EmailBroadcast $broadcast, string $email, ?User $user = null): void
    {
        SendBroadcastEmailJob::dispatch(
            broadcastId: $broadcast->id,
            userId: $user?->id,
            recipientEmail: $email,
            recipientName: (string) ($user?->name ?? 'Cliente'),
            isTest: true,
        );
    }
}
