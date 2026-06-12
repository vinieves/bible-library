<?php

namespace App\Services\Webhooks;

use App\Contracts\WebhookAdapterInterface;
use App\DataTransferObjects\NormalizedPurchaseData;
use App\DataTransferObjects\ParsedWebhookResult;
use App\Enums\WebhookPlatform;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class HotmartWebhookAdapter implements WebhookAdapterInterface
{
    private const APPROVED_EVENTS = [
        'PURCHASE_APPROVED',
        'PURCHASE_COMPLETE',
    ];

    public function parse(Request $request): ParsedWebhookResult
    {
        $payload = $request->all();

        if (! is_array($payload) || blank($payload['event'] ?? null)) {
            return ParsedWebhookResult::ignored('Payload Hotmart inválido.');
        }

        $event = strtoupper((string) $payload['event']);

        if (! in_array($event, self::APPROVED_EVENTS, true)) {
            return ParsedWebhookResult::ignored("Evento {$event} ignorado.");
        }

        $data = $payload['data'] ?? [];

        if (! is_array($data)) {
            return ParsedWebhookResult::ignored('Campo data ausente no payload Hotmart.');
        }

        $purchaseStatus = strtoupper((string) Arr::get($data, 'purchase.status', 'APPROVED'));

        if ($purchaseStatus !== 'APPROVED') {
            return ParsedWebhookResult::ignored("Compra com status {$purchaseStatus} ignorada.");
        }

        $email = strtolower(trim((string) Arr::get($data, 'buyer.email', '')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ParsedWebhookResult::ignored('E-mail do comprador inválido.');
        }

        $productCodes = $this->resolveProductCodes($data);

        if ($productCodes === []) {
            return ParsedWebhookResult::ignored('Código do produto Hotmart não encontrado.');
        }

        $transaction = trim((string) Arr::get($data, 'purchase.transaction', ''));

        if (blank($transaction)) {
            return ParsedWebhookResult::ignored('Transação Hotmart ausente.');
        }

        $eventId = trim((string) ($payload['id'] ?? $transaction));

        return ParsedWebhookResult::approved(new NormalizedPurchaseData(
            email: $email,
            name: $this->resolveName($data),
            phone: $this->resolvePhone($data),
            productCode: $productCodes[0],
            amount: $this->resolveAmount($data),
            externalReference: $transaction,
            eventId: $eventId,
            rawPayload: $payload,
            productCodeCandidates: $productCodes,
        ));
    }

    /**
     * @return list<string>
     */
    private function resolveProductCodes(array $data): array
    {
        $candidates = [
            Arr::get($data, 'product.id'),
            Arr::get($data, 'product.ucode'),
            Arr::get($data, 'purchase.offer.code'),
        ];

        return array_values(array_unique(array_filter(
            array_map(fn ($value) => trim((string) $value), $candidates),
            fn (string $value) => filled($value),
        )));
    }

    private function resolveName(array $data): ?string
    {
        $name = trim((string) Arr::get($data, 'buyer.name', ''));

        return filled($name) ? $name : null;
    }

    private function resolvePhone(array $data): ?string
    {
        $candidates = [
            Arr::get($data, 'buyer.checkout_phone'),
            Arr::get($data, 'buyer.phone'),
            Arr::get($data, 'buyer.phone_number'),
        ];

        foreach ($candidates as $candidate) {
            $normalized = PhoneNumber::normalize($candidate);

            if ($normalized) {
                return $normalized;
            }
        }

        return null;
    }

    private function resolveAmount(array $data): ?float
    {
        $value = Arr::get($data, 'purchase.full_price.value');

        if ($value === null) {
            $value = Arr::get($data, 'purchase.price.value');
        }

        if ($value === null) {
            return null;
        }

        $amount = (float) $value;

        if ($amount > 999 && fmod($amount, 1.0) === 0.0) {
            return round($amount / 100, 2);
        }

        return round($amount, 2);
    }
}
