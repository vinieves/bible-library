<?php

namespace App\Services;

use App\DataTransferObjects\NormalizedPurchaseData;
use App\Enums\EmailDispatchStatus;
use App\Enums\EmailDispatchTrigger;
use App\Enums\PurchaseWebhookAction;
use App\Enums\WhatsAppMessageEvent;
use App\Jobs\SendTransactionalEmailJob;
use App\Models\EmailDispatchLog;
use App\Models\User;
use App\Support\IntegrationSettings;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EmailNotificationService
{
    public function __construct(
        private readonly EmailMessageTemplateService $templates,
    ) {}

    public function dispatchForWebhook(
        NormalizedPurchaseData $data,
        PurchaseWebhookAction $action,
        ?int $purchaseId = null,
        ?int $userId = null,
    ): bool {
        if (! IntegrationSettings::emailEnabled()) {
            Log::info('E-mail não disparado: integração global desligada.', [
                'hotmart_event' => $data->hotmartEvent,
                'transaction' => $data->externalReference,
            ]);

            return false;
        }

        if (! IntegrationSettings::emailSmtpConfigured()) {
            Log::info('E-mail não disparado: SMTP Hostinger não configurado.', [
                'hotmart_event' => $data->hotmartEvent,
                'transaction' => $data->externalReference,
            ]);

            return false;
        }

        if (blank($data->email)) {
            Log::info('E-mail não disparado: e-mail ausente no webhook.', [
                'hotmart_event' => $data->hotmartEvent,
                'transaction' => $data->externalReference,
            ]);

            return false;
        }

        $messageEvent = WhatsAppMessageEvent::fromHotmartEvent($data->hotmartEvent, $action);

        if (! $messageEvent) {
            Log::info('E-mail não disparado: evento sem regra mapeada.', [
                'hotmart_event' => $data->hotmartEvent,
                'action' => $action->value,
            ]);

            return false;
        }

        if (! $this->templates->isEnabled($messageEvent)) {
            Log::info('E-mail não disparado: regra inexistente ou inativa.', [
                'message_event' => $messageEvent->value,
                'transaction' => $data->externalReference,
            ]);

            return false;
        }

        if ($this->wasAlreadySent($messageEvent, $purchaseId, $data->externalReference)) {
            Log::info('E-mail não disparado: mensagem já enviada para esta compra/evento.', [
                'message_event' => $messageEvent->value,
                'purchase_id' => $purchaseId,
                'transaction' => $data->externalReference,
                'email' => $data->email,
            ]);

            return false;
        }

        $user = $this->resolveUser($data, $userId);
        $productTitle = (string) data_get($data->rawPayload, 'data.product.name', '');

        SendTransactionalEmailJob::dispatch(
            userId: $user->id,
            recipientEmail: $data->email,
            purchaseId: $purchaseId ?? 0,
            messageEvent: $messageEvent,
            trigger: EmailDispatchTrigger::PurchaseWebhook,
            contextHotmartEvent: $data->hotmartEvent,
            contextProductTitle: $productTitle,
            contextCurrency: $data->currency,
            contextAmount: $data->amount,
            contextTransaction: $data->externalReference,
            contextPhone: $data->phone,
        );

        Log::info('E-mail enfileirado para o cliente do webhook.', [
            'message_event' => $messageEvent->value,
            'purchase_id' => $purchaseId,
            'transaction' => $data->externalReference,
            'email' => $data->email,
        ]);

        return true;
    }

    private function wasAlreadySent(
        WhatsAppMessageEvent $messageEvent,
        ?int $purchaseId,
        string $transaction,
    ): bool {
        $query = EmailDispatchLog::query()
            ->where('status', EmailDispatchStatus::Sent)
            ->where('trigger', EmailDispatchTrigger::PurchaseWebhook)
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
