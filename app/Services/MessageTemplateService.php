<?php

namespace App\Services;

use App\Enums\WhatsAppMessageEvent;
use App\Models\Purchase;
use App\Models\User;

class MessageTemplateService
{
    public function __construct(
        private readonly WhatsAppMessageTemplateService $templates,
    ) {}

    public function renderWelcomeMessage(User $user, ?Purchase $purchase = null): string
    {
        return $this->templates->render(WhatsAppMessageEvent::PurchaseApproved, $user, $purchase);
    }

    public function render(WhatsAppMessageEvent $event, User $user, ?Purchase $purchase = null): string
    {
        return $this->templates->render($event, $user, $purchase);
    }
}
