<?php

namespace App\Enums;

enum EmailBroadcastStatus: string
{
    case Draft = 'draft';
    case Queued = 'queued';
    case Sending = 'sending';
    case Sent = 'sent';
    case Partial = 'partial';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Queued => 'Na fila',
            self::Sending => 'Enviando',
            self::Sent => 'Enviado',
            self::Partial => 'Enviado com falhas',
            self::Failed => 'Falhou',
            self::Cancelled => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Queued => 'info',
            self::Sending => 'warning',
            self::Sent => 'success',
            self::Partial => 'warning',
            self::Failed => 'danger',
            self::Cancelled => 'gray',
        };
    }
}
