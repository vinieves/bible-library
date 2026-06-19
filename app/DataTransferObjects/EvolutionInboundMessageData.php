<?php

namespace App\DataTransferObjects;

readonly class EvolutionInboundMessageData
{
    public function __construct(
        public string $event,
        public string $instance,
        public string $remoteJid,
        public string $phoneNormalized,
        public bool $fromMe,
        public ?string $messageId,
        public ?string $pushName,
        public array $rawPayload,
    ) {}

    public static function fromPayload(array $payload): ?self
    {
        $event = strtoupper(str_replace('.', '_', (string) ($payload['event'] ?? '')));

        if ($event !== 'MESSAGES_UPSERT') {
            return null;
        }

        $data = $payload['data'] ?? [];

        if (! is_array($data)) {
            return null;
        }

        $key = $data['key'] ?? [];

        if (! is_array($key)) {
            return null;
        }

        if ((bool) ($key['fromMe'] ?? false)) {
            return null;
        }

        $remoteJid = (string) ($key['remoteJidAlt'] ?? $key['remoteJid'] ?? '');

        $phone = \App\Services\Webhooks\PhoneNumber::fromRemoteJid($remoteJid);

        if (! $phone) {
            return null;
        }

        if (self::isProtocolOnlyMessage($data)) {
            return null;
        }

        return new self(
            event: $event,
            instance: (string) ($payload['instance'] ?? ''),
            remoteJid: $remoteJid,
            phoneNormalized: $phone,
            fromMe: false,
            messageId: filled($key['id'] ?? null) ? (string) $key['id'] : null,
            pushName: filled($data['pushName'] ?? null) ? (string) $data['pushName'] : null,
            rawPayload: $payload,
        );
    }

    private static function isProtocolOnlyMessage(array $data): bool
    {
        $message = $data['message'] ?? [];

        if (! is_array($message)) {
            return true;
        }

        if (isset($message['protocolMessage'])) {
            return true;
        }

        $hasContent = filled($message['conversation'] ?? null)
            || filled($message['extendedTextMessage']['text'] ?? null)
            || filled($message['imageMessage'] ?? null)
            || filled($message['videoMessage'] ?? null)
            || filled($message['audioMessage'] ?? null)
            || filled($message['documentMessage'] ?? null)
            || filled($message['stickerMessage'] ?? null)
            || filled($message['buttonsResponseMessage'] ?? null)
            || filled($message['listResponseMessage'] ?? null);

        return ! $hasContent;
    }
}
