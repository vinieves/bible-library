<?php

namespace App\Services;

use App\DataTransferObjects\NormalizedPurchaseData;
use App\Enums\PurchaseStatus;
use App\Enums\WebhookPlatform;
use App\Exceptions\WebhookProcessingException;
use App\Jobs\SendWelcomeWhatsAppJob;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\User;
use App\Support\IntegrationSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PurchaseWebhookService
{
    public function process(NormalizedPurchaseData $data, WebhookPlatform $platform): array
    {
        $existing = Purchase::query()
            ->where('platform', $platform->value)
            ->where('external_reference', $data->externalReference)
            ->first();

        if ($existing?->isApproved()) {
            return [
                'status' => 'duplicate',
                'message' => 'Compra já processada.',
                'purchase_id' => $existing->id,
                'user_id' => $existing->user_id,
            ];
        }

        $product = Product::query()
            ->where('is_active', true)
            ->whereIn('product_code', $data->productCodesForLookup())
            ->first();

        if (! $product) {
            $codes = implode(', ', $data->productCodesForLookup());

            throw new WebhookProcessingException(
                "Produto não mapeado para product_code: {$codes}"
            );
        }

        if (! $product->plan_id) {
            throw new WebhookProcessingException(
                "Produto {$product->title} não possui plano vinculado."
            );
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
                ],
                [
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                    'plan_id' => $product->plan_id,
                    'email' => $data->email,
                    'name' => $data->name,
                    'phone' => $data->phone,
                    'product_code' => $product->product_code,
                    'amount' => $data->amount ?? $product->price,
                    'status' => PurchaseStatus::Approved,
                    'metadata' => [
                        'event_id' => $data->eventId,
                        'platform' => $platform->value,
                        'raw_payload' => $data->rawPayload,
                        'previous_status' => $existing?->status?->value,
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
            'external_reference' => $data->externalReference,
            'email' => $data->email,
            'purchase_id' => $result['purchase_id'],
        ]);

        if (IntegrationSettings::whatsappEnabled() && filled($data->phone)) {
            SendWelcomeWhatsAppJob::dispatch(
                userId: $result['user_id'],
                phone: $data->phone,
                purchaseId: $result['purchase_id'],
            );
        }

        return $result;
    }
}
