<?php

namespace App\Filament\Resources\BibleTopicResource\Pages;

use App\Filament\Resources\BibleTopicResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBibleTopic extends EditRecord
{
    protected static string $resource = BibleTopicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
