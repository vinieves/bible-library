<?php

namespace App\Services\Webhooks;

use App\Enums\WebhookPlatform;
use App\Models\WebhookLog;
use App\Support\IntegrationSettings;
use Illuminate\Http\Request;
use InvalidArgumentException;

class WebhookReprocessService
{
    public function __construct(
        private readonly IncomingWebhookProcessor $processor,
    ) {}

    public function reprocess(WebhookLog $source): WebhookLog
    {
        $platform = WebhookPlatform::tryFrom($source->platform);

        if (! $platform) {
            throw new InvalidArgumentException('Plataforma de webhook inválida.');
        }

        $payload = $source->payload;

        if (! is_array($payload) || $payload === []) {
            throw new InvalidArgumentException('Este log não possui payload salvo para reprocessar.');
        }

        if ($platform === WebhookPlatform::Hotmart && blank($payload['event'] ?? null)) {
            throw new InvalidArgumentException(
                'O payload salvo está inválido ou corrompido. Reprocesse um log que contenha o JSON original da Hotmart.'
            );
        }

        $request = Request::create(
            '/api/webhooks/'.$platform->value,
            'POST',
            $this->preparePayload($platform, $payload),
        );

        $request->headers->set('Content-Type', 'application/json');
        $request->server->set('REMOTE_ADDR', 'reprocess');

        return $this->processor->handle($request, $platform)['log'];
    }

    private function preparePayload(WebhookPlatform $platform, array $payload): array
    {
        if ($platform === WebhookPlatform::Hotmart) {
            if (($payload['hottok'] ?? null) === '***' || blank($payload['hottok'] ?? null)) {
                unset($payload['hottok']);
            }

            $hottok = IntegrationSettings::hotmartHottok();

            if (filled($hottok)) {
                $payload['hottok'] = $hottok;
            }
        }

        return $payload;
    }
}
