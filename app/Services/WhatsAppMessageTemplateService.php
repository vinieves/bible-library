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

    public function render(WhatsAppMessageEvent $event, User $user, ?Purchase $purchase = null): string
    {
        $replacements = [
            '{nome}' => $user->name,
            '{email}' => $user->email,
            '{telefone}' => $purchase?->phone ?? '',
            '{producto}' => $purchase?->product?->title ?? '',
            '{link_acceso}' => route('login'),
            '{transacao}' => $purchase?->external_reference ?? '',
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
                'sort_order' => array_search($event, WhatsAppMessageEvent::cases(), true) + 1,
            ]
        );
    }

    private function find(WhatsAppMessageEvent $event): ?WhatsAppMessageTemplate
    {
        return WhatsAppMessageTemplate::query()
            ->where('event', $event->value)
            ->first();
    }
}
