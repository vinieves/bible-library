<?php

namespace App\Services;

use App\Support\IntegrationSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class EvolutionApiService
{
    public function sendText(string $phone, string $message, ?string $instanceName = null): array
    {
        $baseUrl = IntegrationSettings::evolutionBaseUrl();
        $instance = $instanceName ?: IntegrationSettings::evolutionInstanceForMessages();
        $apiKey = IntegrationSettings::evolutionApiKey();

        if (! $baseUrl || ! $instance || ! $apiKey) {
            throw new RuntimeException('Evolution API não configurada no painel admin.');
        }

        $endpoint = rtrim($baseUrl, '/')."/message/sendText/{$instance}";

        $response = Http::timeout(20)
            ->withHeaders([
                'apikey' => $apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post($endpoint, [
                'number' => $phone,
                'text' => $message,
            ]);

        if (! $response->successful()) {
            Log::error('Evolution API falhou ao enviar mensagem.', [
                'phone' => $phone,
                'instance' => $instance,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException(
                'Evolution API retornou erro HTTP '.$response->status().': '.$response->body()
            );
        }

        return [
            'http_status' => $response->status(),
            'body' => $response->json() ?? [],
            'instance' => $instance,
        ];
    }
}
