<?php

namespace App\Enums;

enum WhatsAppFlowExecutionStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case Waiting = 'waiting';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Running => 'Em execução',
            self::Waiting => 'Aguardando resposta',
            self::Completed => 'Concluído',
            self::Failed => 'Falhou',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Running => 'info',
            self::Waiting => 'warning',
            self::Completed => 'success',
            self::Failed => 'danger',
        };
    }
}
