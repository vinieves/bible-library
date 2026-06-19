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
        $providedApiKey = trim((string) (
            $request->input('apikey')
            ?? $request->header('apikey')
            ?? $request->header('Apikey')
            ?? ''
        ));

        if (filled($configuredApiKey) && filled($providedApiKey) && hash_equals($configuredApiKey, $providedApiKey)) {
            return true;
        }

        $configuredSecret = IntegrationSettings::webhookSecret();
        $providedSecret = trim((string) $request->header('X-Webhook-Secret', ''));

        return filled($configuredSecret)
            && filled($providedSecret)
            && hash_equals($configuredSecret, $providedSecret);
    }
}
