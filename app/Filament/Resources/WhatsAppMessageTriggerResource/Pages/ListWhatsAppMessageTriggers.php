<?php

namespace App\Filament\Resources\WhatsAppMessageTriggerResource\Pages;

use App\Filament\Resources\WhatsAppMessageTriggerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWhatsAppMessageTriggers extends ListRecords
{
    protected static string $resource = WhatsAppMessageTriggerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
