<?php

namespace App\Services;

use App\DataTransferObjects\NormalizedPurchaseData;
use App\Enums\PurchaseWebhookAction;
use App\Enums\WhatsAppMessageEvent;
use App\Jobs\SendWelcomeWhatsAppJob;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\User;
use App\Support\IntegrationSettings;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class WhatsAppNotificationService
{
    public function __construct(
        private readonly WhatsAppMessageTemplateService $templates,
    ) {}

    public function dispatchForWebhook(
        NormalizedPurchaseData $data,
        PurchaseWebhookAction $action,
        ?int $purchaseId = null,
        ?int $userId = null,
    ): bool {
        if (! IntegrationSettings::whatsappEnabled()) {
            return false;
        }

        if (blank($data->phone)) {
            return false;
        }

        $messageEvent = WhatsAppMessageEvent::fromHotmartEvent($data->hotmartEvent, $action);

        if (! $messageEvent || ! $this->templates->isEnabled($messageEvent)) {
            return false;
        }

        $user = $this->resolveUser($data, $userId);

        $productTitle = (string) data_get($data->rawPayload, 'data.product.name', '');

        SendWelcomeWhatsAppJob::dispatch(
            userId: $user->id,
            phone: $data->phone,
            purchaseId: $purchaseId ?? 0,
            messageEvent: $messageEvent,
            trigger: $messageEvent->dispatchTrigger(),
            contextHotmartEvent: $data->hotmartEvent,
            contextProductTitle: $productTitle,
            contextCurrency: $data->currency,
            contextAmount: $data->amount,
            contextTransaction: $data->externalReference,
        );

        return true;
    }

    private function resolveUser(NormalizedPurchaseData $data, ?int $userId): User
    {
        if ($userId) {
            return User::query()->findOrFail($userId);
        }

        return User::query()->firstOrCreate(
            ['email' => $data->email],
            [
                'name' => $data->name ?: 'Cliente',
                'password' => Hash::make(Str::random(40)),
            ]
        );
    }
}
