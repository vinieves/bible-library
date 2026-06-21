<?php

namespace App\Services;

use App\DataTransferObjects\EvolutionInboundMessageData;
use App\Enums\EvolutionWebhookLogStatus;
use App\Models\EvolutionWebhookLog;
use App\Services\Webhooks\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class EvolutionWebhookLogService
{
    public function recordIncoming(Request $request, ?string $routeSlug = null): EvolutionWebhookLog
    {
        $payload = $this->sanitizePayload($request->all());
        $extracted = $this->extractSummary($payload);

        $log = EvolutionWebhookLog::query()->create([
            'event' => $extracted['event'],
            'instance' => $extracted['instance'],
            'route_slug' => $routeSlug,
            'phone_normalized' => $extracted['phone'],
            'remote_jid' => $extracted['remote_jid'],
            'from_me' => $extracted['from_me'],
            'message_preview' => $extracted['message_preview'],
            'inbound_count' => 0,
            'processing_status' => EvolutionWebhookLogStatus::Received,
            'processing_message' => 'Webhook recebido e enfileirado para processamento.',
            'payload' => $payload ?: null,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        app(EvolutionRegistryService::class)->recordFromWebhookLog($log);

        return $log;
    }

    public function recordUnauthorized(Request $request, ?string $routeSlug = null): EvolutionWebhookLog
    {
        $payload = $this->sanitizePayload($request->all());
        $extracted = $this->extractSummary($payload);

        return EvolutionWebhookLog::query()->create([
            'event' => $extracted['event'],
            'instance' => $extracted['instance'],
            'route_slug' => $routeSlug,
            'phone_normalized' => $extracted['phone'],
            'remote_jid' => $extracted['remote_jid'],
            'from_me' => $extracted['from_me'],
            'message_preview' => $extracted['message_preview'],
            'inbound_count' => 0,
            'processing_status' => EvolutionWebhookLogStatus::Unauthorized,
            'processing_message' => 'Webhook rejeitado: apikey/secret inválidos ou instância não confiável.',
            'payload' => $payload ?: null,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);
    }

    /**
     * @param  list<EvolutionInboundMessageData>  $messages
     */
    public function markJobResult(int $logId, array $messages, bool $wasIgnored): void
    {
        $log = EvolutionWebhookLog::query()->find($logId);

        if (! $log) {
            return;
        }

        if ($messages === []) {
            $log->update([
                'processing_status' => EvolutionWebhookLogStatus::Ignored,
                'processing_message' => $wasIgnored
                    ? 'Payload recebido, mas nenhuma mensagem inbound válida (evento diferente, fromMe=true, protocolo ou formato inválido).'
                    : 'Nenhuma mensagem inbound válida encontrada.',
                'inbound_count' => 0,
            ]);

            return;
        }

        $first = $messages[0];

        $log->update([
            'processing_status' => EvolutionWebhookLogStatus::Parsed,
            'processing_message' => count($messages) > 1
                ? count($messages).' mensagens inbound válidas processadas.'
                : 'Mensagem inbound válida processada.',
            'inbound_count' => count($messages),
            'phone_normalized' => $first->phoneNormalized,
            'remote_jid' => $first->remoteJid,
            'from_me' => false,
            'message_preview' => $this->extractMessagePreview($log->payload ?? []),
        ]);
    }

    public function markJobFailed(int $logId, ?string $error): void
    {
        $log = EvolutionWebhookLog::query()->find($logId);

        if (! $log) {
            return;
        }

        $log->update([
            'processing_status' => EvolutionWebhookLogStatus::Ignored,
            'processing_message' => 'Falha ao processar job: '.($error ?? 'erro desconhecido'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{event: ?string, instance: ?string, phone: ?string, remote_jid: ?string, from_me: ?bool, message_preview: ?string}
     */
    private function extractSummary(array $payload): array
    {
        $event = filled($payload['event'] ?? null)
            ? strtoupper(str_replace(['.', '-'], '_', (string) $payload['event']))
            : null;

        $instance = filled($payload['instance'] ?? null) ? (string) $payload['instance'] : null;
        $item = $this->firstDataItem($payload);

        $remoteJid = filled($item['remote_jid'] ?? null) ? (string) $item['remote_jid'] : null;
        $phone = null;

        if ($remoteJid) {
            $phone = PhoneNumber::fromRemoteJid($remoteJid);
        }

        return [
            'event' => $event,
            'instance' => $instance,
            'phone' => $phone,
            'remote_jid' => $remoteJid,
            'from_me' => $item['from_me'] ?? null,
            'message_preview' => $this->extractMessagePreview($payload),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{remote_jid: ?string, from_me: ?bool}
     */
    private function firstDataItem(array $payload): array
    {
        $data = $payload['data'] ?? null;

        if (! is_array($data)) {
            return ['remote_jid' => null, 'from_me' => null];
        }

        $item = array_is_list($data) ? ($data[0] ?? null) : $data;

        if (! is_array($item)) {
            return ['remote_jid' => null, 'from_me' => null];
        }

        $key = is_array($item['key'] ?? null) ? $item['key'] : [];

        return [
            'remote_jid' => (string) ($key['remoteJidAlt'] ?? $key['remoteJid'] ?? '') ?: null,
            'from_me' => array_key_exists('fromMe', $key) ? (bool) $key['fromMe'] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractMessagePreview(array $payload): ?string
    {
        $data = $payload['data'] ?? null;

        if (! is_array($data)) {
            return null;
        }

        $items = array_is_list($data) ? $data : [$data];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $message = is_array($item['message'] ?? null) ? $item['message'] : [];
            $text = $message['conversation']
                ?? Arr::get($message, 'extendedTextMessage.text')
                ?? Arr::get($message, 'buttonsResponseMessage.selectedDisplayText')
                ?? Arr::get($message, 'listResponseMessage.title');

            if (filled($text)) {
                return mb_substr(trim((string) $text), 0, 500);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function sanitizePayload(array $payload): array
    {
        foreach (['apikey', 'apiKey'] as $key) {
            if (isset($payload[$key])) {
                $payload[$key] = '***';
            }
        }

        return $payload;
    }
}
