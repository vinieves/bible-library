<?php

namespace App\Jobs;

use App\Enums\EmailDispatchStatus;
use App\Enums\EmailDispatchTrigger;
use App\Enums\WhatsAppMessageEvent;
use App\Models\EmailDispatchLog;
use App\Models\Purchase;
use App\Models\User;
use App\Services\EmailDispatchLogService;
use App\Services\EmailMessageTemplateService;
use App\Services\NormalizedPurchaseContext;
use App\Services\TransactionalMailService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendTransactionalEmailJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public int $userId,
        public string $recipientEmail,
        public int $purchaseId,
        public WhatsAppMessageEvent $messageEvent,
        public EmailDispatchTrigger $trigger = EmailDispatchTrigger::PurchaseWebhook,
        public ?string $contextHotmartEvent = null,
        public ?string $contextProductTitle = null,
        public ?string $contextCurrency = null,
        public ?float $contextAmount = null,
        public ?string $contextTransaction = null,
        public ?string $contextPhone = null,
    ) {}

    public function uniqueId(): string
    {
        $reference = $this->purchaseId > 0
            ? 'purchase:'.$this->purchaseId
            : 'transaction:'.($this->contextTransaction ?? '0');

        return implode(':', [
            'email',
            $this->trigger->value,
            $this->messageEvent->value,
            $reference,
            $this->recipientEmail,
        ]);
    }

    public function handle(
        TransactionalMailService $mailer,
        EmailMessageTemplateService $templates,
        EmailDispatchLogService $dispatchLog,
    ): void {
        $user = User::query()->findOrFail($this->userId);
        $purchase = $this->purchaseId > 0
            ? Purchase::query()->with('product')->find($this->purchaseId)
            : null;

        $context = new NormalizedPurchaseContext(
            hotmartEvent: $this->contextHotmartEvent,
            productTitle: $this->contextProductTitle,
            phone: $this->contextPhone,
            transaction: $this->contextTransaction,
            currency: $this->contextCurrency,
            amount: $this->contextAmount,
        );

        $subject = $templates->renderSubject($this->messageEvent, $user, $purchase, $context);
        $bodyPlain = $templates->renderBody($this->messageEvent, $user, $purchase, $context);
        $bodyHtml = $templates->renderBodyHtml($this->messageEvent, $user, $purchase, $context);
        $attachmentPaths = $templates->attachments($this->messageEvent);
        $purchaseId = $this->purchaseId > 0 ? $this->purchaseId : null;
        $attempt = $this->attempts();

        if ($this->wasAlreadySent($purchaseId)) {
            Log::info('E-mail ignorado: envio já registrado para esta compra/evento.', [
                'user_id' => $this->userId,
                'purchase_id' => $this->purchaseId,
                'email' => $this->recipientEmail,
                'message_event' => $this->messageEvent->value,
            ]);

            return;
        }

        if (blank($this->recipientEmail) || ! filter_var($this->recipientEmail, FILTER_VALIDATE_EMAIL)) {
            $dispatchLog->recordFailure(
                trigger: $this->trigger,
                messageEvent: $this->messageEvent,
                recipientEmail: $this->recipientEmail,
                userId: $this->userId,
                purchaseId: $purchaseId,
                hotmartTransaction: $this->contextTransaction,
                subject: $subject,
                body: $bodyPlain,
                errorMessage: 'Endereço de e-mail inválido ou vazio.',
                attempt: $attempt,
            );

            Log::warning('E-mail não enviado: endereço inválido.', [
                'user_id' => $this->userId,
                'email' => $this->recipientEmail,
                'message_event' => $this->messageEvent->value,
            ]);

            return;
        }

        if (! $mailer->isConfigured()) {
            $dispatchLog->recordFailure(
                trigger: $this->trigger,
                messageEvent: $this->messageEvent,
                recipientEmail: $this->recipientEmail,
                userId: $this->userId,
                purchaseId: $purchaseId,
                hotmartTransaction: $this->contextTransaction,
                subject: $subject,
                body: $bodyPlain,
                errorMessage: 'SMTP Hostinger não configurado.',
                attempt: $attempt,
            );

            return;
        }

        try {
            $result = $mailer->send($this->recipientEmail, $subject, $bodyHtml, $attachmentPaths);

            $dispatchLog->recordSuccess(
                trigger: $this->trigger,
                messageEvent: $this->messageEvent,
                recipientEmail: $this->recipientEmail,
                userId: $this->userId,
                purchaseId: $purchaseId,
                hotmartTransaction: $this->contextTransaction,
                subject: $subject,
                body: $bodyPlain,
                mailerResponse: $result['response'],
                attempt: $attempt,
            );

            Log::info('E-mail enviado.', [
                'user_id' => $this->userId,
                'purchase_id' => $this->purchaseId,
                'email' => $this->recipientEmail,
                'trigger' => $this->trigger->value,
                'message_event' => $this->messageEvent->value,
                'attachments' => $result['response']['attachments'] ?? [],
            ]);
        } catch (Throwable $exception) {
            $dispatchLog->recordThrowable(
                trigger: $this->trigger,
                messageEvent: $this->messageEvent,
                recipientEmail: $this->recipientEmail,
                userId: $this->userId,
                purchaseId: $purchaseId,
                hotmartTransaction: $this->contextTransaction,
                subject: $subject,
                body: $bodyPlain,
                exception: $exception,
                attempt: $attempt,
            );

            throw $exception;
        }
    }

    private function wasAlreadySent(?int $purchaseId): bool
    {
        if ($this->trigger === EmailDispatchTrigger::ManualTest) {
            return false;
        }

        $query = EmailDispatchLog::query()
            ->where('status', EmailDispatchStatus::Sent)
            ->where('trigger', $this->trigger)
            ->where('message_event', $this->messageEvent->value);

        if ($purchaseId) {
            return $query->where('purchase_id', $purchaseId)->exists();
        }

        if (filled($this->contextTransaction)) {
            return $query->where('hotmart_transaction', $this->contextTransaction)->exists();
        }

        return false;
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Falha definitiva ao enviar e-mail.', [
            'user_id' => $this->userId,
            'purchase_id' => $this->purchaseId,
            'email' => $this->recipientEmail,
            'trigger' => $this->trigger->value,
            'message_event' => $this->messageEvent->value,
            'error' => $exception?->getMessage(),
        ]);
    }
}
