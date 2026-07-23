<?php

namespace App\Filament\Resources\EmailBroadcastResource\Pages;

use App\Filament\Resources\EmailBroadcastResource;
use Filament\Resources\Pages\EditRecord;

class EditEmailBroadcast extends EditRecord
{
    protected static string $resource = EmailBroadcastResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EmailBroadcastResource::testAction(),
            EmailBroadcastResource::dispatchAction(),
            EmailBroadcastResource::cancelAction(),
            EmailBroadcastResource::duplicateAction(),
        ];
    }

    /** Converte o array de e-mails de volta para texto (uma linha por e-mail) ao editar. */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (is_array($data['email_list'] ?? null)) {
            $data['email_list'] = implode("\n", $data['email_list']);
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return EmailBroadcastResource::normalizeAudienceData($data);
    }
}
