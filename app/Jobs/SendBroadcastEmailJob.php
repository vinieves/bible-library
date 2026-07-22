<?php

namespace App\Jobs;

use App\Enums\EmailDispatchTrigger;
use App\Models\EmailBroadcast;
use App\Services\EmailDispatchLogService;
use App\Services\TransactionalMailService;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendBroadcastEmailJob implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public int $broadcastId,
        public ?int $userId,
        public string $recipientEmail,
        public string $recipientName,
        public bool $isTest = false,
    ) {}

    public function handle(
        TransactionalMailService $mailer,
        EmailDispatchLogService $dispatchLog,
    ): void {
        // Numa campanha real, se o lote foi cancelado, aborta silenciosamente.
        if ($this->batch()?->cancelled()) {
            return;
        }

        $broadcast = EmailBroadcast::query()->find($this->broadcastId);

        if (! $broadcast) {
            return;
        }

        $subject = $this->renderPlaceholders($broadcast->subject);
        $bodyHtml = $this->renderPlaceholders($broadcast->body);
        $attempt = $this->attempts();

        if (blank($this->recipientEmail) || ! filter_var($this->recipientEmail, FILTER_VALIDATE_EMAIL)) {
            $this->recordFailure($dispatchLog, $subject, $bodyHtml, 'Endereço de e-mail inválido ou vazio.', $attempt);
            $this->incrementFailed();

            return;
        }

        if (! $mailer->isConfigured()) {
            $this->recordFailure($dispatchLog, $subject, $bodyHtml, 'SMTP não configurado.', $attempt);
            $this->incrementFailed();

            return;
        }

        try {
            $result = $mailer->send(
                $this->recipientEmail,
                $subject,
                $bodyHtml,
                array_values($broadcast->attachments ?? []),
            );

            $dispatchLog->recordSuccess(
                trigger: EmailDispatchTrigger::Broadcast,
                messageEvent: null,
                recipientEmail: $this->recipientEmail,
                userId: $this->userId,
                purchaseId: null,
                hotmartTransaction: null,
                subject: $subject,
                body: $bodyHtml,
                mailerResponse: $result['response'],
                attempt: $attempt,
            );

            $this->incrementSent();
        } catch (Throwable $exception) {
            $dispatchLog->recordThrowable(
                trigger: EmailDispatchTrigger::Broadcast,
                messageEvent: null,
                recipientEmail: $this->recipientEmail,
                userId: $this->userId,
                purchaseId: null,
                hotmartTransaction: null,
                subject: $subject,
                body: $bodyHtml,
                exception: $exception,
                attempt: $attempt,
            );

            throw $exception;
        }
    }

    private function renderPlaceholders(string $text): string
    {
        return str_replace(
            ['{nome}', '{email}', '{link_acceso}'],
            [$this->recipientName ?: 'Cliente', $this->recipientEmail, route('login')],
            $text,
        );
    }

    private function recordFailure(
        EmailDispatchLogService $dispatchLog,
        string $subject,
        string $body,
        string $error,
        int $attempt,
    ): void {
        $dispatchLog->recordFailure(
            trigger: EmailDispatchTrigger::Broadcast,
            messageEvent: null,
            recipientEmail: $this->recipientEmail,
            userId: $this->userId,
            purchaseId: null,
            hotmartTransaction: null,
            subject: $subject,
            body: $body,
            errorMessage: $error,
            attempt: $attempt,
        );
    }

    private function incrementSent(): void
    {
        if ($this->isTest) {
            return;
        }

        EmailBroadcast::query()->whereKey($this->broadcastId)->increment('sent_count');
    }

    private function incrementFailed(): void
    {
        if ($this->isTest) {
            return;
        }

        EmailBroadcast::query()->whereKey($this->broadcastId)->increment('failed_count');
    }

    public function failed(?Throwable $exception): void
    {
        $this->incrementFailed();

        Log::error('Falha definitiva ao enviar e-mail de campanha.', [
            'broadcast_id' => $this->broadcastId,
            'email' => $this->recipientEmail,
            'error' => $exception?->getMessage(),
        ]);
    }
}
