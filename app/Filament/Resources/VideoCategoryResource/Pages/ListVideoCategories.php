<?php

namespace App\Filament\Resources\VideoCategoryResource\Pages;

use App\Filament\Resources\VideoCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVideoCategories extends ListRecords
{
    protected static string $resource = VideoCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
