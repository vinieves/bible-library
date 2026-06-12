<?php

namespace App\Filament\Resources\WebhookLogResource\Pages;

use App\Filament\Resources\WebhookLogResource;
use App\Filament\Resources\WebhookLogResource\Actions\ReprocessWebhookAction;
use Filament\Resources\Pages\ViewRecord;

class ViewWebhookLog extends ViewRecord
{
    protected static string $resource = WebhookLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ReprocessWebhookAction::make(),
        ];
    }
}
