<?php

namespace App\Filament\Resources\AudioCategoryResource\Pages;

use App\Filament\Resources\AudioCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAudioCategories extends ListRecords
{
    protected static string $resource = AudioCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
