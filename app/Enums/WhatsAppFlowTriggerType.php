<?php

namespace App\Enums;

enum WhatsAppFlowTriggerType: string
{
    case Manual = 'manual';
    case Webhook = 'webhook';
    case Scheduled = 'scheduled';
    case FirstMessage = 'first_message';
    case MessageTrigger = 'message_trigger';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Webhook => 'Webhook (Hotmart)',
            self::Scheduled => 'Agendado',
            self::FirstMessage => 'Primeira mensagem (WhatsApp)',
            self::MessageTrigger => 'Gatilho por mensagem',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Manual => 'Disparo manual pelo painel ou teste.',
            self::Webhook => 'Disparo automático por evento Hotmart.',
            self::Scheduled => 'Disparo agendado (em breve).',
            self::FirstMessage => 'Dispara uma vez quando um contato novo envia a primeira mensagem no WhatsApp.',
            self::MessageTrigger => 'Dispara quando a primeira mensagem do contato for igual ao texto de um gatilho cadastrado (ex.: mensagem do anúncio).',
        };
    }
}
