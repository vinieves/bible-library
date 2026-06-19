<?php

namespace App\Filament\Resources\WhatsAppFlowResource\Pages;

use App\Filament\Resources\WhatsAppFlowResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWhatsAppFlow extends CreateRecord
{
    protected static string $resource = WhatsAppFlowResource::class;

    protected function getRedirectUrl(): string
    {
        return WhatsAppFlowResource::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
