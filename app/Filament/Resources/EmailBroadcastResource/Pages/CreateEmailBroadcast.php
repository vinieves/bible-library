<?php

namespace App\Filament\Resources\EmailBroadcastResource\Pages;

use App\Filament\Resources\EmailBroadcastResource;
use App\Services\EmailBroadcastAudienceService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateEmailBroadcast extends CreateRecord
{
    protected static string $resource = EmailBroadcastResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return EmailBroadcastResource::normalizeAudienceData($data, Auth::id());
    }
}
