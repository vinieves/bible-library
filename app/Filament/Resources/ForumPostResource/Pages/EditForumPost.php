<?php

namespace App\Filament\Resources\ForumPostResource\Pages;

use App\Filament\Resources\ForumPostResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditForumPost extends EditRecord
{
    protected static string $resource = ForumPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
