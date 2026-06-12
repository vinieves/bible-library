<?php

namespace App\Filament\Resources\AudioTrackResource\Pages;

use App\Filament\Resources\AudioTrackResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAudioTracks extends ListRecords
{
    protected static string $resource = AudioTrackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
