<?php

namespace App\Services;

use App\DataTransferObjects\NormalizedPurchaseData;
use App\Enums\PurchaseWebhookAction;
use App\Enums\WhatsAppDispatchStatus;
use App\Enums\WhatsAppDispatchTrigger;
use App\Enums\WhatsAppMessageEvent;
use App\Jobs\SendWelcomeWhatsAppJob;
use App\Models\User;
use App\Models\WhatsAppDispatchLog;
use App\Support\IntegrationSettings;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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
            Log::info('WhatsApp não disparado: integração global desligada.', [
                'hotmart_event' => $data->hotmartEvent,
                'transaction' => $data->externalReference,
            ]);

            return false;
        }

        if (blank($data->phone)) {
            Log::info('WhatsApp não disparado: telefone ausente no webhook.', [
                'hotmart_event' => $data->hotmartEvent,
                'transaction' => $data->externalReference,
                'email' => $data->email,
            ]);

            return false;
        }

        $messageEvent = WhatsAppMessageEvent::fromHotmartEvent($data->hotmartEvent, $action);

        if (! $messageEvent) {
            Log::info('WhatsApp não disparado: evento sem regra mapeada.', [
                'hotmart_event' => $data->hotmartEvent,
                'action' => $action->value,
            ]);

            return false;
        }

        if (! $this->templates->isEnabled($messageEvent)) {
            Log::info('WhatsApp não disparado: regra inexistente ou inativa.', [
                'message_event' => $messageEvent->value,
                'transaction' => $data->externalReference,
            ]);

            return false;
        }

        if ($this->wasAlreadySent($messageEvent, $purchaseId, $data->externalReference)) {
            Log::info('WhatsApp não disparado: mensagem já enviada para esta compra/evento.', [
                'message_event' => $messageEvent->value,
                'purchase_id' => $purchaseId,
                'transaction' => $data->externalReference,
                'phone' => $data->phone,
            ]);

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

        Log::info('WhatsApp enfileirado para o cliente do webhook.', [
            'message_event' => $messageEvent->value,
            'purchase_id' => $purchaseId,
            'transaction' => $data->externalReference,
            'phone' => $data->phone,
            'email' => $data->email,
        ]);

        return true;
    }

    private function wasAlreadySent(
        WhatsAppMessageEvent $messageEvent,
        ?int $purchaseId,
        string $transaction,
    ): bool {
        $query = WhatsAppDispatchLog::query()
            ->where('status', WhatsAppDispatchStatus::Sent)
            ->where('trigger', WhatsAppDispatchTrigger::PurchaseWebhook)
            ->where('message_event', $messageEvent->value);

        if ($purchaseId) {
            return $query->where('purchase_id', $purchaseId)->exists();
        }

        if (filled($transaction)) {
            return $query->where('hotmart_transaction', $transaction)->exists();
        }

        return false;
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
