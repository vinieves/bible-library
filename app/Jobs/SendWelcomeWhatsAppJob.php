<?php

namespace App\Jobs;

use App\Enums\WhatsAppDispatchTrigger;
use App\Models\Purchase;
use App\Models\User;
use App\Services\EvolutionApiService;
use App\Services\MessageTemplateService;
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
        public WhatsAppDispatchTrigger $trigger = WhatsAppDispatchTrigger::PurchaseWebhook,
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

        $phoneNormalized = PhoneNumber::normalize($this->phone);
        $message = $messageTemplate->renderWelcomeMessage($user, $purchase);
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

            Log::info('WhatsApp de boas-vindas enviado.', [
                'user_id' => $this->userId,
                'purchase_id' => $this->purchaseId,
                'phone' => $phoneNormalized,
                'trigger' => $this->trigger->value,
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
        Log::error('Falha definitiva ao enviar WhatsApp de boas-vindas.', [
            'user_id' => $this->userId,
            'purchase_id' => $this->purchaseId,
            'phone' => $this->phone,
            'trigger' => $this->trigger->value,
            'error' => $exception?->getMessage(),
        ]);
    }
}
