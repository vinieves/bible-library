<?php

namespace App\Filament\Resources\WhatsAppDispatchLogResource\Pages;

use App\Filament\Resources\WhatsAppDispatchLogResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewWhatsAppDispatchLog extends ViewRecord
{
    protected static string $resource = WhatsAppDispatchLogResource::class;

    protected function resolveRecord(int|string $key): Model
    {
        return parent::resolveRecord($key)->loadMissing('user');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['user_email'] = $this->getRecord()->user?->email;

        return $data;
    }
}
