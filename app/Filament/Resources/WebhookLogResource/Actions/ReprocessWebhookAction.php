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
            ->visible(fn (WebhookLog $record): bool => self::canReprocess($record))
            ->requiresConfirmation()
            ->modalHeading('Reprocessar webhook')
            ->modalDescription(fn (WebhookLog $record): string => match ($record->processing_status) {
                WebhookLogStatus::Processed, WebhookLogStatus::Duplicate => 'Esta compra pode já ter sido processada. O sistema não duplicará o acesso se a transação já existir.',
                default => 'O payload salvo será processado novamente como uma nova tentativa.',
            })
            ->modalSubmitActionLabel('Reprocessar')
            ->action(function (WebhookLog $record, WebhookReprocessService $reprocessor) {
                try {
                    $source = WebhookLog::query()->findOrFail($record->getKey());

                    $newLog = $reprocessor->reprocess($source);
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
                        WebhookLogStatus::Acknowledged => match (true) {
                            str_contains((string) ($result['message'] ?? ''), 'reembolso') => 'Pedido de reembolso registrado',
                            str_contains((string) ($result['message'] ?? ''), 'checkout') => 'Abandono de checkout registrado',
                            default => 'Funil registrado sem liberação de acesso',
                        },
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

    public static function canReprocess(WebhookLog $record): bool
    {
        $payload = $record->payload;

        if (! is_array($payload) || $payload === []) {
            return false;
        }

        if ($record->platform === 'hotmart') {
            return filled($payload['event'] ?? null) && is_array($payload['data'] ?? null);
        }

        return filled($payload['product_code'] ?? null) && filled($payload['external_reference'] ?? null);
    }
}
