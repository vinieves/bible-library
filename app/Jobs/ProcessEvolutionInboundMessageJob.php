<?php

namespace App\Jobs;

use App\DataTransferObjects\EvolutionInboundMessageData;
use App\Services\EvolutionInboundMessageProcessor;
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
    public function __construct(private readonly array $payload) {}

    public function handle(EvolutionInboundMessageProcessor $processor): void
    {
        $messages = EvolutionInboundMessageData::collectFromPayload($this->payload);

        if ($messages === []) {
            Log::warning('Webhook Evolution ignorado: evento/payload inválido para mensagem recebida.', [
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
        Log::error('ProcessEvolutionInboundMessageJob falhou.', [
            'error' => $exception?->getMessage(),
            'event' => $this->payload['event'] ?? null,
        ]);
    }
}
