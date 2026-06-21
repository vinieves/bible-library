<?php

namespace App\Services;

use App\DataTransferObjects\EvolutionInboundMessageData;
use App\Enums\WhatsAppFlowExecutionLogStatus;
use App\Enums\WhatsAppFlowExecutionStatus;
use App\Enums\WhatsAppFlowTriggerType;
use App\Jobs\ExecuteWhatsAppFlowJob;
use App\Models\WhatsAppFlow;
use App\Models\WhatsAppFlowExecution;
use App\Models\WhatsAppFlowExecutionLog;
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
        if ($this->tryResumeWaitingExecution($message)) {
            return;
        }

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

    private function tryResumeWaitingExecution(EvolutionInboundMessageData $message): bool
    {
        $execution = WhatsAppFlowExecution::query()
            ->where('phone_normalized', $message->phoneNormalized)
            ->where('status', WhatsAppFlowExecutionStatus::Waiting)
            ->when(
                filled($message->instance),
                fn ($query) => $query->where('instance_name', $message->instance),
            )
            ->latest('id')
            ->first();

        if (! $execution) {
            return false;
        }

        WhatsAppFlowExecutionLog::query()
            ->where('execution_id', $execution->id)
            ->where('step_id', $execution->waiting_step_id)
            ->where('status', WhatsAppFlowExecutionLogStatus::Waiting)
            ->latest('id')
            ->limit(1)
            ->update([
                'status' => WhatsAppFlowExecutionLogStatus::Received,
                'evolution_response' => [
                    'inbound_message_id' => $message->messageId,
                    'remote_jid' => $message->remoteJid,
                ],
            ]);

        ExecuteWhatsAppFlowJob::dispatch($execution->id);

        Log::info('Fluxo retomado após resposta do contato.', [
            'execution_id' => $execution->id,
            'phone' => $message->phoneNormalized,
            'instance' => $message->instance,
            'inbound_message_id' => $message->messageId,
        ]);

        return true;
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
