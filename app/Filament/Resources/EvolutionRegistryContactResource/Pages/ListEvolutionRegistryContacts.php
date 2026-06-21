<?php

namespace App\Filament\Resources\EvolutionRegistryContactResource\Pages;

use App\Filament\Resources\EvolutionRegistryContactResource;
use App\Filament\Resources\EvolutionWebhookLogResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListEvolutionRegistryContacts extends ListRecords
{
    protected static string $resource = EvolutionRegistryContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('webhooksEvolution')
                ->label('Webhooks Evolution (bruto)')
                ->icon('heroicon-o-inbox-arrow-down')
                ->url(EvolutionWebhookLogResource::getUrl('index')),
        ];
    }
}
