<?php

namespace App\Services;

use App\Support\IntegrationSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class EvolutionWebhookRegistrationService
{
    /**
     * @return array{success: bool, http_status: int, body: array|string|null, message: string}
     */
    public function registerInstanceWebhook(?string $instanceName = null): array
    {
        $baseUrl = IntegrationSettings::evolutionBaseUrl();
        $instance = $instanceName ?: IntegrationSettings::evolutionInstanceForFlows();
        $apiKey = IntegrationSettings::evolutionApiKey();
        $webhookUrl = IntegrationSettings::evolutionWebhookUrl();

        if (! $baseUrl || ! $instance || ! $apiKey) {
            throw new RuntimeException('Evolution API não configurada no painel admin.');
        }

        $payloads = [
            [
                'endpoint' => "{$baseUrl}/webhook/set/{$instance}",
                'body' => [
                    'enabled' => true,
                    'url' => $webhookUrl,
                    'webhookByEvents' => false,
                    'webhook_base64' => false,
                    'events' => ['MESSAGES_UPSERT'],
                ],
            ],
            [
                'endpoint' => "{$baseUrl}/webhook/set/{$instance}",
                'body' => [
                    'webhook' => [
                        'enabled' => true,
                        'url' => $webhookUrl,
                        'webhookByEvents' => false,
                        'webhook_base64' => false,
                        'events' => ['MESSAGES_UPSERT'],
                    ],
                ],
            ],
        ];

        $lastResponse = null;

        foreach ($payloads as $attempt) {
            $response = Http::timeout(20)
                ->withHeaders([
                    'apikey' => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($attempt['endpoint'], $attempt['body']);

            $lastResponse = $response;

            if ($response->successful()) {
                Log::info('Webhook Evolution registrado com sucesso.', [
                    'instance' => $instance,
                    'url' => $webhookUrl,
                    'endpoint' => $attempt['endpoint'],
                ]);

                return [
                    'success' => true,
                    'http_status' => $response->status(),
                    'body' => $response->json(),
                    'message' => 'Webhook MESSAGES_UPSERT registrado na Evolution.',
                ];
            }
        }

        Log::error('Falha ao registrar webhook Evolution.', [
            'instance' => $instance,
            'url' => $webhookUrl,
            'status' => $lastResponse?->status(),
            'body' => $lastResponse?->body(),
        ]);

        return [
            'success' => false,
            'http_status' => $lastResponse?->status() ?? 0,
            'body' => $lastResponse?->json() ?? $lastResponse?->body(),
            'message' => 'Não foi possível registrar o webhook na Evolution. Verifique os logs.',
        ];
    }

    /**
     * @return array{enabled: bool, url: ?string, events: array<int, string>}|null
     */
    public function findInstanceWebhook(?string $instanceName = null): ?array
    {
        $baseUrl = IntegrationSettings::evolutionBaseUrl();
        $instance = $instanceName ?: IntegrationSettings::evolutionInstanceForFlows();
        $apiKey = IntegrationSettings::evolutionApiKey();

        if (! $baseUrl || ! $instance || ! $apiKey) {
            return null;
        }

        $response = Http::timeout(15)
            ->withHeaders(['apikey' => $apiKey])
            ->get("{$baseUrl}/webhook/find/{$instance}");

        if (! $response->successful()) {
            return null;
        }

        $body = $response->json();

        if (! is_array($body)) {
            return null;
        }

        return [
            'enabled' => (bool) ($body['enabled'] ?? false),
            'url' => filled($body['url'] ?? null) ? (string) $body['url'] : null,
            'events' => is_array($body['events'] ?? null) ? $body['events'] : [],
        ];
    }
}
