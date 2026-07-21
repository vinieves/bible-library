<?php

namespace App\Filament\Resources\WhatsAppFlowExecutionResource\Pages;

use App\Enums\WhatsAppFlowExecutionLogStatus;
use App\Enums\WhatsAppFlowStepType;
use App\Filament\Resources\WhatsAppFlowExecutionResource;
use App\Models\WhatsAppFlowExecution;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewWhatsAppFlowExecution extends ViewRecord
{
    protected static string $resource = WhatsAppFlowExecutionResource::class;

    protected function resolveRecord(int|string $key): Model
    {
        return parent::resolveRecord($key)->loadMissing(['flow', 'logs', 'user']);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var WhatsAppFlowExecution $record */
        $record = $this->getRecord();

        $data['flow_name'] = $record->flow?->name ?? '—';
        $data['progress'] = "{$record->current_step}/{$record->total_steps}";
        $data['steps_log'] = $this->buildStepsLog($record);

        return $data;
    }

    private function buildStepsLog(WhatsAppFlowExecution $record): string
    {
        $lines = $record->logs
            ->sortBy('step_order')
            ->map(function ($log): string {
                $type = WhatsAppFlowStepType::tryFrom($log->step_type)?->label() ?? $log->step_type;
                $status = $log->status instanceof WhatsAppFlowExecutionLogStatus
                    ? $log->status->label()
                    : (string) $log->status;
                $http = $log->http_status ? "HTTP {$log->http_status}" : '—';
                $error = $log->error_message ? " | Erro: {$log->error_message}" : '';
                $response = filled($log->evolution_response)
                    ? "\n   Resposta: ".json_encode($log->evolution_response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : '';

                return "#{$log->step_order} {$type} — {$status} ({$http}){$error}{$response}";
            });

        return $lines->isEmpty() ? 'Nenhum passo registrado.' : $lines->implode("\n\n");
    }
}
