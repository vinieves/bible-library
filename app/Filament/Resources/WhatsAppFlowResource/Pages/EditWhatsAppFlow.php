<?php

namespace App\Filament\Resources\WhatsAppFlowResource\Pages;

use App\Filament\Resources\WhatsAppFlowResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWhatsAppFlow extends EditRecord
{
    protected static string $resource = WhatsAppFlowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $this->record->update([
            'steps_count' => $this->record->steps()->count(),
        ]);
    }
}
