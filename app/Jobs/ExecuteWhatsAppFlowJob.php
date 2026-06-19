<?php

namespace App\Jobs;

use App\Enums\WhatsAppFlowExecutionLogStatus;
use App\Enums\WhatsAppFlowExecutionStatus;
use App\Enums\WhatsAppFlowStepType;
use App\Models\WhatsAppFlowExecution;
use App\Models\WhatsAppFlowExecutionLog;
use App\Services\WhatsAppFlowStepSenderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExecuteWhatsAppFlowJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(private readonly int $executionId) {}

    public function handle(WhatsAppFlowStepSenderService $sender): void
    {
        $execution = WhatsAppFlowExecution::query()
            ->with(['flow.steps'])
            ->findOrFail($this->executionId);

        if (blank($execution->phone_normalized)) {
            $execution->update([
                'status' => WhatsAppFlowExecutionStatus::Failed,
                'error_message' => 'Telefone normalizado ausente.',
                'completed_at' => now(),
            ]);

            return;
        }

        $execution->update([
            'status' => WhatsAppFlowExecutionStatus::Running,
            'started_at' => now(),
        ]);

        $steps = $execution->flow->steps;

        foreach ($steps as $step) {
            $execution->increment('current_step');

            $result = $sender->send($step, $execution->phone_normalized);

            $stepType = $step->type instanceof WhatsAppFlowStepType
                ? $step->type->value
                : (string) $step->type;

            WhatsAppFlowExecutionLog::query()->create([
                'execution_id' => $execution->id,
                'step_id' => $step->id,
                'step_order' => $step->order,
                'step_type' => $stepType,
                'status' => $result['success']
                    ? WhatsAppFlowExecutionLogStatus::Sent
                    : WhatsAppFlowExecutionLogStatus::Failed,
                'http_status' => $result['http_status'] ?: null,
                'error_message' => $result['error'],
                'evolution_response' => $result['response'],
                'sent_at' => now(),
            ]);

            if (! $result['success']) {
                Log::warning('WhatsApp Flow passo falhou', [
                    'execution_id' => $execution->id,
                    'step_id' => $step->id,
                    'error' => $result['error'],
                ]);
            }
        }

        $execution->update([
            'status' => WhatsAppFlowExecutionStatus::Completed,
            'completed_at' => now(),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        WhatsAppFlowExecution::query()
            ->whereKey($this->executionId)
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
