<?php

namespace App\Enums;

enum WhatsAppMessageEvent: string
{
    case PurchaseApproved = 'purchase_approved';
    case PurchaseFunnel = 'purchase_funnel';
    case ManualTest = 'manual_test';

    public function label(): string
    {
        return match ($this) {
            self::PurchaseApproved => 'Compra aprovada (Plan Completo)',
            self::PurchaseFunnel => 'Order bump / Upsell',
            self::ManualTest => 'Teste manual',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PurchaseApproved => 'Enviada quando o produto principal da Hotmart é aprovado e o cliente recebe acesso à biblioteca.',
            self::PurchaseFunnel => 'Enviada quando um order bump ou upsell é registrado. Por padrão fica desligada.',
            self::ManualTest => 'Usada ao clicar em "Enviar teste" no painel. Não é disparada automaticamente.',
        };
    }

    public function hotmartEvents(): string
    {
        return match ($this) {
            self::PurchaseApproved => 'PURCHASE_APPROVED, PURCHASE_COMPLETE',
            self::PurchaseFunnel => 'PURCHASE_APPROVED (produtos de funil)',
            self::ManualTest => '—',
        };
    }

  /**
     * @return list<string>
     */
    public function placeholders(): array
    {
        return match ($this) {
            self::PurchaseApproved, self::ManualTest => [
                '{nome}',
                '{email}',
                '{telefone}',
                '{producto}',
                '{link_acceso}',
                '{transacao}',
            ],
            self::PurchaseFunnel => [
                '{nome}',
                '{email}',
                '{telefone}',
                '{producto}',
                '{transacao}',
            ],
        };
    }

    public function defaultBody(): string
    {
        return match ($this) {
            self::PurchaseApproved => "¡Hola {nome}! Su acceso a la Biblioteca Bíblica Digital ya está listo.\n\nEntre con su correo {email} en:\n{link_acceso}",
            self::PurchaseFunnel => "¡Hola {nome}! Confirmamos su compra adicional: {producto}.\n\nGracias por confiar en nosotros.",
            self::ManualTest => "¡Hola {nome}! Este es un mensaje de prueba de la Biblioteca Bíblica Digital.\n\nSi recibió esto, la integración con WhatsApp está funcionando.",
        };
    }

    public function defaultEnabled(): bool
    {
        return match ($this) {
            self::PurchaseApproved, self::ManualTest => true,
            self::PurchaseFunnel => false,
        };
    }

    public function dispatchTrigger(): WhatsAppDispatchTrigger
    {
        return match ($this) {
            self::ManualTest => WhatsAppDispatchTrigger::ManualTest,
            self::PurchaseApproved, self::PurchaseFunnel => WhatsAppDispatchTrigger::PurchaseWebhook,
        };
    }
}
