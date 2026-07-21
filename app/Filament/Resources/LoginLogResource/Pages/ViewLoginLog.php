<?php

namespace App\Filament\Resources\LoginLogResource\Pages;

use App\Filament\Resources\LoginLogResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewLoginLog extends ViewRecord
{
    protected static string $resource = LoginLogResource::class;

    protected function resolveRecord(int|string $key): Model
    {
        return parent::resolveRecord($key)->loadMissing('user');
    }
}
