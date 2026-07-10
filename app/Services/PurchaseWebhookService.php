<?php

namespace App\Services;

use App\DataTransferObjects\NormalizedPurchaseData;
use App\Enums\PurchaseStatus;
use App\Enums\PurchaseWebhookAction;
use App\Enums\WebhookPlatform;
use App\Exceptions\WebhookProcessingException;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PurchaseWebhookService
{
    public function __construct(
        private readonly WhatsAppNotificationService $whatsappNotifications,
        private readonly EmailNotificationService $emailNotifications,
        private readonly ProductWebhookSyncService $productSync,
    ) {}

    public function handle(NormalizedPurchaseData $data, WebhookPlatform $platform): array
    {
        return match ($data->action) {
            PurchaseWebhookAction::GrantAccess => $this->processGrantAccess($data, $platform),
            PurchaseWebhookAction::AcknowledgeFunnel => $this->acknowledgeFunnelPurchase($data, $platform),
            PurchaseWebhookAction::NotifyOnly => $this->notifyOnly($data, $platform),
            PurchaseWebhookAction::UnmappedProduct => throw new WebhookProcessingException(
                'Produto não mapeado para product_code: '.implode(', ', $data->productCodesForLookup())
            ),
        };
    }

    private function processGrantAccess(NormalizedPurchaseData $data, WebhookPlatform $platform): array
    {
        $product = $this->findProduct($data);

        if (! $product->grantsAccess()) {
            return $this->acknowledgeFunnelPurchase($data, $platform);
        }

        if (! $product->plan_id) {
            throw new WebhookProcessingException(
                "Produto {$product->title} não possui plano vinculado."
            );
        }

        $existing = $this->findExistingPurchase($data, $platform, $product);

        if ($existing?->isApproved()) {
            return [
                'status' => 'duplicate',
                'message' => 'Compra já processada.',
                'purchase_id' => $existing->id,
                'user_id' => $existing->user_id,
            ];
        }

        $result = DB::transaction(function () use ($data, $platform, $product, $existing) {
            $user = User::query()->firstOrCreate(
                ['email' => $data->email],
                [
                    'name' => $data->name ?: 'Cliente',
                    'password' => Hash::make(Str::random(40)),
                ]
            );

            if ($data->name && $user->name !== $data->name) {
                $user->update(['name' => $data->name]);
            }

            $user->plans()->syncWithoutDetaching([
                $product->plan_id => [
                    'granted_at' => now(),
                    'expires_at' => null,
                    'granted_by' => 'webhook:'.$platform->value,
                ],
            ]);

            $purchase = Purchase::query()->updateOrCreate(
                [
                    'platform' => $platform->value,
                    'external_reference' => $data->externalReference,
                    'product_code' => $product->product_code,
                ],
                [
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                    'plan_id' => $product->plan_id,
                    'email' => $data->email,
                    'name' => $data->name,
                    'phone' => $data->phone,
                    'amount' => $data->amount ?? $product->price,
                    'status' => PurchaseStatus::Approved,
                    'metadata' => [
                        'event_id' => $data->eventId,
                        'hotmart_event' => $data->hotmartEvent,
                        'platform' => $platform->value,
                        'raw_payload' => $data->rawPayload,
                        'previous_status' => $existing?->status?->value,
                        'grants_access' => true,
                    ],
                ]
            );

            return [
                'status' => 'processed',
                'message' => 'Compra aprovada processada com sucesso.',
                'purchase_id' => $purchase->id,
                'user_id' => $user->id,
                'product_id' => $product->id,
                'plan_id' => $product->plan_id,
            ];
        });

        Log::info('Webhook de compra processado.', [
            'platform' => $platform->value,
            'hotmart_event' => $data->hotmartEvent,
            'external_reference' => $data->externalReference,
            'email' => $data->email,
            'purchase_id' => $result['purchase_id'],
        ]);

        $this->whatsappNotifications->dispatchForWebhook(
            data: $data,
            action: PurchaseWebhookAction::GrantAccess,
            purchaseId: $result['purchase_id'],
            userId: $result['user_id'],
        );

        $this->emailNotifications->dispatchForWebhook(
            data: $data,
            action: PurchaseWebhookAction::GrantAccess,
            purchaseId: $result['purchase_id'],
            userId: $result['user_id'],
        );

        return $result;
    }

    private function acknowledgeFunnelPurchase(NormalizedPurchaseData $data, WebhookPlatform $platform): array
    {
        $product = $this->findProduct($data);
        $existing = $this->findExistingPurchase($data, $platform, $product);
        $user = User::query()->where('email', $data->email)->first();

        $purchase = Purchase::query()->updateOrCreate(
            [
                'platform' => $platform->value,
                'external_reference' => $data->externalReference,
                'product_code' => $product->product_code,
            ],
            [
                'user_id' => $user?->id,
                'product_id' => $product->id,
                'plan_id' => null,
                'email' => $data->email,
                'name' => $data->name,
                'phone' => $data->phone,
                'amount' => $data->amount ?? $product->price,
                'status' => PurchaseStatus::Approved,
                'metadata' => [
                    'event_id' => $data->eventId,
                    'hotmart_event' => $data->hotmartEvent,
                    'platform' => $platform->value,
                    'raw_payload' => $data->rawPayload,
                    'previous_status' => $existing?->status?->value,
                    'grants_access' => false,
                    'funnel_acknowledgement' => true,
                ],
            ]
        );

        Log::info('Webhook de funil registrado sem liberação de acesso.', [
            'platform' => $platform->value,
            'hotmart_event' => $data->hotmartEvent,
            'external_reference' => $data->externalReference,
            'email' => $data->email,
            'product_code' => $product->product_code,
            'purchase_id' => $purchase->id,
        ]);

        $this->whatsappNotifications->dispatchForWebhook(
            data: $data,
            action: PurchaseWebhookAction::AcknowledgeFunnel,
            purchaseId: $purchase->id,
            userId: $user?->id,
        );

        $this->emailNotifications->dispatchForWebhook(
            data: $data,
            action: PurchaseWebhookAction::AcknowledgeFunnel,
            purchaseId: $purchase->id,
            userId: $user?->id,
        );

        return [
            'status' => 'acknowledged',
            'message' => 'Compra de funil registrada com sucesso (sem liberação de acesso).',
            'purchase_id' => $purchase->id,
            'user_id' => $user?->id,
            'product_id' => $product->id,
        ];
    }

    private function notifyOnly(NormalizedPurchaseData $data, WebhookPlatform $platform): array
    {
        $productName = (string) data_get($data->rawPayload, 'data.product.name', $data->productCode);

        $logMessage = match ($data->hotmartEvent) {
            'PURCHASE_OUT_OF_SHOPPING_CART' => 'Abandono de checkout Hotmart registrado (somente notificação).',
            'PURCHASE_PROTEST' => 'Pedido de reembolso Hotmart registrado (somente notificação).',
            default => 'Webhook Hotmart registrado (somente notificação).',
        };

        Log::info($logMessage, [
            'platform' => $platform->value,
            'hotmart_event' => $data->hotmartEvent,
            'external_reference' => $data->externalReference,
            'email' => $data->email,
            'product' => $productName,
        ]);

        $this->whatsappNotifications->dispatchForWebhook(
            data: $data,
            action: PurchaseWebhookAction::NotifyOnly,
        );

        $this->emailNotifications->dispatchForWebhook(
            data: $data,
            action: PurchaseWebhookAction::NotifyOnly,
        );

        $responseMessage = match ($data->hotmartEvent) {
            'PURCHASE_OUT_OF_SHOPPING_CART' => 'Abandono de checkout registrado (sem liberação de acesso).',
            'PURCHASE_PROTEST' => 'Pedido de reembolso registrado (sem alteração de acesso).',
            default => "Evento {$data->hotmartEvent} registrado.",
        };

        return [
            'status' => 'acknowledged',
            'message' => $responseMessage,
            'hotmart_event' => $data->hotmartEvent,
            'product' => $productName,
        ];
    }

    private function findProduct(NormalizedPurchaseData $data): Product
    {
        return $this->productSync->resolve($data);
    }

    private function findExistingPurchase(
        NormalizedPurchaseData $data,
        WebhookPlatform $platform,
        Product $product,
    ): ?Purchase {
        return Purchase::query()
            ->where('platform', $platform->value)
            ->where('external_reference', $data->externalReference)
            ->where('product_code', $product->product_code)
            ->first();
    }
}
