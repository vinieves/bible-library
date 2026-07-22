<?php

namespace App\Filament\Resources\EmailBroadcastResource\Pages;

use App\Filament\Resources\EmailBroadcastResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmailBroadcasts extends ListRecords
{
    protected static string $resource = EmailBroadcastResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nova campanha'),
        ];
    }
}
