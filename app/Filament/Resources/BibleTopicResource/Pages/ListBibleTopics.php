<?php

namespace App\Filament\Resources\BibleTopicResource\Pages;

use App\Filament\Resources\BibleTopicResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBibleTopics extends ListRecords
{
    protected static string $resource = BibleTopicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
