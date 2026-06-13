<?php

namespace App\Services;

use App\Enums\WebhookLogStatus;
use App\Enums\WebhookPlatform;
use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class WebhookLogService
{
    public function log(
        Request $request,
        WebhookPlatform $platform,
        WebhookLogStatus $status,
        ?string $message = null,
        ?int $httpStatus = null,
        ?array $response = null,
        ?int $purchaseId = null,
    ): WebhookLog {
        $payload = $this->sanitizePayload($request->all());
        $extracted = $this->extractFields($platform, $payload);

        return WebhookLog::query()->create([
            'platform' => $platform->value,
            'event' => $extracted['event'],
            'processing_status' => $status,
            'http_status' => $httpStatus,
            'message' => $message,
            'email' => $extracted['email'],
            'product_code' => $extracted['product_code'],
            'external_reference' => $extracted['external_reference'],
            'purchase_id' => $purchaseId,
            'payload' => $payload ?: null,
            'response' => $response,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);
    }

    private function sanitizePayload(array $payload): array
    {
        if (isset($payload['hottok'])) {
            $payload['hottok'] = '***';
        }

        return $payload;
    }

    /**
     * @return array{event: ?string, email: ?string, product_code: ?string, external_reference: ?string}
     */
    private function extractFields(WebhookPlatform $platform, array $payload): array
    {
        return match ($platform) {
            WebhookPlatform::Hotmart => $this->extractHotmartFields($payload),
            WebhookPlatform::Generic => $this->extractGenericFields($payload),
        };
    }

    private function extractHotmartFields(array $payload): array
    {
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        $productCode = null;
        foreach ([
            Arr::get($data, 'product.id'),
            Arr::get($data, 'product.ucode'),
            Arr::get($data, 'purchase.offer.code'),
            Arr::get($data, 'offer.code'),
        ] as $candidate) {
            if (filled($candidate)) {
                $productCode = (string) $candidate;
                break;
            }
        }

        $email = strtolower(trim((string) Arr::get($data, 'buyer.email', '')));

        return [
            'event' => filled($payload['event'] ?? null) ? strtoupper((string) $payload['event']) : null,
            'email' => filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null,
            'product_code' => $productCode,
            'external_reference' => filled(Arr::get($data, 'purchase.transaction'))
                ? trim((string) Arr::get($data, 'purchase.transaction'))
                : (filled($payload['id'] ?? null) ? trim((string) $payload['id']) : null),
        ];
    }

    private function extractGenericFields(array $payload): array
    {
        $email = strtolower(trim((string) ($payload['email'] ?? '')));

        return [
            'event' => filled($payload['event'] ?? null) ? strtolower((string) $payload['event']) : null,
            'email' => filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null,
            'product_code' => filled($payload['product_code'] ?? null)
                ? trim((string) $payload['product_code'])
                : null,
            'external_reference' => filled($payload['external_reference'] ?? null)
                ? trim((string) $payload['external_reference'])
                : null,
        ];
    }
}
