<?php

namespace App\Filament\Resources\PushNotificationResource\Pages;

use App\Filament\Resources\PushNotificationResource;
use App\Models\PushNotification;
use App\Services\PushNotificationService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePushNotification extends CreateRecord
{
    protected static string $resource = PushNotificationResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['status'] = 'scheduled';

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var PushNotification $record */
        $record = $this->getRecord();

        // "Enviar agora" dispara imediatamente (não depende do scheduler).
        if ($record->schedule_type === 'now') {
            app(PushNotificationService::class)->dispatch($record);

            Notification::make()
                ->title('Notificação enviada')
                ->body('Enfileirada para todos os inscritos.')
                ->success()
                ->send();
        }
    }
}
