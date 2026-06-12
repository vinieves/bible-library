<?php

namespace App\Http\Middleware;

use App\Enums\WebhookLogStatus;
use App\Enums\WebhookPlatform;
use App\Services\WebhookLogService;
use App\Support\IntegrationSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookSecret
{
    public function __construct(
        private readonly WebhookLogService $webhookLogService,
    ) {}

        public function handle(Request $request, Closure $next): Response
    {
        $resolvedPlatform = WebhookPlatform::tryFromRoute($request->route('platform'));

        if (! $resolvedPlatform) {
            abort(404, 'Plataforma de webhook não encontrada.');
        }

        if ($resolvedPlatform === WebhookPlatform::Hotmart) {
            if ($this->isValidHotmartRequest($request)) {
                return $next($request);
            }
        }

        if ($this->isValidSecretHeader($request)) {
            return $next($request);
        }

        $this->webhookLogService->log(
            request: $request,
            platform: $resolvedPlatform,
            status: WebhookLogStatus::Unauthorized,
            message: $this->unauthorizedMessage($resolvedPlatform, $request),
            httpStatus: Response::HTTP_UNAUTHORIZED,
            response: ['message' => 'Não autorizado.'],
        );

        return response()->json([
            'message' => 'Não autorizado.',
        ], Response::HTTP_UNAUTHORIZED);
    }

    private function unauthorizedMessage(WebhookPlatform $platform, Request $request): string
    {
        if ($platform === WebhookPlatform::Hotmart && blank(IntegrationSettings::hotmartHottok())) {
            return 'Hottok da Hotmart não configurado no painel admin.';
        }

        if ($platform === WebhookPlatform::Hotmart && blank($this->resolveHotmartHottok($request))) {
            return 'Hottok ausente no payload ou headers da Hotmart.';
        }

        return 'Não autorizado.';
    }

    private function resolveHotmartHottok(Request $request): string
    {
        foreach ([
            $request->input('hottok'),
            $request->header('X-HOTMART-HOTTOK'),
            $request->header('X-Hottok'),
        ] as $candidate) {
            $value = trim((string) $candidate);

            if (filled($value)) {
                return $value;
            }
        }

        if ($request->isJson()) {
            return trim((string) $request->json('hottok', ''));
        }

        return '';
    }

    private function isValidHotmartRequest(Request $request): bool
    {
        $configuredHottok = trim((string) IntegrationSettings::hotmartHottok());
        $payloadHottok = $this->resolveHotmartHottok($request);

        return filled($configuredHottok)
            && filled($payloadHottok)
            && hash_equals($configuredHottok, $payloadHottok);
    }

    private function isValidSecretHeader(Request $request): bool
    {
        $configuredSecret = IntegrationSettings::webhookSecret();
        $providedSecret = trim((string) $request->header('X-Webhook-Secret', ''));

        return filled($configuredSecret)
            && filled($providedSecret)
            && hash_equals($configuredSecret, $providedSecret);
    }
}
