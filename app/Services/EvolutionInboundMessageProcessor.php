<?php

namespace App\Services;

use App\DataTransferObjects\EvolutionInboundMessageData;
use App\Enums\WhatsAppFlowExecutionLogStatus;
use App\Enums\WhatsAppFlowExecutionStatus;
use App\Enums\WhatsAppFlowTriggerType;
use App\Jobs\ExecuteWhatsAppFlowJob;
use App\Models\WhatsAppFlowExecution;
use App\Models\WhatsAppFlowExecutionLog;
use App\Models\WhatsAppInboundContact;
use App\Services\Webhooks\PhoneNumberQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class EvolutionInboundMessageProcessor
{
    public function __construct(
        private readonly WhatsAppFlowService $flowService,
        private readonly WhatsAppPendingInboundService $pendingInboundService,
        private readonly WhatsAppFlowContactNameService $contactNameService,
        private readonly WhatsAppMessageTriggerMatcher $messageTriggerMatcher,
    ) {}

    public function process(EvolutionInboundMessageData $message, ?int $webhookLogId = null): void
    {
        $this->contactNameService->syncFromInboundMessage($message);

        if ($this->tryResumeWaitingExecution($message, $webhookLogId)) {
            $this->pendingInboundService->clear($message);

            return;
        }

        if ($this->pendingInboundService->hasActiveExecution($message)) {
            $this->pendingInboundService->store($message);

            Log::info('Resposta armazenada: fluxo ainda não estava aguardando (corrida de fila).', [
                'phone' => $message->phoneNormalized,
                'instance' => $message->instance,
                'message_id' => $message->messageId,
            ]);

            return;
        }

        $flow = $this->messageTriggerMatcher->resolveFlowForInboundMessage(
            $message->instance,
            $message->messageText,
        );

        if (! $flow) {
            Log::info('Mensagem recebida sem fluxo compatível para retomar ou disparar.', [
                'phone' => $message->phoneNormalized,
                'instance' => $message->instance,
                'message_text' => $message->messageText,
            ]);

            return;
        }

        $triggerLabel = $flow->trigger_type === WhatsAppFlowTriggerType::MessageTrigger
            ? WhatsAppFlowTriggerType::MessageTrigger->value.':'.($flow->messageTrigger?->public_code ?? $flow->message_trigger_id)
            : WhatsAppFlowTriggerType::FirstMessage->value;

        $isFirstMessage = false;
        $executionId = null;

        DB::transaction(function () use ($message, $flow, $triggerLabel, &$isFirstMessage, &$executionId): void {
            $existing = WhatsAppInboundContact::query()
                ->tap(fn ($query) => PhoneNumberQuery::whereMatchesPhone($query, 'phone_normalized', $message->phoneNormalized))
                ->lockForUpdate()
                ->first();

            if ($existing) {
                Log::info('Primeira mensagem ignorada: contato já registrado.', [
                    'phone' => $message->phoneNormalized,
                    'first_message_at' => $existing->first_message_at?->toIso8601String(),
                    'latest_executions' => WhatsAppFlowExecution::query()
                        ->tap(fn ($query) => PhoneNumberQuery::whereMatchesPhone($query, 'phone_normalized', $message->phoneNormalized))
                        ->latest('id')
                        ->limit(3)
                        ->get(['id', 'status', 'instance_name', 'current_step', 'waiting_step_id'])
                        ->map(fn (WhatsAppFlowExecution $execution) => [
                            'id' => $execution->id,
                            'status' => $execution->status?->value ?? $execution->status,
                            'instance_name' => $execution->instance_name,
                            'current_step' => $execution->current_step,
                            'waiting_step_id' => $execution->waiting_step_id,
                        ])
                        ->all(),
                ]);

                return;
            }

            $isFirstMessage = true;

            try {
                $execution = $this->flowService->dispatch(
                    flow: $flow,
                    phone: $message->phoneNormalized,
                    trigger: $triggerLabel,
                    contactName: $message->pushName,
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
            app(EvolutionRegistryService::class)->markFlowTriggeredForWebhookLog($webhookLogId);

            Log::info('Fluxo enfileirado para nova conversa.', [
                'phone' => $message->phoneNormalized,
                'flow_id' => $flow->id,
                'flow_trigger_type' => $flow->trigger_type?->value ?? $flow->trigger_type,
                'message_trigger_id' => $flow->message_trigger_id,
                'instance' => $message->instance,
                'execution_id' => $executionId,
                'inbound_message' => $message->messageText,
            ]);
        }
    }

    private function tryResumeWaitingExecution(EvolutionInboundMessageData $message, ?int $webhookLogId = null): bool
    {
        $dispatched = false;

        DB::transaction(function () use ($message, &$dispatched): void {
            $query = WhatsAppFlowExecution::query()
                ->tap(fn ($builder) => PhoneNumberQuery::whereMatchesPhone($builder, 'phone_normalized', $message->phoneNormalized))
                ->where('status', WhatsAppFlowExecutionStatus::Waiting)
                ->lockForUpdate();

            if (filled($message->instance)) {
                $query->whereRaw('LOWER(instance_name) = ?', [strtolower(trim($message->instance))]);
            }

            $execution = $query->latest('id')->first();

            if (! $execution && filled($message->instance)) {
                $execution = WhatsAppFlowExecution::query()
                    ->tap(fn ($builder) => PhoneNumberQuery::whereMatchesPhone($builder, 'phone_normalized', $message->phoneNormalized))
                    ->where('status', WhatsAppFlowExecutionStatus::Waiting)
                    ->lockForUpdate()
                    ->latest('id')
                    ->first();

                if ($execution) {
                    Log::warning('Retomando execução aguardando com instância divergente.', [
                        'phone' => $message->phoneNormalized,
                        'webhook_instance' => $message->instance,
                        'execution_instance' => $execution->instance_name,
                        'execution_id' => $execution->id,
                    ]);
                }
            }

            if (! $execution) {
                return;
            }

            $this->pendingInboundService->clear($message);

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

            $resumeAfterStepOrder = (int) $execution->current_step;

            $execution->update([
                'status' => WhatsAppFlowExecutionStatus::Running,
                'waiting_step_id' => null,
                'waiting_since' => null,
            ]);

            ExecuteWhatsAppFlowJob::dispatchFlow($execution->id, $resumeAfterStepOrder);

            $dispatched = true;
        });

        if ($dispatched) {
            app(EvolutionRegistryService::class)->markFlowTriggeredForWebhookLog($webhookLogId);

            Log::info('Fluxo retomado após resposta do contato.', [
                'phone' => $message->phoneNormalized,
                'instance' => $message->instance,
                'inbound_message_id' => $message->messageId,
            ]);
        } else {
            Log::info('Resposta recebida sem execução em status waiting.', [
                'phone' => $message->phoneNormalized,
                'instance' => $message->instance,
                'message_id' => $message->messageId,
                'latest_executions' => WhatsAppFlowExecution::query()
                    ->tap(fn ($query) => PhoneNumberQuery::whereMatchesPhone($query, 'phone_normalized', $message->phoneNormalized))
                    ->when(
                        filled($message->instance),
                        fn ($query) => $query->whereRaw('LOWER(instance_name) = ?', [strtolower(trim($message->instance))]),
                    )
                    ->latest('id')
                    ->limit(3)
                    ->get(['id', 'status', 'instance_name', 'current_step', 'waiting_step_id'])
                    ->map(fn (WhatsAppFlowExecution $execution) => [
                        'id' => $execution->id,
                        'status' => $execution->status?->value ?? $execution->status,
                        'instance_name' => $execution->instance_name,
                        'current_step' => $execution->current_step,
                        'waiting_step_id' => $execution->waiting_step_id,
                    ])
                    ->all(),
            ]);
        }

        return $dispatched;
    }
}
