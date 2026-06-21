<?php

namespace App\Jobs;

use App\DataTransferObjects\EvolutionInboundMessageData;
use App\Services\EvolutionInboundMessageProcessor;
use App\Services\EvolutionWebhookLogService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessEvolutionInboundMessageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $backoff = 10;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        private readonly array $payload,
        private readonly ?int $logId = null,
    ) {}

    public function handle(
        EvolutionInboundMessageProcessor $processor,
        EvolutionWebhookLogService $logService,
    ): void {
        $messages = EvolutionInboundMessageData::collectFromPayload($this->payload);

        if ($this->logId) {
            $logService->markJobResult($this->logId, $messages, wasIgnored: true);
        }

        if ($messages === []) {
            Log::warning('Webhook Evolution ignorado: evento/payload inválido para mensagem recebida.', [
                'log_id' => $this->logId,
                'event' => $this->payload['event'] ?? null,
                'event_normalized' => strtoupper(str_replace(['.', '-'], '_', (string) ($this->payload['event'] ?? ''))),
                'instance' => $this->payload['instance'] ?? null,
                'from_me' => data_get($this->payload, 'data.key.fromMe') ?? data_get($this->payload, 'data.0.key.fromMe'),
                'data_is_list' => is_array($this->payload['data'] ?? null) && array_is_list($this->payload['data']),
                'status' => data_get($this->payload, 'data.status') ?? data_get($this->payload, 'data.0.status'),
            ]);

            return;
        }

        foreach ($messages as $message) {
            $processor->process($message);
        }
    }

    public function failed(?Throwable $exception): void
    {
        if ($this->logId) {
            app(EvolutionWebhookLogService::class)->markJobFailed(
                $this->logId,
                $exception?->getMessage(),
            );
        }

        Log::error('ProcessEvolutionInboundMessageJob falhou.', [
            'log_id' => $this->logId,
            'error' => $exception?->getMessage(),
            'event' => $this->payload['event'] ?? null,
        ]);
    }
}
