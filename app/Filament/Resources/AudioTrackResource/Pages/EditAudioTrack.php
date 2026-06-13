<?php

namespace App\Filament\Resources\AudioTrackResource\Pages;

use App\Filament\Resources\AudioTrackResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAudioTrack extends EditRecord
{
    protected static string $resource = AudioTrackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
