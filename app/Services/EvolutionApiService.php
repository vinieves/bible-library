<?php

namespace App\Services;

use App\Support\IntegrationSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class EvolutionApiService
{
    public function sendText(string $phone, string $message): array
    {
        $baseUrl = IntegrationSettings::evolutionBaseUrl();
        $instance = IntegrationSettings::evolutionInstance();
        $apiKey = IntegrationSettings::evolutionApiKey();

        if (! $baseUrl || ! $instance || ! $apiKey) {
            throw new RuntimeException('Evolution API não configurada no painel admin.');
        }

        $endpoint = "{$baseUrl}/message/sendText/{$instance}";

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
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException(
                'Evolution API retornou erro HTTP '.$response->status().': '.$response->body()
            );
        }

        return $response->json() ?? ['status' => 'sent'];
    }
}
