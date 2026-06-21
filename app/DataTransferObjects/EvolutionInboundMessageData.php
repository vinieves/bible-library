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

    /**
     * @return list<self>
     */
    public static function collectFromPayload(array $payload): array
    {
        $event = self::normalizeEvent($payload['event'] ?? '');

        if ($event !== 'MESSAGES_UPSERT') {
            return [];
        }

        $instance = (string) ($payload['instance'] ?? '');
        $messages = [];

        foreach (self::extractDataItems($payload) as $item) {
            $parsed = self::fromDataItem($payload, $item, $instance, $event);

            if ($parsed) {
                $messages[] = $parsed;
            }
        }

        return $messages;
    }

    public static function fromPayload(array $payload): ?self
    {
        return self::collectFromPayload($payload)[0] ?? null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function extractDataItems(array $payload): array
    {
        $data = $payload['data'] ?? null;

        if (! is_array($data)) {
            return [];
        }

        if (array_is_list($data)) {
            return array_values(array_filter(
                $data,
                fn (mixed $item): bool => is_array($item) && isset($item['key']),
            ));
        }

        if (isset($data['key']) || isset($data['message'])) {
            return [$data];
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $data
     */
    private static function fromDataItem(array $payload, array $data, string $instance, string $event): ?self
    {
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
            $phone = \App\Services\Webhooks\PhoneNumber::fromRemoteJid(
                (string) ($key['remoteJid'] ?? '')
            );
        }

        if (! $phone) {
            return null;
        }

        if (self::isProtocolOnlyMessage($data)) {
            return null;
        }

        return new self(
            event: $event,
            instance: $instance,
            remoteJid: $remoteJid,
            phoneNormalized: $phone,
            fromMe: false,
            messageId: filled($key['id'] ?? null) ? (string) $key['id'] : null,
            pushName: filled($data['pushName'] ?? null) ? (string) $data['pushName'] : null,
            rawPayload: $payload,
        );
    }

    private static function normalizeEvent(mixed $event): string
    {
        return strtoupper(str_replace(['.', '-'], '_', (string) $event));
    }

    /**
     * @param  array<string, mixed>  $data
     */
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
            || filled($message['listResponseMessage'] ?? null)
            || filled($message['templateButtonReplyMessage'] ?? null);

        return ! $hasContent;
    }
}
