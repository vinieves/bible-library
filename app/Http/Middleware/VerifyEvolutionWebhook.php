<?php

namespace App\Http\Middleware;

use App\Support\IntegrationSettings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyEvolutionWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isAuthorized($request)) {
            return $next($request);
        }

        Log::warning('Webhook Evolution rejeitado (401).', [
            'event' => $request->input('event'),
            'instance' => $request->input('instance'),
            'has_apikey_body' => filled($request->input('apikey')),
            'has_apikey_header' => filled($request->header('apikey')),
        ]);

        return response()->json([
            'message' => 'Não autorizado.',
        ], Response::HTTP_UNAUTHORIZED);
    }

    private function isAuthorized(Request $request): bool
    {
        $configuredApiKey = trim((string) IntegrationSettings::evolutionApiKey());
        $providedApiKey = $this->resolveProvidedApiKey($request);

        if (filled($configuredApiKey) && filled($providedApiKey) && hash_equals($configuredApiKey, $providedApiKey)) {
            return true;
        }

        $configuredSecret = IntegrationSettings::webhookSecret();
        $providedSecret = trim((string) $request->header('X-Webhook-Secret', ''));

        if (filled($configuredSecret) && filled($providedSecret) && hash_equals($configuredSecret, $providedSecret)) {
            return true;
        }

        if ($this->isTrustedEvolutionInstancePayload($request)) {
            Log::info('Webhook Evolution aceito por instância confiável (sem apikey no payload).', [
                'event' => $request->input('event'),
                'instance' => $request->input('instance'),
            ]);

            return true;
        }

        return false;
    }

    private function resolveProvidedApiKey(Request $request): string
    {
        $authorization = trim((string) $request->header('Authorization', ''));

        if (str_starts_with(strtolower($authorization), 'bearer ')) {
            return trim(substr($authorization, 7));
        }

        return trim((string) (
            $request->input('apikey')
            ?? $request->input('apiKey')
            ?? $request->header('apikey')
            ?? $request->header('Apikey')
            ?? ''
        ));
    }

    private function isTrustedEvolutionInstancePayload(Request $request): bool
    {
        $configuredInstance = IntegrationSettings::evolutionInstance();
        $payloadInstance = trim((string) $request->input('instance', ''));
        $event = strtoupper(str_replace('.', '_', (string) $request->input('event', '')));

        if (blank($configuredInstance) || blank($payloadInstance) || blank($event)) {
            return false;
        }

        if ($payloadInstance !== $configuredInstance) {
            return false;
        }

        return $request->has('data') || $request->has('date_time') || $request->has('server_url');
    }
}
