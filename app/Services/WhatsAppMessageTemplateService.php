<?php

namespace App\Services;

use App\Enums\WhatsAppMessageEvent;
use App\Models\Purchase;
use App\Models\User;
use App\Models\WhatsAppMessageTemplate;

class WhatsAppMessageTemplateService
{
    public function body(WhatsAppMessageEvent $event): string
    {
        $template = $this->find($event);

        if ($template && filled($template->body)) {
            return $template->body;
        }

        return $event->defaultBody();
    }

    public function isEnabled(WhatsAppMessageEvent $event): bool
    {
        $template = $this->find($event);

        return $template?->is_enabled ?? $event->defaultEnabled();
    }

    public function render(WhatsAppMessageEvent $event, User $user, ?Purchase $purchase = null, ?NormalizedPurchaseContext $context = null): string
    {
        $productTitle = $purchase?->product?->title
            ?? $context?->productTitle
            ?? '';

        $replacements = [
            '{nome}' => $user->name,
            '{email}' => $user->email,
            '{telefone}' => $purchase?->phone ?? $context?->phone ?? '',
            '{producto}' => $productTitle,
            '{link_acceso}' => route('login'),
            '{transacao}' => $purchase?->external_reference ?? $context?->transaction ?? '',
            '{evento}' => $context?->hotmartEvent ?? $event->hotmartEvent(),
            '{moeda}' => $context?->currency ?? '',
            '{valor}' => $this->formatAmount($purchase?->amount ?? $context?->amount),
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $this->body($event)
        );
    }

    public function preview(string $body): string
    {
        $replacements = [
            '{nome}' => 'María García',
            '{email}' => 'maria@ejemplo.com',
            '{telefone}' => '5215512345678',
            '{producto}' => 'Plan Completo — Hotmart',
            '{link_acceso}' => route('login'),
            '{transacao}' => 'HP1234567890',
            '{evento}' => 'PURCHASE_APPROVED',
            '{moeda}' => 'USD',
            '{valor}' => '4.90',
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $body
        );
    }

    public function upsert(WhatsAppMessageEvent $event, string $body, bool $isEnabled): WhatsAppMessageTemplate
    {
        return WhatsAppMessageTemplate::query()->updateOrCreate(
            ['event' => $event->value],
            [
                'body' => $body,
                'is_enabled' => $isEnabled,
                'sort_order' => $this->sortOrder($event),
            ]
        );
    }

    /**
     * @return list<WhatsAppMessageTemplate>
     */
    public function allTemplates(): array
    {
        $indexed = WhatsAppMessageTemplate::query()
            ->get()
            ->keyBy(fn (WhatsAppMessageTemplate $template) => $template->event->value);

        $templates = [];

        foreach (WhatsAppMessageEvent::cases() as $event) {
            $templates[] = $indexed->get($event->value) ?? new WhatsAppMessageTemplate([
                'event' => $event,
                'body' => $event->defaultBody(),
                'is_enabled' => $event->defaultEnabled(),
                'sort_order' => $this->sortOrder($event),
            ]);
        }

        return $templates;
    }

    public function ensureDefaults(): void
    {
        foreach (WhatsAppMessageEvent::cases() as $event) {
            WhatsAppMessageTemplate::query()->firstOrCreate(
                ['event' => $event->value],
                [
                    'body' => $event->defaultBody(),
                    'is_enabled' => $event->defaultEnabled(),
                    'sort_order' => $this->sortOrder($event),
                ]
            );
        }
    }

    private function find(WhatsAppMessageEvent $event): ?WhatsAppMessageTemplate
    {
        return WhatsAppMessageTemplate::query()
            ->where('event', $event->value)
            ->first();
    }

    private function sortOrder(WhatsAppMessageEvent $event): int
    {
        return array_search($event, WhatsAppMessageEvent::cases(), true) + 1;
    }

    private function formatAmount(mixed $amount): string
    {
        if ($amount === null || $amount === '') {
            return '';
        }

        return number_format((float) $amount, 2, '.', '');
    }
}
