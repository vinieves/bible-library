<?php

namespace App\Http\Middleware;

use App\Enums\WebhookPlatform;
use App\Support\IntegrationSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookSecret
{
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

        return response()->json([
            'message' => 'Não autorizado.',
        ], Response::HTTP_UNAUTHORIZED);
    }

    private function isValidHotmartRequest(Request $request): bool
    {
        $configuredHottok = IntegrationSettings::hotmartHottok();
        $payloadHottok = trim((string) $request->input('hottok', ''));

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
