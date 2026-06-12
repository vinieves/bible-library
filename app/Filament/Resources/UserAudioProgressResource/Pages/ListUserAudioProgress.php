<?php

namespace App\Filament\Resources\UserAudioProgressResource\Pages;

use App\Filament\Resources\UserAudioProgressResource;
use Filament\Resources\Pages\ListRecords;

class ListUserAudioProgress extends ListRecords
{
    protected static string $resource = UserAudioProgressResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
