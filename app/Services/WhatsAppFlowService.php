<?php

namespace App\Services;

use App\Enums\WhatsAppFlowExecutionStatus;
use App\Jobs\ExecuteWhatsAppFlowJob;
use App\Models\WhatsAppFlow;
use App\Models\WhatsAppFlowExecution;
use App\Services\Webhooks\PhoneNumber;
use App\Support\IntegrationSettings;
use RuntimeException;

class WhatsAppFlowService
{
    public function dispatch(
        WhatsAppFlow $flow,
        string $phone,
        string $trigger = 'manual',
        ?int $userId = null,
    ): WhatsAppFlowExecution {
        if (! IntegrationSettings::evolutionApiReady()) {
            throw new RuntimeException('Evolution API não configurada no painel admin.');
        }

        $instanceName = $flow->resolveInstanceName();

        if (blank($instanceName)) {
            throw new RuntimeException('Defina a instância WhatsApp deste fluxo ou configure o padrão em Integrações API.');
        }

        $normalized = PhoneNumber::normalize($phone);

        if (! $normalized) {
            throw new RuntimeException('Número de telefone inválido ou vazio.');
        }

        if ($flow->steps()->count() === 0) {
            throw new RuntimeException('O fluxo não possui passos configurados.');
        }

        $execution = WhatsAppFlowExecution::query()->create([
            'flow_id' => $flow->id,
            'phone' => $phone,
            'phone_normalized' => $normalized,
            'user_id' => $userId,
            'trigger' => $trigger,
            'instance_name' => $instanceName,
            'status' => WhatsAppFlowExecutionStatus::Pending,
            'current_step' => 0,
            'total_steps' => $flow->steps()->count(),
        ]);

        ExecuteWhatsAppFlowJob::dispatch($execution->id);

        return $execution;
    }
}
