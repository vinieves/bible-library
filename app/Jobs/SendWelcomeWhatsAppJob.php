<?php

namespace App\Jobs;

use App\Enums\WhatsAppDispatchTrigger;
use App\Enums\WhatsAppMessageEvent;
use App\Models\Purchase;
use App\Models\User;
use App\Services\EvolutionApiService;
use App\Services\MessageTemplateService;
use App\Services\NormalizedPurchaseContext;
use App\Services\Webhooks\PhoneNumber;
use App\Services\WhatsAppDispatchLogService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendWelcomeWhatsAppJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public int $userId,
        public string $phone,
        public int $purchaseId,
        public WhatsAppMessageEvent $messageEvent = WhatsAppMessageEvent::PurchaseApproved,
        public WhatsAppDispatchTrigger $trigger = WhatsAppDispatchTrigger::PurchaseWebhook,
        public ?string $contextHotmartEvent = null,
        public ?string $contextProductTitle = null,
        public ?string $contextCurrency = null,
        public ?float $contextAmount = null,
        public ?string $contextTransaction = null,
    ) {}

    public function handle(
        EvolutionApiService $evolutionApi,
        MessageTemplateService $messageTemplate,
        WhatsAppDispatchLogService $dispatchLog,
    ): void {
        $user = User::query()->findOrFail($this->userId);
        $purchase = $this->purchaseId > 0
            ? Purchase::query()->with('product')->find($this->purchaseId)
            : null;

        $context = new NormalizedPurchaseContext(
            hotmartEvent: $this->contextHotmartEvent,
            productTitle: $this->contextProductTitle,
            phone: $this->phone,
            transaction: $this->contextTransaction,
            currency: $this->contextCurrency,
            amount: $this->contextAmount,
        );

        $phoneNormalized = PhoneNumber::normalize($this->phone);
        $message = $messageTemplate->render($this->messageEvent, $user, $purchase, $context);
        $purchaseId = $this->purchaseId > 0 ? $this->purchaseId : null;
        $attempt = $this->attempts();

        if (! $phoneNormalized) {
            $dispatchLog->recordFailure(
                trigger: $this->trigger,
                phone: $this->phone,
                phoneNormalized: null,
                userId: $this->userId,
                purchaseId: $purchaseId,
                message: $message,
                errorMessage: 'Número de telefone inválido ou vazio.',
                attempt: $attempt,
            );

            Log::warning('WhatsApp não enviado: telefone inválido.', [
                'user_id' => $this->userId,
                'phone' => $this->phone,
                'message_event' => $this->messageEvent->value,
            ]);

            return;
        }

        try {
            $result = $evolutionApi->sendText($phoneNormalized, $message);

            $dispatchLog->recordSuccess(
                trigger: $this->trigger,
                phone: $this->phone,
                phoneNormalized: $phoneNormalized,
                userId: $this->userId,
                purchaseId: $purchaseId,
                message: $message,
                evolutionResponse: $result,
                attempt: $attempt,
                httpStatus: $result['http_status'] ?? null,
            );

            Log::info('WhatsApp enviado.', [
                'user_id' => $this->userId,
                'purchase_id' => $this->purchaseId,
                'phone' => $phoneNormalized,
                'trigger' => $this->trigger->value,
                'message_event' => $this->messageEvent->value,
            ]);
        } catch (Throwable $exception) {
            $dispatchLog->recordThrowable(
                trigger: $this->trigger,
                phone: $this->phone,
                phoneNormalized: $phoneNormalized,
                userId: $this->userId,
                purchaseId: $purchaseId,
                message: $message,
                exception: $exception,
                attempt: $attempt,
            );

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Falha definitiva ao enviar WhatsApp.', [
            'user_id' => $this->userId,
            'purchase_id' => $this->purchaseId,
            'phone' => $this->phone,
            'trigger' => $this->trigger->value,
            'message_event' => $this->messageEvent->value,
            'error' => $exception?->getMessage(),
        ]);
    }
}
