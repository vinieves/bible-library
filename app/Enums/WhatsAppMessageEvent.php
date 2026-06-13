<?php

namespace App\Enums;

enum WhatsAppMessageEvent: string
{
    case PurchaseApproved = 'purchase_approved';
    case PurchaseComplete = 'purchase_complete';
    case PurchaseFunnel = 'purchase_funnel';
    case PurchaseCanceled = 'purchase_canceled';
    case PurchaseBilletPrinted = 'purchase_billet_printed';
    case PurchaseProtest = 'purchase_protest';
    case PurchaseRefunded = 'purchase_refunded';
    case PurchaseChargeback = 'purchase_chargeback';
    case PurchaseExpired = 'purchase_expired';
    case PurchaseDelayed = 'purchase_delayed';
    case PurchaseOutOfShoppingCart = 'purchase_out_of_shopping_cart';
    case ManualTest = 'manual_test';

    public function label(): string
    {
        return $this->conditionLabel();
    }

    public function conditionLabel(): string
    {
        return match ($this) {
            self::PurchaseApproved => 'Venda aprovada (Plan Completo)',
            self::PurchaseComplete => 'Compra completa (garantia encerrada)',
            self::PurchaseFunnel => 'Order bump / upsell aprovado',
            self::PurchaseCanceled => 'Compra cancelada',
            self::PurchaseBilletPrinted => 'Boleto gerado / impresso',
            self::PurchaseProtest => 'Pedido de reembolso',
            self::PurchaseRefunded => 'Compra reembolsada',
            self::PurchaseChargeback => 'Chargeback',
            self::PurchaseExpired => 'Compra expirada',
            self::PurchaseDelayed => 'Pagamento atrasado',
            self::PurchaseOutOfShoppingCart => 'Abandonou checkout (carrinho)',
            self::ManualTest => 'Teste manual',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PurchaseApproved => 'Disparada quando o produto principal é aprovado e o cliente recebe acesso à biblioteca.',
            self::PurchaseComplete => 'Evento informativo da Hotmart quando o prazo de garantia expira. Apenas registra no sistema — não libera acesso. WhatsApp opcional se a regra estiver ativa.',
            self::PurchaseFunnel => 'Disparada quando um order bump ou upsell é aprovado (sem liberar novo acesso).',
            self::PurchaseCanceled => 'Disparada quando a Hotmart envia PURCHASE_CANCELED.',
            self::PurchaseBilletPrinted => 'Disparada quando o comprador gera/imprime boleto (PURCHASE_BILLET_PRINTED).',
            self::PurchaseProtest => 'Disparada em pedidos de reembolso / protesto (PURCHASE_PROTEST).',
            self::PurchaseRefunded => 'Disparada quando a compra é reembolsada (PURCHASE_REFUNDED).',
            self::PurchaseChargeback => 'Disparada em chargeback (PURCHASE_CHARGEBACK).',
            self::PurchaseExpired => 'Disparada quando a compra expira sem pagamento (PURCHASE_EXPIRED).',
            self::PurchaseDelayed => 'Disparada quando o pagamento está atrasado (PURCHASE_DELAYED).',
            self::PurchaseOutOfShoppingCart => 'Disparada quando o comprador sai do checkout sem concluir (PURCHASE_OUT_OF_SHOPPING_CART). Não libera acesso nem registra compra aprovada.',
            self::ManualTest => 'Usada apenas pelo botão "Enviar teste" no painel.',
        };
    }

    public function hotmartEvent(): string
    {
        return match ($this) {
            self::PurchaseApproved => 'PURCHASE_APPROVED',
            self::PurchaseComplete => 'PURCHASE_COMPLETE',
            self::PurchaseFunnel => 'PURCHASE_APPROVED',
            self::PurchaseCanceled => 'PURCHASE_CANCELED',
            self::PurchaseBilletPrinted => 'PURCHASE_BILLET_PRINTED',
            self::PurchaseProtest => 'PURCHASE_PROTEST',
            self::PurchaseRefunded => 'PURCHASE_REFUNDED',
            self::PurchaseChargeback => 'PURCHASE_CHARGEBACK',
            self::PurchaseExpired => 'PURCHASE_EXPIRED',
            self::PurchaseDelayed => 'PURCHASE_DELAYED',
            self::PurchaseOutOfShoppingCart => 'PURCHASE_OUT_OF_SHOPPING_CART',
            self::ManualTest => '—',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::PurchaseApproved, self::PurchaseFunnel => 'Vendas aprovadas',
            self::PurchaseOutOfShoppingCart => 'Recuperação de vendas',
            self::PurchaseComplete, self::PurchaseCanceled, self::PurchaseBilletPrinted, self::PurchaseProtest,
            self::PurchaseRefunded, self::PurchaseChargeback, self::PurchaseExpired,
            self::PurchaseDelayed => 'Pós-venda e pagamentos',
            self::ManualTest => 'Sistema',
        };
    }

    public function groupSortOrder(): int
    {
        return match ($this->group()) {
            'Vendas aprovadas' => 1,
            'Recuperação de vendas' => 2,
            'Pós-venda e pagamentos' => 3,
            'Sistema' => 4,
            default => 99,
        };
    }

