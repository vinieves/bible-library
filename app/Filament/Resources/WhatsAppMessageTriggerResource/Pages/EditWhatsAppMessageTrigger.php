<?php

namespace App\Filament\Resources\WhatsAppMessageTriggerResource\Pages;

use App\Filament\Resources\WhatsAppMessageTriggerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWhatsAppMessageTrigger extends EditRecord
{
    protected static string $resource = WhatsAppMessageTriggerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
