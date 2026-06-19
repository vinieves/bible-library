<?php

namespace App\Filament\Resources\WhatsAppFlowResource\Pages;

use App\Enums\WhatsAppFlowTriggerType;
use App\Filament\Resources\WhatsAppFlowResource;
use App\Services\EvolutionWebhookRegistrationService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditWhatsAppFlow extends EditRecord
{
    protected static string $resource = WhatsAppFlowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('registerEvolutionWebhook')
                ->label('Registrar webhook na Evolution')
                ->icon('heroicon-o-link')
                ->color('warning')
                ->visible(fn (): bool => $this->record?->trigger_type === WhatsAppFlowTriggerType::FirstMessage)
                ->requiresConfirmation()
                ->modalHeading('Registrar webhook MESSAGES_UPSERT')
                ->modalDescription('Isso configura a Evolution API para enviar mensagens recebidas para o Bible Library. A URL usada é a exibida nas configurações do fluxo.')
                ->action(function (EvolutionWebhookRegistrationService $registration): void {
                    $result = $registration->registerInstanceWebhook();

                    if ($result['success']) {
                        Notification::make()
                            ->title('Webhook registrado')
                            ->body($result['message'])
                            ->success()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Falha ao registrar webhook')
                        ->body($result['message'])
                        ->danger()
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $this->record->update([
            'steps_count' => $this->record->steps()->count(),
        ]);
    }
}
