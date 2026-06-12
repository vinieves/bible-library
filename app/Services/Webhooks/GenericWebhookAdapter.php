<?php

namespace App\Services\Webhooks;

use App\Contracts\WebhookAdapterInterface;
use App\DataTransferObjects\NormalizedPurchaseData;
use App\DataTransferObjects\ParsedWebhookResult;
use App\Enums\PurchaseWebhookAction;
use Illuminate\Http\Request;

class GenericWebhookAdapter implements WebhookAdapterInterface
{
    public function parse(Request $request): ParsedWebhookResult
    {
        $payload = $request->all();

        $event = strtolower((string) ($payload['event'] ?? 'purchase.approved'));

        if (! in_array($event, ['purchase.approved', 'approved', 'purchase_approved'], true)) {
            return ParsedWebhookResult::ignored("Evento {$event} ignorado.");
        }

        $email = strtolower(trim((string) ($payload['email'] ?? '')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ParsedWebhookResult::ignored('E-mail inválido.');
        }

        $productCode = trim((string) ($payload['product_code'] ?? ''));

        if (blank($productCode)) {
            return ParsedWebhookResult::ignored('product_code ausente.');
        }

        $externalReference = trim((string) ($payload['external_reference'] ?? ''));

        if (blank($externalReference)) {
            return ParsedWebhookResult::ignored('external_reference ausente.');
        }

        $amount = isset($payload['amount']) ? round((float) $payload['amount'], 2) : null;

        return ParsedWebhookResult::approved(new NormalizedPurchaseData(
            hotmartEvent: 'PURCHASE_APPROVED',
            action: PurchaseWebhookAction::GrantAccess,
            email: $email,
            name: filled($payload['name'] ?? null) ? trim((string) $payload['name']) : null,
            phone: PhoneNumber::normalize($payload['phone'] ?? null),
            productCode: $productCode,
            amount: $amount,
            currency: filled($payload['currency'] ?? null) ? strtoupper((string) $payload['currency']) : null,
            externalReference: $externalReference,
            eventId: trim((string) ($payload['event_id'] ?? $externalReference)),
            rawPayload: $payload,
            productCodeCandidates: [$productCode],
        ));
    }
}