    public function systemAction(): string
    {
        return match ($this) {
            self::PurchaseApproved => PurchaseWebhookAction::GrantAccess->label(),
            self::PurchaseFunnel => PurchaseWebhookAction::AcknowledgeFunnel->label(),
            self::PurchaseComplete => 'Somente registrar',
            self::PurchaseOutOfShoppingCart => 'Somente notificar (sem compra aprovada)',
            self::ManualTest => 'Não dispara automaticamente',
            default => PurchaseWebhookAction::NotifyOnly->label(),
        };
    }

    /**
     * @return list<string>
     */
    public function placeholders(): array
    {
        $common = ['{nome}', '{email}', '{telefone}', '{producto}', '{transacao}', '{evento}'];

        return match ($this) {
            self::PurchaseApproved, self::ManualTest => [
                ...$common,
                '{link_acceso}',
                '{moeda}',
                '{valor}',
            ],
            self::PurchaseOutOfShoppingCart => [
                ...$common,
                '{link_checkout}',
                '{moeda}',
                '{valor}',
            ],
            default => [...$common, '{moeda}', '{valor}'],
        };
    }

    public function defaultBody(): string
    {
        return match ($this) {
            self::PurchaseApproved => "¡Hola {nome}! Su acceso a la Biblioteca Bíblica Digital ya está listo.\n\nEntre con su correo {email} en:\n{link_acceso}",
            self::PurchaseComplete => "Hola {nome}, registramos la finalización de su compra de {producto}.\n\nTransacción: {transacao}",
            self::PurchaseFunnel => "¡Hola {nome}! Confirmamos su compra adicional: {producto}.\n\nGracias por confiar en nosotros.",
            self::PurchaseCanceled => "Hola {nome}, su compra de {producto} fue cancelada.\n\nSi tiene dudas, responda este mensaje.",
            self::PurchaseBilletPrinted => "Hola {nome}, recibimos su solicitud de boleto para {producto}.\n\nComplete el pago para activar su acceso.",
            self::PurchaseProtest => "Hola {nome}, recibimos su solicitud de reembolso referente a {producto}.\n\nNuestro equipo revisará su caso.",
            self::PurchaseRefunded => "Hola {nome}, su compra de {producto} fue reembolsada.\n\nTransacción: {transacao}",
            self::PurchaseChargeback => "Hola {nome}, se registró un chargeback en la transacción {transacao}.\n\nProducto: {producto}",
            self::PurchaseExpired => "Hola {nome}, su oportunidad de compra de {producto} expiró.\n\nSi aún le interesa, puede volver a adquirirlo.",
            self::PurchaseDelayed => "Hola {nome}, su pago de {producto} está pendiente.\n\nPor favor, regularice para activar su acceso.",
            self::PurchaseOutOfShoppingCart => "Hola {nome}, notamos que salió del checkout de {producto} sin finalizar su compra.\n\nSi aún le interesa, puede completar aquí:\n{link_checkout}",
            self::ManualTest => "¡Hola {nome}! Este es un mensaje de prueba de la Biblioteca Bíblica Digital.\n\nSi recibió esto, la integración con WhatsApp está funcionando.",
        };
    }

    public function defaultEnabled(): bool
    {
        return match ($this) {
            self::PurchaseApproved, self::ManualTest => true,
            default => false,
        };
    }

    public function isAutomatic(): bool
    {
        return $this !== self::ManualTest;
    }

    public function dispatchTrigger(): WhatsAppDispatchTrigger
    {
        return match ($this) {
            self::ManualTest => WhatsAppDispatchTrigger::ManualTest,
            default => WhatsAppDispatchTrigger::PurchaseWebhook,
        };
    }

    public static function fromHotmartEvent(string $hotmartEvent, PurchaseWebhookAction $action): ?self
    {
        $event = strtoupper(trim($hotmartEvent));

        return match (true) {
            $event === 'PURCHASE_APPROVED' && $action === PurchaseWebhookAction::GrantAccess => self::PurchaseApproved,
            $event === 'PURCHASE_COMPLETE' => self::PurchaseComplete,
            $event === 'PURCHASE_APPROVED' && $action === PurchaseWebhookAction::AcknowledgeFunnel => self::PurchaseFunnel,
            $event === 'PURCHASE_CANCELED' => self::PurchaseCanceled,
            $event === 'PURCHASE_BILLET_PRINTED' => self::PurchaseBilletPrinted,
            $event === 'PURCHASE_PROTEST' => self::PurchaseProtest,
            $event === 'PURCHASE_REFUNDED' => self::PurchaseRefunded,
            $event === 'PURCHASE_CHARGEBACK' => self::PurchaseChargeback,
            $event === 'PURCHASE_EXPIRED' => self::PurchaseExpired,
            $event === 'PURCHASE_DELAYED' => self::PurchaseDelayed,
            $event === 'PURCHASE_OUT_OF_SHOPPING_CART' => self::PurchaseOutOfShoppingCart,
            default => null,
        };
    }

    /**
     * @return list<self>
     */
    public static function creatableCases(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $event) => $event !== self::ManualTest,
        ));
    }

    /**
     * @return list<self>
     */
    public static function automaticCases(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $event) => $event->isAutomatic()
        ));
    }
}
