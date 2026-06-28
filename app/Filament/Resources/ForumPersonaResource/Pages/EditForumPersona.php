<?php

namespace App\Filament\Resources\ForumPersonaResource\Pages;

use App\Filament\Resources\ForumPersonaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditForumPersona extends EditRecord
{
    protected static string $resource = ForumPersonaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
