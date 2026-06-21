<?php

namespace App\Services;

use App\Enums\EvolutionRegistryEventDirection;
use App\Services\Webhooks\PhoneNumber;
use Illuminate\Support\Arr;

class EvolutionWebhookPayloadAnalyzer
{
    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{
     *     phone_normalized: ?string,
     *     remote_jid: ?string,
     *     contact_name: ?string,
     *     from_me: ?bool,
     *     direction: EvolutionRegistryEventDirection,
     *     summary: string,
     *     message_preview: ?string
     * }>
     */
    public function analyze(array $payload, ?string $routeSlug = null): array
    {
        $event = $this->normalizeEvent($payload['event'] ?? $routeSlug);

        if ($event === null) {
            return [[
                'phone_normalized' => null,
                'remote_jid' => null,
                'contact_name' => null,
                'from_me' => null,
                'direction' => EvolutionRegistryEventDirection::Unknown,
                'summary' => 'Webhook recebido sem evento identificado.',
                'message_preview' => null,
            ]];
        }

        if ($this->isBulkInstanceEvent($event)) {
            return [[
                'phone_normalized' => null,
                'remote_jid' => null,
                'contact_name' => null,
                'from_me' => null,
                'direction' => EvolutionRegistryEventDirection::System,
                'summary' => $this->bulkEventSummary($event, $payload),
                'message_preview' => null,
            ]];
        }

        if ($this->isInstanceSystemEvent($event)) {
            return [[
                'phone_normalized' => null,
                'remote_jid' => null,
                'contact_name' => null,
                'from_me' => null,
                'direction' => EvolutionRegistryEventDirection::System,
                'summary' => $this->systemEventSummary($event, $payload),
                'message_preview' => null,
            ]];
        }

        $entries = [];

        foreach ($this->extractDataItems($payload) as $item) {
            $parsed = $this->parseDataItem($event, $item, $payload);

            if ($parsed !== null) {
                $entries[] = $parsed;
            }
        }

        if ($entries !== []) {
            return $entries;
        }

        $fallbackPhone = $this->phoneFromSender($payload['sender'] ?? null);

        return [[
            'phone_normalized' => $fallbackPhone,
            'remote_jid' => is_string($payload['sender'] ?? null) ? $payload['sender'] : null,
            'contact_name' => filled($payload['pushName'] ?? null) ? trim((string) $payload['pushName']) : null,
            'from_me' => null,
            'direction' => $this->defaultDirectionForEvent($event),
            'summary' => $this->genericEventSummary($event, $payload),
            'message_preview' => $this->extractMessagePreviewFromItem(is_array($payload['data'] ?? null) ? $payload['data'] : []),
        ]];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{
     *     phone_normalized: ?string,
     *     remote_jid: ?string,
     *     contact_name: ?string,
     *     from_me: ?bool,
     *     direction: EvolutionRegistryEventDirection,
     *     summary: string,
     *     message_preview: ?string
     * }|null
     */
    private function parseDataItem(string $event, array $item, array $payload): ?array
    {
        $key = is_array($item['key'] ?? null) ? $item['key'] : [];
        $remoteJid = (string) ($key['remoteJidAlt'] ?? $key['remoteJid'] ?? $item['id'] ?? $item['remoteJid'] ?? '') ?: null;
        $phone = $remoteJid ? PhoneNumber::fromRemoteJid($remoteJid) : null;

        if (! $phone && filled($item['id'] ?? null)) {
            $phone = PhoneNumber::fromRemoteJid((string) $item['id']);
            $remoteJid ??= (string) $item['id'];
        }

        $fromMe = array_key_exists('fromMe', $key)
            ? (bool) $key['fromMe']
            : (array_key_exists('fromMe', $item) ? (bool) $item['fromMe'] : null);

        $contactName = filled($item['pushName'] ?? null)
            ? trim((string) $item['pushName'])
            : (filled($payload['pushName'] ?? null) ? trim((string) $payload['pushName']) : null);

        $messagePreview = $this->extractMessagePreviewFromItem($item);
        $direction = $this->resolveDirection($event, $fromMe, $item);
        $summary = $this->buildItemSummary($event, $direction, $item, $messagePreview);

        if ($phone === null && $direction === EvolutionRegistryEventDirection::System) {
            return [
                'phone_normalized' => null,
                'remote_jid' => $remoteJid,
                'contact_name' => $contactName,
                'from_me' => $fromMe,
                'direction' => $direction,
                'summary' => $summary,
                'message_preview' => $messagePreview,
            ];
        }

        if ($phone === null) {
            return null;
        }

        return [
            'phone_normalized' => $phone,
            'remote_jid' => $remoteJid,
            'contact_name' => $contactName,
            'from_me' => $fromMe,
            'direction' => $direction,
            'summary' => $summary,
            'message_preview' => $messagePreview,
        ];
    }

    private function resolveDirection(string $event, ?bool $fromMe, array $item): EvolutionRegistryEventDirection
    {
        if (in_array($event, ['SEND_MESSAGE'], true)) {
            return EvolutionRegistryEventDirection::Outbound;
        }

        if (in_array($event, [
            'MESSAGES_UPSERT',
            'MESSAGES_UPDATE',
            'MESSAGES_DELETE',
        ], true)) {
            if ($fromMe === true) {
                return EvolutionRegistryEventDirection::Outbound;
            }

            if ($fromMe === false) {
                return EvolutionRegistryEventDirection::Inbound;
            }
        }

        if (in_array($event, [
            'PRESENCE_UPDATE',
            'CONTACTS_UPSERT',
            'CONTACTS_UPDATE',
            'CHATS_UPSERT',
            'CHATS_UPDATE',
        ], true)) {
            return EvolutionRegistryEventDirection::System;
        }

        return $this->defaultDirectionForEvent($event);
    }

    private function defaultDirectionForEvent(string $event): EvolutionRegistryEventDirection
    {
        return match ($event) {
            'SEND_MESSAGE' => EvolutionRegistryEventDirection::Outbound,
            'MESSAGES_UPSERT', 'MESSAGES_UPDATE', 'MESSAGES_DELETE' => EvolutionRegistryEventDirection::Unknown,
            'CONNECTION_UPDATE', 'QRCODE_UPDATED', 'APPLICATION_STARTUP', 'NEW_TOKEN' => EvolutionRegistryEventDirection::System,
            default => EvolutionRegistryEventDirection::Unknown,
        };
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function buildItemSummary(string $event, EvolutionRegistryEventDirection $direction, array $item, ?string $messagePreview): string
    {
        $eventLabel = str_replace('_', ' ', $event);
        $status = filled($item['status'] ?? null) ? (string) $item['status'] : null;

        $base = match ($direction) {
            EvolutionRegistryEventDirection::Inbound => 'Mensagem recebida',
            EvolutionRegistryEventDirection::Outbound => 'Mensagem enviada',
            EvolutionRegistryEventDirection::System => 'Evento de instância/contato',
            EvolutionRegistryEventDirection::Unknown => 'Evento '.$eventLabel,
        };

        if ($event === 'PRESENCE_UPDATE') {
            $presence = Arr::get($item, 'presences.lastKnownPresence')
                ?? Arr::get($item, 'lastKnownPresence')
                ?? Arr::get($item, 'presence');

            return 'Presença atualizada'.(filled($presence) ? ': '.$presence : '');
        }

        if ($event === 'CONNECTION_UPDATE') {
            $state = (string) ($item['state'] ?? $item['status'] ?? '');

            return 'Conexão WhatsApp'.(filled($state) ? ': '.$state : '');
        }

        if ($status) {
            $base .= " ({$status})";
        }

        if (filled($messagePreview)) {
            return $base.': "'.mb_substr($messagePreview, 0, 120).'"';
        }

        return $base.' — '.$eventLabel;
    }

    private function systemEventSummary(string $event, array $payload): string
    {
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        return match ($event) {
            'CONNECTION_UPDATE' => 'Conexão WhatsApp: '.((string) ($data['state'] ?? $data['status'] ?? 'atualizada')),
            'QRCODE_UPDATED' => 'QR Code atualizado para pareamento.',
            'APPLICATION_STARTUP' => 'Evolution API iniciada.',
            'NEW_TOKEN' => 'Token JWT renovado.',
            default => str_replace('_', ' ', $event),
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function bulkEventSummary(string $event, array $payload): string
    {
        $data = $payload['data'] ?? null;
        $count = is_array($data) ? count($data) : 0;

        return match ($event) {
            'MESSAGES_SET' => "Sincronização inicial de mensagens ({$count} itens).",
            'CONTACTS_SET' => "Sincronização inicial de contatos ({$count} itens).",
            'CHATS_SET' => "Sincronização inicial de chats ({$count} itens).",
            default => str_replace('_', ' ', $event)." ({$count} itens).",
        };
    }

    private function genericEventSummary(string $event, array $payload): string
    {
        return str_replace('_', ' ', $event).' — webhook registrado.';
    }

    private function isBulkInstanceEvent(string $event): bool
    {
        return in_array($event, ['MESSAGES_SET', 'CONTACTS_SET', 'CHATS_SET'], true);
    }

    private function isInstanceSystemEvent(string $event): bool
    {
        return in_array($event, [
            'CONNECTION_UPDATE',
            'QRCODE_UPDATED',
            'APPLICATION_STARTUP',
            'NEW_TOKEN',
        ], true);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    private function extractDataItems(array $payload): array
    {
        $data = $payload['data'] ?? null;

        if (! is_array($data)) {
            return [];
        }

        if (array_is_list($data)) {
            return array_values(array_filter($data, fn (mixed $item): bool => is_array($item)));
        }

        return [$data];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function extractMessagePreviewFromItem(array $item): ?string
    {
        $message = is_array($item['message'] ?? null) ? $item['message'] : [];
        $text = $message['conversation']
            ?? Arr::get($message, 'extendedTextMessage.text')
            ?? Arr::get($message, 'buttonsResponseMessage.selectedDisplayText')
            ?? Arr::get($message, 'listResponseMessage.title')
            ?? Arr::get($message, 'imageMessage.caption')
            ?? Arr::get($message, 'videoMessage.caption')
            ?? Arr::get($message, 'documentMessage.caption');

        if (filled($text)) {
            return mb_substr(trim((string) $text), 0, 500);
        }

        if (isset($message['audioMessage'])) {
            return '[Áudio]';
        }

        if (isset($message['imageMessage'])) {
            return '[Imagem]';
        }

        if (isset($message['videoMessage'])) {
            return '[Vídeo]';
        }

        if (isset($message['documentMessage'])) {
            return '[Documento]';
        }

        return null;
    }

    private function phoneFromSender(mixed $sender): ?string
    {
        if (! is_string($sender) || blank($sender)) {
            return null;
        }

        return PhoneNumber::fromRemoteJid($sender);
    }

    private function normalizeEvent(mixed $event): ?string
    {
        if (! filled($event)) {
            return null;
        }

        return strtoupper(str_replace(['.', '-'], '_', (string) $event));
    }
}
