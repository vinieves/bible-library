<?php

namespace App\Filament\Resources\WhatsAppMessageTriggerResource\Pages;

use App\Filament\Resources\WhatsAppMessageTriggerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWhatsAppMessageTrigger extends CreateRecord
{
    protected static string $resource = WhatsAppMessageTriggerResource::class;

    protected function getRedirectUrl(): string
    {
        return WhatsAppMessageTriggerResource::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
