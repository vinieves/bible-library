<?php

namespace App\Filament\Resources\WhatsAppFlowResource\Pages;

use App\Enums\WhatsAppFlowTriggerType;
use App\Filament\Resources\WhatsAppFlowResource;
use App\Services\EvolutionWebhookRegistrationService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;

class EditWhatsAppFlow extends EditRecord
{
    protected static string $resource = WhatsAppFlowResource::class;

    private static bool $flowAccordionHookRegistered = false;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        if (! self::$flowAccordionHookRegistered) {
            FilamentView::registerRenderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => view('filament.components.flow-accordion')->render(),
            );

            self::$flowAccordionHookRegistered = true;
        }
    }

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }

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
                    $instanceName = $this->record?->resolveInstanceName();

                    if (blank($instanceName)) {
                        Notification::make()
                            ->title('Defina a instância do fluxo')
                            ->body('Salve o fluxo com a instância WhatsApp antes de registrar o webhook.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $result = $registration->registerInstanceWebhook($instanceName);

                    if ($result['success']) {
                        Notification::make()
                            ->title('Webhook registrado')
                            ->body("Instância {$instanceName}: {$result['message']}")
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
