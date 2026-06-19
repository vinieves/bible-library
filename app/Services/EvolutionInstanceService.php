<?php

namespace App\Services;

use App\DataTransferObjects\EvolutionInstanceSummary;
use App\Support\IntegrationSettings;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class EvolutionInstanceService
{
    /**
     * @return list<EvolutionInstanceSummary>
     */
    public function fetchAll(): array
    {
        $response = $this->request('GET', '/instance/fetchInstances');

        if (! $response->successful()) {
            $this->fail('listar instâncias', $response);
        }

        $body = $response->json();

        if (! is_array($body)) {
            return [];
        }

        return $this->normalizeInstancesList($body);
    }

    /**
     * @return array{base64: ?string, code: ?string, pairingCode: ?string, count: ?int, raw: array}
     */
    public function connect(string $instanceName): array
    {
        $response = $this->request('GET', '/instance/connect/'.rawurlencode($instanceName));

        if (! $response->successful()) {
            $this->fail("conectar instância {$instanceName}", $response);
        }

        $body = $response->json() ?? [];
        $qrcode = is_array($body['qrcode'] ?? null) ? $body['qrcode'] : $body;

        return [
            'base64' => $qrcode['base64'] ?? $body['base64'] ?? null,
            'code' => $qrcode['code'] ?? $body['code'] ?? null,
            'pairingCode' => $qrcode['pairingCode'] ?? $body['pairingCode'] ?? null,
            'count' => isset($qrcode['count']) ? (int) $qrcode['count'] : null,
            'raw' => $body,
        ];
    }

    /**
     * @return array{instanceName: string, state: string, raw: array}
     */
    public function create(string $instanceName, bool $generateQrCode = true): array
    {
        $response = $this->request('POST', '/instance/create', [
            'instanceName' => $instanceName,
            'qrcode' => $generateQrCode,
            'integration' => 'WHATSAPP-BAILEYS',
        ]);

        if (! $response->successful()) {
            $this->fail("criar instância {$instanceName}", $response);
        }

        $body = $response->json() ?? [];
        $instance = is_array($body['instance'] ?? null) ? $body['instance'] : $body;
        $qrcode = is_array($body['qrcode'] ?? null) ? $body['qrcode'] : [];

        return [
            'instanceName' => (string) ($instance['instanceName'] ?? $instanceName),
            'state' => (string) ($instance['status'] ?? $instance['state'] ?? 'connecting'),
            'base64' => $qrcode['base64'] ?? $body['base64'] ?? null,
            'code' => $qrcode['code'] ?? $body['code'] ?? null,
            'pairingCode' => $qrcode['pairingCode'] ?? $body['pairingCode'] ?? null,
            'raw' => $body,
        ];
    }

    public function connectionState(string $instanceName): string
    {
        $response = $this->request('GET', '/instance/connectionState/'.rawurlencode($instanceName));

        if (! $response->successful()) {
            $this->fail("consultar estado de {$instanceName}", $response);
        }

        $body = $response->json() ?? [];
        $instance = is_array($body['instance'] ?? null) ? $body['instance'] : $body;

        return (string) ($instance['state'] ?? $body['state'] ?? 'unknown');
    }

    public function logout(string $instanceName): void
    {
        $response = $this->request('DELETE', '/instance/logout/'.rawurlencode($instanceName));

        if (! $response->successful()) {
            $this->fail("desconectar instância {$instanceName}", $response);
        }
    }

    public function restart(string $instanceName): void
    {
        $response = $this->request('POST', '/instance/restart/'.rawurlencode($instanceName));

        if (! $response->successful()) {
            $this->fail("reiniciar instância {$instanceName}", $response);
        }
    }

    public function delete(string $instanceName): void
    {
        $response = $this->request('DELETE', '/instance/delete/'.rawurlencode($instanceName));

        if (! $response->successful()) {
            $this->fail("excluir instância {$instanceName}", $response);
        }
    }

    /**
     * @return list<EvolutionInstanceSummary>
     */
    private function normalizeInstancesList(array $body): array
    {
        $items = Arr::isList($body) ? $body : [$body];
        $instances = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $summary = $this->normalizeInstanceItem($item);

            if ($summary) {
                $instances[$summary->name] = $summary;
            }
        }

        return array_values($instances);
    }

    private function normalizeInstanceItem(array $item): ?EvolutionInstanceSummary
    {
        $instance = is_array($item['instance'] ?? null) ? $item['instance'] : $item;

        $name = (string) (
            $instance['instanceName']
            ?? $instance['name']
            ?? $item['instanceName']
            ?? $item['name']
            ?? ''
        );

        if (blank($name)) {
            return null;
        }

        $state = (string) (
            $item['state']
            ?? $instance['state']
            ?? $instance['status']
            ?? $instance['connectionStatus']
            ?? $item['connectionStatus']
            ?? 'unknown'
        );

        return new EvolutionInstanceSummary(
            name: $name,
            state: strtolower($state),
            instanceId: filled($instance['instanceId'] ?? null) ? (string) $instance['instanceId'] : null,
            profileName: filled($instance['profileName'] ?? $item['profileName'] ?? null)
                ? (string) ($instance['profileName'] ?? $item['profileName'])
                : null,
            ownerJid: filled($instance['ownerJid'] ?? $item['ownerJid'] ?? null)
                ? (string) ($instance['ownerJid'] ?? $item['ownerJid'])
                : null,
        );
    }

    private function request(string $method, string $path, ?array $body = null): Response
    {
        $baseUrl = IntegrationSettings::evolutionBaseUrl();
        $apiKey = IntegrationSettings::evolutionApiKey();

        if (! $baseUrl || ! $apiKey) {
            throw new RuntimeException('Evolution API não configurada. Configure URL e API Key em Integrações API.');
        }

        $url = rtrim($baseUrl, '/').$path;

        $pending = Http::timeout(30)->withHeaders([
            'apikey' => $apiKey,
            'Content-Type' => 'application/json',
        ]);

        return match (strtoupper($method)) {
            'GET' => $pending->get($url),
            'POST' => $pending->post($url, $body ?? []),
            'DELETE' => $pending->delete($url, $body ?? []),
            default => throw new RuntimeException("Método HTTP não suportado: {$method}"),
        };
    }

    private function fail(string $action, Response $response): never
    {
        Log::error("Evolution API falhou ao {$action}.", [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        throw new RuntimeException(
            "Evolution API falhou ao {$action} (HTTP {$response->status()}): ".$response->body()
        );
    }
}
