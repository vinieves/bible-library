<?php

namespace App\Services;

use App\Enums\EmailBroadcastStatus;
use App\Jobs\SendBroadcastEmailJob;
use App\Models\EmailBroadcast;
use App\Models\User;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Throwable;

class EmailBroadcastDispatcher
{
    /**
     * Quantos e-mails despachar por minuto (escalonamento p/ respeitar o limite do SMTP).
     */
    private const PER_MINUTE = 60;

    public function __construct(
        private readonly EmailBroadcastAudienceService $audience,
    ) {}

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

        $this->audience->query($broadcast)
            ->select(['id', 'name', 'email'])
            ->chunkById(500, function ($users) use (&$jobs, &$index, $broadcast) {
                foreach ($users as $user) {
                    $delaySeconds = intdiv($index, self::PER_MINUTE) * 60;

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
