<?php

namespace App\Services;

use App\Enums\WhatsAppDispatchStatus;
use App\Enums\WhatsAppDispatchTrigger;
use App\Models\WhatsAppDispatchLog;
use Throwable;

class WhatsAppDispatchLogService
{
    public function recordSuccess(
        WhatsAppDispatchTrigger $trigger,
        string $phone,
        ?string $phoneNormalized,
        ?int $userId,
        ?int $purchaseId,
        string $message,
        array $evolutionResponse,
        int $attempt = 1,
        ?int $httpStatus = null,
    ): WhatsAppDispatchLog {
        return $this->create([
            'trigger' => $trigger,
            'status' => WhatsAppDispatchStatus::Sent,
            'phone' => $phone,
            'phone_normalized' => $phoneNormalized,
            'user_id' => $userId,
            'purchase_id' => $purchaseId,
            'message' => $message,
            'http_status' => $httpStatus ?? 200,
            'attempt' => $attempt,
            'evolution_response' => $evolutionResponse,
        ]);
    }

    public function recordFailure(
        WhatsAppDispatchTrigger $trigger,
        string $phone,
        ?string $phoneNormalized,
        ?int $userId,
        ?int $purchaseId,
        ?string $message,
        string $errorMessage,
        int $attempt = 1,
        ?int $httpStatus = null,
        ?array $evolutionResponse = null,
    ): WhatsAppDispatchLog {
        return $this->create([
            'trigger' => $trigger,
            'status' => WhatsAppDispatchStatus::Failed,
            'phone' => $phone,
            'phone_normalized' => $phoneNormalized,
            'user_id' => $userId,
            'purchase_id' => $purchaseId,
            'message' => $message,
            'error_message' => $errorMessage,
            'http_status' => $httpStatus,
            'attempt' => $attempt,
            'evolution_response' => $evolutionResponse,
        ]);
    }

    public function recordThrowable(
        WhatsAppDispatchTrigger $trigger,
        string $phone,
        ?string $phoneNormalized,
        ?int $userId,
        ?int $purchaseId,
        ?string $message,
        Throwable $exception,
        int $attempt = 1,
    ): WhatsAppDispatchLog {
        $httpStatus = $this->extractHttpStatus($exception->getMessage());

        return $this->recordFailure(
            trigger: $trigger,
            phone: $phone,
            phoneNormalized: $phoneNormalized,
            userId: $userId,
            purchaseId: $purchaseId,
            message: $message,
            errorMessage: $exception->getMessage(),
            attempt: $attempt,
            httpStatus: $httpStatus,
        );
    }

    private function extractHttpStatus(string $message): ?int
    {
        if (preg_match('/erro HTTP (\d{3})/i', $message, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function create(array $attributes): WhatsAppDispatchLog
    {
        return WhatsAppDispatchLog::query()->create([
            ...$attributes,
            'purchase_id' => ($attributes['purchase_id'] ?? null) > 0 ? $attributes['purchase_id'] : null,
            'created_at' => now(),
        ]);
    }
}
