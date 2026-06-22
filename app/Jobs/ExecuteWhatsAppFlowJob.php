<?php

namespace App\Jobs;

use App\Enums\WhatsAppFlowExecutionLogStatus;
use App\Enums\WhatsAppFlowExecutionStatus;
use App\Enums\WhatsAppFlowStepType;
use App\Models\WhatsAppFlowExecution;
use App\Models\WhatsAppFlowExecutionLog;
use App\Models\WhatsAppFlowStep;
use App\Services\WhatsAppFlowStepSenderService;
use App\Services\WhatsAppPendingInboundService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExecuteWhatsAppFlowJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(
        private readonly int $executionId,
        private readonly ?int $resumeAfterStepOrder = null,
        private readonly ?int $stepOrder = null,
        private readonly bool $delayAlreadyApplied = false,
    ) {}

    public static function dispatchFlow(int $executionId, ?int $resumeAfterStepOrder = null): void
    {
        dispatch(new self($executionId, resumeAfterStepOrder: $resumeAfterStepOrder));
    }

    public static function dispatchStep(
        int $executionId,
        int $stepOrder,
        bool $delayAlreadyApplied = false,
        int $waitSeconds = 0,
    ): void {
        $job = new self(
            executionId: $executionId,
            stepOrder: $stepOrder,
            delayAlreadyApplied: $delayAlreadyApplied,
        );

        if ($waitSeconds > 0) {
            dispatch($job)->delay(now()->addSeconds($waitSeconds));

            return;
        }

        dispatch($job);
    }

    public function handle(WhatsAppPendingInboundService $pendingInboundService): void
    {
        $execution = WhatsAppFlowExecution::query()
            ->with(['flow.steps'])
            ->findOrFail($this->executionId);

        if (in_array($execution->status, [WhatsAppFlowExecutionStatus::Completed, WhatsAppFlowExecutionStatus::Failed], true)) {
            Log::info('ExecuteWhatsAppFlowJob ignorado: execução já finalizada.', [
                'execution_id' => $execution->id,
                'status' => $execution->status?->value ?? $execution->status,
            ]);

            return;
        }

        $instanceName = $execution->instance_name ?: $execution->flow?->resolveInstanceName();

        if (blank($instanceName)) {
            $this->failExecution($execution, 'Instância WhatsApp não definida para esta execução.');

            return;
        }

        if (blank($execution->phone_normalized)) {
            $this->failExecution($execution, 'Telefone normalizado ausente.');

            return;
        }

        if ($this->shouldSkipAsDuplicateStart($execution)) {
            Log::info('ExecuteWhatsAppFlowJob ignorado: execução já em andamento ou finalizada.', [
                'execution_id' => $execution->id,
                'status' => $execution->status?->value ?? $execution->status,
                'current_step' => $execution->current_step,
            ]);

            return;
        }

        /** @var Collection<int, WhatsAppFlowStep> $steps */
        $steps = $execution->flow->steps->sortBy('order')->values();

        $targetStepOrder = $this->resolveTargetStepOrder($steps, $execution);

        if ($targetStepOrder === null) {
            $this->completeExecution($execution);

            return;
        }

        $step = $steps->firstWhere('order', $targetStepOrder);

        if (! $step) {
            $this->failExecution($execution, "Passo {$targetStepOrder} não encontrado no fluxo.");

            return;
        }

        if ($step->delay_seconds > 0 && ! $this->delayAlreadyApplied) {
            if ($execution->status === WhatsAppFlowExecutionStatus::Pending) {
                $execution->update([
                    'status' => WhatsAppFlowExecutionStatus::Running,
                    'started_at' => now(),
                ]);
            }

            self::dispatchStep(
                executionId: $execution->id,
                stepOrder: $targetStepOrder,
                delayAlreadyApplied: true,
                waitSeconds: $step->delay_seconds,
            );

            return;
        }

        $execution->update([
            'status' => WhatsAppFlowExecutionStatus::Running,
            'started_at' => $execution->started_at ?? now(),
            'waiting_step_id' => null,
            'waiting_since' => null,
        ]);

        $stepType = $step->type instanceof WhatsAppFlowStepType
            ? $step->type
            : WhatsAppFlowStepType::tryFrom((string) $step->type);

        if ($stepType === WhatsAppFlowStepType::WaitForResponse) {
            $this->handleWaitForResponseStep($execution, $step, $stepType, $pendingInboundService, $instanceName, $steps);

            return;
        }

        if ($stepType === WhatsAppFlowStepType::Delay) {
            WhatsAppFlowExecutionLog::query()->create([
                'execution_id' => $execution->id,
                'step_id' => $step->id,
                'step_order' => $step->order,
                'step_type' => $stepType->value,
                'status' => WhatsAppFlowExecutionLogStatus::Sent,
                'http_status' => null,
                'error_message' => null,
                'evolution_response' => null,
                'sent_at' => now(),
            ]);

            $execution->update(['current_step' => $step->order]);
            $this->scheduleNextStep($execution, $steps, $step->order);

            return;
        }

        $sender = new WhatsAppFlowStepSenderService($instanceName);
        $result = $sender->send($step, $execution->phone_normalized, $execution->contact_name);

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
            Log::warning('WhatsApp Flow passo falhou', [
                'execution_id' => $execution->id,
                'step_id' => $step->id,
                'error' => $result['error'],
            ]);
        }

        $this->scheduleNextStep($execution, $steps, $step->order);
    }

    /**
     * @param  Collection<int, WhatsAppFlowStep>  $steps
     */
    private function handleWaitForResponseStep(
        WhatsAppFlowExecution $execution,
        WhatsAppFlowStep $step,
        WhatsAppFlowStepType $stepType,
        WhatsAppPendingInboundService $pendingInboundService,
        string $instanceName,
        Collection $steps,
    ): void {
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

            $this->scheduleNextStep($execution, $steps, $step->order);

            return;
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
    }

    /**
     * @param  Collection<int, WhatsAppFlowStep>  $steps
     */
    private function scheduleNextStep(
        WhatsAppFlowExecution $execution,
        Collection $steps,
        int $completedStepOrder,
    ): void {
        $nextStepOrder = $this->nextStepOrder($steps, $completedStepOrder);

        if ($nextStepOrder === null) {
            $this->completeExecution($execution);

            return;
        }

        self::dispatchStep($execution->id, $nextStepOrder);
    }

    /**
     * @param  Collection<int, WhatsAppFlowStep>  $steps
     */
    private function resolveTargetStepOrder(Collection $steps, WhatsAppFlowExecution $execution): ?int
    {
        if ($this->stepOrder !== null) {
            return $this->stepOrder;
        }

        $afterOrder = $this->resumeAfterStepOrder;

        if ($afterOrder === null && $execution->status === WhatsAppFlowExecutionStatus::Waiting) {
            $afterOrder = (int) $execution->current_step;
        }

        return $this->nextStepOrder($steps, $afterOrder ?? 0);
    }

    /**
     * @param  Collection<int, WhatsAppFlowStep>  $steps
     */
    private function nextStepOrder(Collection $steps, int $afterOrder): ?int
    {
        return $steps->first(fn (WhatsAppFlowStep $step): bool => $step->order > $afterOrder)?->order;
    }

    private function shouldSkipAsDuplicateStart(WhatsAppFlowExecution $execution): bool
    {
        if ($this->stepOrder !== null || $this->resumeAfterStepOrder !== null) {
            return false;
        }

        if ($execution->status === WhatsAppFlowExecutionStatus::Waiting) {
            return false;
        }

        return $execution->status !== WhatsAppFlowExecutionStatus::Pending;
    }

    private function completeExecution(WhatsAppFlowExecution $execution): void
    {
        $failedLogs = WhatsAppFlowExecutionLog::query()
            ->where('execution_id', $execution->id)
            ->where('status', WhatsAppFlowExecutionLogStatus::Failed)
            ->orderBy('step_order')
            ->get();

        $execution->update([
            'status' => $failedLogs->isNotEmpty()
                ? WhatsAppFlowExecutionStatus::Failed
                : WhatsAppFlowExecutionStatus::Completed,
            'error_message' => $failedLogs->isNotEmpty()
                ? $failedLogs
                    ->map(fn (WhatsAppFlowExecutionLog $log): string => "Passo {$log->step_order}: ".($log->error_message ?? 'erro desconhecido'))
                    ->implode(' | ')
                : null,
            'completed_at' => now(),
        ]);
    }

    private function failExecution(WhatsAppFlowExecution $execution, string $message): void
    {
        $execution->update([
            'status' => WhatsAppFlowExecutionStatus::Failed,
            'error_message' => $message,
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
