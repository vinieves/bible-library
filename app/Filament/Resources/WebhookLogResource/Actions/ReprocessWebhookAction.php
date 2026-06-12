<?php

namespace App\Filament\Resources\WebhookLogResource\Actions;

use App\Enums\WebhookLogStatus;
use App\Filament\Resources\WebhookLogResource;
use App\Models\WebhookLog;
use App\Services\Webhooks\WebhookReprocessService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use InvalidArgumentException;

class ReprocessWebhookAction
{
    public static function make(): Action
    {
        return Action::make('reprocess')
            ->label('Reprocessar')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->visible(fn (WebhookLog $record): bool => filled($record->payload))
            ->requiresConfirmation()
            ->modalHeading('Reprocessar webhook')
            ->modalDescription(fn (WebhookLog $record): string => match ($record->processing_status) {
                WebhookLogStatus::Processed, WebhookLogStatus::Duplicate => 'Esta compra pode já ter sido processada. O sistema não duplicará o acesso se a transação já existir.',
                default => 'O payload salvo será processado novamente como uma nova tentativa.',
            })
            ->modalSubmitActionLabel('Reprocessar')
            ->action(function (WebhookLog $record, WebhookReprocessService $reprocessor) {
                try {
                    $newLog = $reprocessor->reprocess($record);
                } catch (InvalidArgumentException $exception) {
                    Notification::make()
                        ->title('Não foi possível reprocessar')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                $notification = Notification::make()
                    ->title(match ($newLog->processing_status) {
                        WebhookLogStatus::Processed => 'Webhook reprocessado com sucesso',
                        WebhookLogStatus::Acknowledged => 'Funil registrado sem liberação de acesso',
                        WebhookLogStatus::Duplicate => 'Compra já havia sido processada',
                        WebhookLogStatus::Ignored => 'Webhook reprocessado (ignorado)',
                        WebhookLogStatus::Error => 'Webhook reprocessado com erro',
                        default => 'Webhook reprocessado',
                    })
                    ->body($newLog->message ?? 'Consulte o novo log para detalhes.');

                match ($newLog->processing_status) {
                    WebhookLogStatus::Processed, WebhookLogStatus::Acknowledged => $notification->success(),
                    WebhookLogStatus::Duplicate, WebhookLogStatus::Ignored => $notification->warning(),
                    default => $notification->danger(),
                };

                $notification->send();

                return redirect(WebhookLogResource::getUrl('view', ['record' => $newLog]));
            });
    }
}
