<?php

namespace App\Filament\Resources\WhatsAppFlowExecutionResource\Pages;

use App\Filament\Resources\WhatsAppFlowExecutionResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewWhatsAppFlowExecution extends ViewRecord
{
    protected static string $resource = WhatsAppFlowExecutionResource::class;

    protected function resolveRecord(int | string $key): Model
    {
        return parent::resolveRecord($key)->loadMissing(['flow', 'logs', 'user']);
    }
}
