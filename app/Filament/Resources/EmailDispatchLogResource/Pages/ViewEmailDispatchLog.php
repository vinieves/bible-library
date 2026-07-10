<?php

namespace App\Filament\Resources\EmailDispatchLogResource\Pages;

use App\Filament\Resources\EmailDispatchLogResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewEmailDispatchLog extends ViewRecord
{
    protected static string $resource = EmailDispatchLogResource::class;

    protected function resolveRecord(int | string $key): Model
    {
        return parent::resolveRecord($key)->loadMissing('user');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['user_email'] = $this->getRecord()->user?->email;

        return $data;
    }
}
