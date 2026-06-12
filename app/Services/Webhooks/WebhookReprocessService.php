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

        $request = Request::create(
            '/api/webhooks/'.$platform->value,
            'POST',
            $this->preparePayload($platform, $payload),
        );

        $request->headers->set('Content-Type', 'application/json');

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
