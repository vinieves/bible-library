<?php

namespace App\Filament\Resources\ForumPersonaResource\Pages;

use App\Filament\Resources\ForumPersonaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListForumPersonas extends ListRecords
{
    protected static string $resource = ForumPersonaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
