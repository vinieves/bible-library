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
        $message = EvolutionInboundMessageData::fromPayload($this->payload);

        if (! $message) {
            Log::warning('Webhook Evolution ignorado: evento/payload inválido para primeira mensagem.', [
                'event' => $this->payload['event'] ?? null,
                'instance' => $this->payload['instance'] ?? null,
                'from_me' => data_get($this->payload, 'data.key.fromMe'),
                'status' => data_get($this->payload, 'data.status'),
            ]);

            return;
        }

        $processor->process($message);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('ProcessEvolutionInboundMessageJob falhou.', [
            'error' => $exception?->getMessage(),
            'event' => $this->payload['event'] ?? null,
        ]);
    }
}
