<?php

namespace App\Services;

use App\DataTransferObjects\EvolutionInboundMessageData;
use App\Models\WhatsAppFlowExecution;
use App\Models\WhatsAppPendingInboundResponse;
use App\Enums\WhatsAppFlowExecutionStatus;
use Illuminate\Support\Carbon;

class WhatsAppPendingInboundService
{
    public function store(EvolutionInboundMessageData $message): void
    {
        WhatsAppPendingInboundResponse::query()->updateOrCreate(
            [
                'phone_normalized' => $message->phoneNormalized,
                'instance_name' => $this->normalizeInstanceName($message->instance),
            ],
            [
                'message_id' => $message->messageId,
                'remote_jid' => $message->remoteJid,
                'received_at' => now(),
            ],
        );
    }

    public function clear(EvolutionInboundMessageData $message): void
    {
        WhatsAppPendingInboundResponse::query()
            ->where('phone_normalized', $message->phoneNormalized)
            ->when(
                filled($message->instance),
                fn ($query) => $query->whereRaw('LOWER(instance_name) = ?', [strtolower(trim($message->instance))]),
            )
            ->delete();
    }

    /**
     * @return array{message_id: ?string, remote_jid: ?string, received_at: Carbon}|null
     */
    public function consumeForExecution(WhatsAppFlowExecution $execution, string $instanceName): ?array
    {
        $pending = WhatsAppPendingInboundResponse::query()
            ->where('phone_normalized', $execution->phone_normalized)
            ->whereRaw('LOWER(instance_name) = ?', [strtolower(trim($instanceName))])
            ->where('received_at', '>=', $execution->started_at ?? $execution->created_at)
            ->orderByDesc('received_at')
            ->first();

        if (! $pending) {
            return null;
        }

        $payload = [
            'message_id' => $pending->message_id,
            'remote_jid' => $pending->remote_jid,
            'received_at' => $pending->received_at,
        ];

        $pending->delete();

        return $payload;
    }

    public function hasActiveExecution(EvolutionInboundMessageData $message): bool
    {
        return WhatsAppFlowExecution::query()
            ->where('phone_normalized', $message->phoneNormalized)
            ->when(
                filled($message->instance),
                fn ($query) => $query->whereRaw('LOWER(instance_name) = ?', [strtolower(trim($message->instance))]),
            )
            ->whereIn('status', [
                WhatsAppFlowExecutionStatus::Running,
                WhatsAppFlowExecutionStatus::Waiting,
                WhatsAppFlowExecutionStatus::Pending,
            ])
            ->exists();
    }

    private function normalizeInstanceName(?string $instanceName): ?string
    {
        $normalized = trim((string) $instanceName);

        return filled($normalized) ? $normalized : null;
    }
}
