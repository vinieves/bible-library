<?php

namespace App\Jobs;

use App\Models\Purchase;
use App\Models\User;
use App\Services\EvolutionApiService;
use App\Services\MessageTemplateService;
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
    ) {}

    public function handle(
        EvolutionApiService $evolutionApi,
        MessageTemplateService $messageTemplate,
    ): void {
        $user = User::query()->findOrFail($this->userId);
        $purchase = $this->purchaseId > 0
            ? Purchase::query()->with('product')->find($this->purchaseId)
            : null;

        $message = $messageTemplate->renderWelcomeMessage($user, $purchase);

        $evolutionApi->sendText($this->phone, $message);

        Log::info('WhatsApp de boas-vindas enviado.', [
            'user_id' => $this->userId,
            'purchase_id' => $this->purchaseId,
            'phone' => $this->phone,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Falha ao enviar WhatsApp de boas-vindas.', [
            'user_id' => $this->userId,
            'purchase_id' => $this->purchaseId,
            'phone' => $this->phone,
            'error' => $exception?->getMessage(),
        ]);
    }
}
