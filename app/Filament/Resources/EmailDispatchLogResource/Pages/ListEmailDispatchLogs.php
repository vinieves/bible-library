<?php

namespace App\Filament\Resources\EmailDispatchLogResource\Pages;

use App\Filament\Concerns\ExportsTableData;
use App\Filament\Resources\EmailDispatchLogResource;
use App\Models\EmailDispatchLog;
use Filament\Resources\Pages\ListRecords;

class ListEmailDispatchLogs extends ListRecords
{
    use ExportsTableData;

    protected static string $resource = EmailDispatchLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->tableExportActions(
                'disparos-email',
                ['Recebido em', 'Gatilho', 'Evento', 'Status', 'Remetente', 'Destinatário', 'Assunto', 'Tentativa', 'Erro'],
                fn (EmailDispatchLog $r): array => [
                    $r->created_at?->format('d/m/Y H:i:s'),
                    $r->trigger?->label(),
                    $r->message_event?->value,
                    $r->status?->label(),
                    $r->from_address,
                    $r->recipient_email,
                    $r->subject,
                    $r->attempt,
                    $r->error_message,
                ],
            ),
        ];
    }
}
