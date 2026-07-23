<?php

namespace App\Filament\Resources\WebhookLogResource\Pages;

use App\Filament\Concerns\ExportsTableData;
use App\Filament\Resources\WebhookLogResource;
use App\Models\WebhookLog;
use Filament\Resources\Pages\ListRecords;

class ListWebhookLogs extends ListRecords
{
    use ExportsTableData;

    protected static string $resource = WebhookLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->tableExportActions(
                'logs-webhook',
                ['Recebido em', 'Plataforma', 'Evento', 'Resultado', 'HTTP', 'E-mail', 'Código do produto', 'Transação', 'ID da compra', 'IP', 'Mensagem'],
                fn (WebhookLog $r): array => [
                    $r->created_at?->format('d/m/Y H:i:s'),
                    $r->platformLabel(),
                    $r->event,
                    $r->processing_status instanceof \App\Enums\WebhookLogStatus ? $r->processing_status->label() : $r->processing_status,
                    $r->http_status,
                    $r->email,
                    $r->product_code,
                    $r->external_reference,
                    $r->purchase_id,
                    $r->ip_address,
                    $r->message,
                ],
            ),
        ];
    }
}
