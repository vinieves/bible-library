<?php

namespace App\Services\Webhooks;

use App\Contracts\WebhookAdapterInterface;
use App\DataTransferObjects\NormalizedPurchaseData;
use App\DataTransferObjects\ParsedWebhookResult;
use App\Enums\PurchaseWebhookAction;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class HotmartWebhookAdapter implements WebhookAdapterInterface
{
    private const SUPPORTED_EVENTS = [
        'PURCHASE_APPROVED',
        'PURCHASE_COMPLETE',
        'PURCHASE_CANCELED',
        'PURCHASE_BILLET_PRINTED',
        'PURCHASE_PROTEST',
        'PURCHASE_REFUNDED',
        'PURCHASE_CHARGEBACK',
        'PURCHASE_EXPIRED',
        'PURCHASE_DELAYED',
    ];

    private const ACCESS_EVENTS = [
        'PURCHASE_APPROVED',
    ];

    public function parse(Request $request): ParsedWebhookResult
    {
        $payload = $request->all();

        if (! is_array($payload) || blank($payload['event'] ?? null)) {
            return ParsedWebhookResult::ignored('Payload Hotmart inválido.');
        }

        $event = strtoupper((string) $payload['event']);

        if (! in_array($event, self::SUPPORTED_EVENTS, true)) {
            return ParsedWebhookResult::ignored("Evento {$event} ignorado.");
        }

        $data = $payload['data'] ?? [];

        if (! is_array($data)) {
            return ParsedWebhookResult::ignored('Campo data ausente no payload Hotmart.');
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

        $action = $this->resolveAction($event, $data, $productCodes);

        if ($action === null) {
            $status = strtoupper((string) Arr::get($data, 'purchase.status', ''));

            return ParsedWebhookResult::ignored("Evento {$event} com status {$status} ignorado.");
        }

        $eventId = trim((string) ($payload['id'] ?? $transaction));

        return ParsedWebhookResult::approved(new NormalizedPurchaseData(
            hotmartEvent: $event,
            action: $action,
            email: $email,
            name: $this->resolveName($data),
            phone: $this->resolvePhone($data),
            productCode: $productCodes[0],
            amount: $this->resolveAmount($data),
            currency: $this->resolveCurrency($data),
            externalReference: $transaction,
            eventId: $eventId,
            rawPayload: $payload,
            productCodeCandidates: $productCodes,
        ));
    }

  /**
     * @param  list<string>  $productCodes
     */
    private function resolveAction(string $event, array $data, array $productCodes): ?PurchaseWebhookAction
    {
        if ($event === 'PURCHASE_COMPLETE') {
            return PurchaseWebhookAction::NotifyOnly;
        }

        if (! in_array($event, self::ACCESS_EVENTS, true)) {
            return PurchaseWebhookAction::NotifyOnly;
        }

        $purchaseStatus = strtoupper((string) Arr::get($data, 'purchase.status', 'APPROVED'));

        if ($purchaseStatus !== 'APPROVED') {
            return null;
        }

        $product = Product::query()
            ->where('is_active', true)
            ->whereIn('product_code', $productCodes)
            ->first();

        if (! $product) {
            return PurchaseWebhookAction::UnmappedProduct;
        }

        if ($product->grantsAccess()) {
            return PurchaseWebhookAction::GrantAccess;
        }

        return PurchaseWebhookAction::AcknowledgeFunnel;
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
        $currency = strtoupper((string) Arr::get($data, 'purchase.full_price.currency_value', Arr::get($data, 'purchase.price.currency_value', '')));

        if (in_array($currency, ['COP', 'CLP'], true) && $amount > 999) {
            return round($amount, 2);
        }

        if ($amount > 999 && fmod($amount, 1.0) === 0.0) {
            return round($amount / 100, 2);
        }

        return round($amount, 2);
    }

    private function resolveCurrency(array $data): ?string
    {
        $currency = Arr::get($data, 'purchase.full_price.currency_value')
            ?? Arr::get($data, 'purchase.price.currency_value');

        return filled($currency) ? strtoupper((string) $currency) : null;
    }
}
