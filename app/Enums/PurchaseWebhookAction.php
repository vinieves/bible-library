<?php

namespace App\Enums;

enum PurchaseWebhookAction: string
{
    case GrantAccess = 'grant_access';
    case AcknowledgeFunnel = 'acknowledge_funnel';
    case NotifyOnly = 'notify_only';
    case UnmappedProduct = 'unmapped_product';

    public function label(): string
    {
        return match ($this) {
            self::GrantAccess => 'Liberar Plan Completo',
            self::AcknowledgeFunnel => 'Registrar funil (sem liberar acesso)',
            self::NotifyOnly => 'Somente notificar',
            self::UnmappedProduct => 'Produto não mapeado',
        };
    }
}
