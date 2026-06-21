<?php

namespace App\Jobs;

use App\Enums\WhatsAppFlowExecutionLogStatus;
use App\Enums\WhatsAppFlowExecutionStatus;
use App\Enums\WhatsAppFlowStepType;
use App\Models\WhatsAppFlowExecution;
use App\Models\WhatsAppFlowExecutionLog;
use App\Services\WhatsAppFlowStepSenderService;
use App\Services\WhatsAppPendingInboundService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExecuteWhatsAppFlowJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(
        private readonly int $executionId,
        private readonly ?int $resumeAfterStepOrder = null,
    ) {}

    public function handle(WhatsAppPendingInboundService $pendingInboundService): void
    {
        $execution = WhatsAppFlowExecution::query()
            ->with(['flow.steps'])
            ->findOrFail($this->executionId);

        $instanceName = $execution->instance_name ?: $execution->flow?->resolveInstanceName();

        if (blank($instanceName)) {
            $execution->update([
                'status' => WhatsAppFlowExecutionStatus::Failed,
                'error_message' => 'Instância WhatsApp não definida para esta execução.',
                'completed_at' => now(),
            ]);

            return;
        }

        if (blank($execution->phone_normalized)) {
            $execution->update([
                'status' => WhatsAppFlowExecutionStatus::Failed,
                'error_message' => 'Telefone normalizado ausente.',
                'completed_at' => now(),
            ]);

            return;
        }

        $resumeAfterOrder = $this->resumeAfterStepOrder;

        if ($resumeAfterOrder === null && $execution->status === WhatsAppFlowExecutionStatus::Waiting) {
            $resumeAfterOrder = (int) $execution->current_step;
        }

        if ($resumeAfterOrder === null && $execution->status !== WhatsAppFlowExecutionStatus::Pending) {
            Log::info('ExecuteWhatsAppFlowJob ignorado: execução já em andamento ou finalizada.', [
                'execution_id' => $execution->id,
                'status' => $execution->status?->value ?? $execution->status,
                'current_step' => $execution->current_step,
            ]);

            return;
        }

        $execution->update([
            'status' => WhatsAppFlowExecutionStatus::Running,
            'started_at' => $execution->started_at ?? now(),
            'waiting_step_id' => null,
            'waiting_since' => null,
        ]);

        $sender = new WhatsAppFlowStepSenderService($instanceName);
        $steps = $execution->flow->steps;
        $hadFailure = false;
        $failureMessages = [];

        foreach ($steps as $step) {
            if ($resumeAfterOrder !== null && $step->order <= $resumeAfterOrder) {
                continue;
            }

            $stepType = $step->type instanceof WhatsAppFlowStepType
                ? $step->type
                : WhatsAppFlowStepType::tryFrom((string) $step->type);

            if ($stepType === WhatsAppFlowStepType::WaitForResponse) {
                if ($step->delay_seconds > 0) {
                    sleep($step->delay_seconds);
                }

                $pendingInbound = $pendingInboundService->consumeForExecution($execution, $instanceName);

                if ($pendingInbound) {
                    WhatsAppFlowExecutionLog::query()->create([
                        'execution_id' => $execution->id,
                        'step_id' => $step->id,
                        'step_order' => $step->order,
                        'step_type' => $stepType->value,
                        'status' => WhatsAppFlowExecutionLogStatus::Received,
                        'http_status' => null,
                        'error_message' => null,
                        'evolution_response' => [
                            'inbound_message_id' => $pendingInbound['message_id'] ?? null,
                            'remote_jid' => $pendingInbound['remote_jid'] ?? null,
                            'source' => 'pending_inbound_buffer',
                            'received_at' => $pendingInbound['received_at']?->toIso8601String(),
                        ],
                        'sent_at' => now(),
                    ]);

                    $execution->update(['current_step' => $step->order]);

                    Log::info('WhatsApp Flow: resposta já recebida antes da pausa, continuando fluxo.', [
                        'execution_id' => $execution->id,
                        'step_id' => $step->id,
                        'step_order' => $step->order,
                        'phone' => $execution->phone_normalized,
                    ]);

                    continue;
                }

                WhatsAppFlowExecutionLog::query()->create([
                    'execution_id' => $execution->id,
                    'step_id' => $step->id,
                    'step_order' => $step->order,
                    'step_type' => $stepType->value,
                    'status' => WhatsAppFlowExecutionLogStatus::Waiting,
                    'http_status' => null,
                    'error_message' => null,
                    'evolution_response' => null,
                    'sent_at' => now(),
                ]);

                $execution->update([
                    'status' => WhatsAppFlowExecutionStatus::Waiting,
                    'current_step' => $step->order,
                    'waiting_step_id' => $step->id,
                    'waiting_since' => now(),
                ]);

                Log::info('WhatsApp Flow pausado aguardando resposta do contato.', [
                    'execution_id' => $execution->id,
                    'step_id' => $step->id,
                    'step_order' => $step->order,
                    'phone' => $execution->phone_normalized,
                ]);

                return;
            }

            $result = $sender->send($step, $execution->phone_normalized);

            WhatsAppFlowExecutionLog::query()->create([
                'execution_id' => $execution->id,
                'step_id' => $step->id,
                'step_order' => $step->order,
                'step_type' => $stepType?->value ?? (string) $step->type,
                'status' => $result['success']
                    ? WhatsAppFlowExecutionLogStatus::Sent
                    : WhatsAppFlowExecutionLogStatus::Failed,
                'http_status' => $result['http_status'] ?: null,
                'error_message' => $result['error'],
                'evolution_response' => $result['response'],
                'sent_at' => now(),
            ]);

            $execution->update(['current_step' => $step->order]);

            if (! $result['success']) {
                $hadFailure = true;
                $failureMessages[] = "Passo {$step->order}: ".($result['error'] ?? 'erro desconhecido');

                Log::warning('WhatsApp Flow passo falhou', [
                    'execution_id' => $execution->id,
                    'step_id' => $step->id,
                    'error' => $result['error'],
                ]);
            }
        }

        $execution->update([
            'status' => $hadFailure
                ? WhatsAppFlowExecutionStatus::Failed
                : WhatsAppFlowExecutionStatus::Completed,
            'error_message' => $hadFailure ? implode(' | ', $failureMessages) : null,
            'completed_at' => now(),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        WhatsAppFlowExecution::query()
            ->whereKey($this->executionId)
            ->where('status', '!=', WhatsAppFlowExecutionStatus::Waiting)
            ->update([
                'status' => WhatsAppFlowExecutionStatus::Failed,
                'error_message' => $exception?->getMessage(),
                'completed_at' => now(),
            ]);

        Log::error('ExecuteWhatsAppFlowJob falhou', [
            'execution_id' => $this->executionId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
