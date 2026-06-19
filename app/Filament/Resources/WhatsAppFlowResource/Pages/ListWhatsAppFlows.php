<?php

namespace App\Filament\Resources\WhatsAppFlowResource\Pages;

use App\Filament\Resources\WhatsAppFlowResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWhatsAppFlows extends ListRecords
{
    protected static string $resource = WhatsAppFlowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('+ Criar Fluxo'),
        ];
    }
}
