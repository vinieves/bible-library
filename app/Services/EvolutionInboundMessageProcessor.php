<?php

namespace App\Services;

use App\DataTransferObjects\EvolutionInboundMessageData;
use App\Enums\WhatsAppFlowTriggerType;
use App\Models\WhatsAppFlow;
use App\Models\WhatsAppInboundContact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class EvolutionInboundMessageProcessor
{
    public function __construct(
        private readonly WhatsAppFlowService $flowService,
    ) {}

    public function process(EvolutionInboundMessageData $message): void
    {
        $flow = $this->resolveFlowForInstance($message->instance);

        if (! $flow) {
            Log::info('Primeira mensagem recebida, mas nenhum fluxo ativo para esta instância.', [
                'phone' => $message->phoneNormalized,
                'instance' => $message->instance,
            ]);

            return;
        }

        $isFirstMessage = false;
        $executionId = null;

        DB::transaction(function () use ($message, $flow, &$isFirstMessage, &$executionId): void {
            $existing = WhatsAppInboundContact::query()
                ->where('phone_normalized', $message->phoneNormalized)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                Log::info('Primeira mensagem ignorada: contato já registrado.', [
                    'phone' => $message->phoneNormalized,
                    'first_message_at' => $existing->first_message_at?->toIso8601String(),
                ]);

                return;
            }

            $isFirstMessage = true;

            try {
                $execution = $this->flowService->dispatch(
                    flow: $flow,
                    phone: $message->phoneNormalized,
                    trigger: WhatsAppFlowTriggerType::FirstMessage->value,
                );

                $executionId = $execution->id;
            } catch (RuntimeException $exception) {
                Log::warning('Falha ao enfileirar fluxo de primeira mensagem.', [
                    'phone' => $message->phoneNormalized,
                    'flow_id' => $flow->id,
                    'instance' => $message->instance,
                    'error' => $exception->getMessage(),
                ]);

                throw $exception;
            }

            WhatsAppInboundContact::query()->create([
                'phone_normalized' => $message->phoneNormalized,
                'remote_jid' => $message->remoteJid,
                'push_name' => $message->pushName,
                'first_message_at' => now(),
                'first_message_id' => $message->messageId,
                'flow_execution_id' => $executionId,
            ]);
        });

        if ($isFirstMessage) {
            Log::info('Fluxo de primeira mensagem enfileirado.', [
                'phone' => $message->phoneNormalized,
                'flow_id' => $flow->id,
                'instance' => $message->instance,
                'execution_id' => $executionId,
            ]);
        }
    }

    private function resolveFlowForInstance(?string $instanceName): ?WhatsAppFlow
    {
        if (blank($instanceName)) {
            return null;
        }

        $flows = WhatsAppFlow::query()
            ->where('trigger_type', WhatsAppFlowTriggerType::FirstMessage)
            ->where('is_active', true)
            ->where('steps_count', '>', 0)
            ->get();

        return $flows->first(function (WhatsAppFlow $flow) use ($instanceName): bool {
            $resolved = $flow->resolveInstanceName();

            return filled($resolved) && strcasecmp($resolved, $instanceName) === 0;
        });
    }
}
