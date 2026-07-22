<?php

namespace App\Services;

use App\Enums\EmailDispatchStatus;
use App\Enums\EmailDispatchTrigger;
use App\Enums\WhatsAppMessageEvent;
use App\Models\EmailDispatchLog;
use App\Support\IntegrationSettings;
use Throwable;

class EmailDispatchLogService
{
    public function recordSuccess(
        EmailDispatchTrigger $trigger,
        ?WhatsAppMessageEvent $messageEvent,
        string $recipientEmail,
        ?int $userId,
        ?int $purchaseId,
        ?string $hotmartTransaction,
        string $subject,
        string $body,
        array $mailerResponse = [],
        int $attempt = 1,
        ?string $fromAddress = null,
    ): EmailDispatchLog {
        return $this->create([
            'trigger' => $trigger,
            'message_event' => $messageEvent?->value,
            'status' => EmailDispatchStatus::Sent,
            'from_address' => $fromAddress ?: IntegrationSettings::mailFromAddress(),
            'recipient_email' => $recipientEmail,
            'user_id' => $userId,
            'purchase_id' => $purchaseId,
            'hotmart_transaction' => $hotmartTransaction,
            'subject' => $subject,
            'body' => $body,
            'attempt' => $attempt,
            'mailer_response' => $mailerResponse,
        ]);
    }

    public function recordFailure(
        EmailDispatchTrigger $trigger,
        ?WhatsAppMessageEvent $messageEvent,
        string $recipientEmail,
        ?int $userId,
        ?int $purchaseId,
        ?string $hotmartTransaction,
        ?string $subject,
        ?string $body,
        string $errorMessage,
        int $attempt = 1,
        ?array $mailerResponse = null,
        ?string $fromAddress = null,
    ): EmailDispatchLog {
        return $this->create([
            'trigger' => $trigger,
            'message_event' => $messageEvent?->value,
            'status' => EmailDispatchStatus::Failed,
            'from_address' => $fromAddress ?: IntegrationSettings::mailFromAddress(),
            'recipient_email' => $recipientEmail,
            'user_id' => $userId,
            'purchase_id' => $purchaseId,
            'hotmart_transaction' => $hotmartTransaction,
            'subject' => $subject,
            'body' => $body,
            'error_message' => $errorMessage,
            'attempt' => $attempt,
            'mailer_response' => $mailerResponse,
        ]);
    }

    public function recordThrowable(
        EmailDispatchTrigger $trigger,
        ?WhatsAppMessageEvent $messageEvent,
        string $recipientEmail,
        ?int $userId,
        ?int $purchaseId,
        ?string $hotmartTransaction,
        ?string $subject,
        ?string $body,
        Throwable $exception,
        int $attempt = 1,
        ?string $fromAddress = null,
    ): EmailDispatchLog {
        return $this->recordFailure(
            trigger: $trigger,
            messageEvent: $messageEvent,
            recipientEmail: $recipientEmail,
            userId: $userId,
            purchaseId: $purchaseId,
            hotmartTransaction: $hotmartTransaction,
            subject: $subject,
            body: $body,
            errorMessage: $exception->getMessage(),
            attempt: $attempt,
            fromAddress: $fromAddress,
        );
    }

    private function create(array $attributes): EmailDispatchLog
    {
        return EmailDispatchLog::query()->create([
            ...$attributes,
            'purchase_id' => ($attributes['purchase_id'] ?? null) > 0 ? $attributes['purchase_id'] : null,
            'created_at' => now(),
        ]);
    }
}
