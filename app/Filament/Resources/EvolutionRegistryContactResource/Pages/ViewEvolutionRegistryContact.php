<?php

namespace App\Filament\Resources\EvolutionRegistryContactResource\Pages;

use App\Enums\WhatsAppFlowExecutionStatus;
use App\Filament\Resources\EvolutionRegistryContactResource;
use App\Filament\Resources\EvolutionWebhookLogResource;
use App\Filament\Resources\WhatsAppFlowExecutionResource;
use App\Models\EvolutionRegistryContact;
use App\Models\EvolutionRegistryEvent;
use App\Models\WhatsAppFlowExecution;
use App\Services\Webhooks\PhoneNumberQuery;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewEvolutionRegistryContact extends ViewRecord
{
    protected static string $resource = EvolutionRegistryContactResource::class;

    protected function resolveRecord(int | string $key): Model
    {
        return parent::resolveRecord($key)->loadMissing(['events.webhookLog']);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('webhooksEvolution')
                ->label('Ver webhooks brutos')
                ->icon('heroicon-o-inbox-arrow-down')
                ->url(EvolutionWebhookLogResource::getUrl('index')),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var EvolutionRegistryContact $record */
        $record = $this->getRecord();

        $data['events_timeline'] = $this->buildEventsTimeline($record);
        $data['flow_executions_summary'] = $this->buildFlowExecutionsSummary($record);

        return $data;
    }

    private function buildEventsTimeline(EvolutionRegistryContact $contact): string
    {
        $events = $contact->events;

        if ($events->isEmpty()) {
            return 'Nenhum evento registrado para este contato.';
        }

        return $events
            ->sortByDesc('occurred_at')
            ->map(function (EvolutionRegistryEvent $event): string {
                $when = EvolutionRegistryContactResource::formatDateTime($event->occurred_at);
                $direction = EvolutionRegistryContactResource::directionLabel($event->direction);
                $flow = $event->flow_triggered ? 'Sim' : 'Não';
                $fromMe = $event->from_me === null
                    ? '—'
                    : ($event->from_me ? 'fromMe=true (enviada)' : 'fromMe=false (recebida)');
                $preview = filled($event->message_preview)
                    ? '"'.mb_substr($event->message_preview, 0, 200).'"'
                    : '—';
                $webhookLink = $event->evolution_webhook_log_id
                    ? " | Webhook #{$event->evolution_webhook_log_id}"
                    : '';

                return implode("\n", [
                    "[{$when}] {$event->event}",
                    "  Direção: {$direction} | {$fromMe}",
                    "  Resumo: {$event->summary}",
                    "  Mensagem: {$preview}",
                    "  Fluxo disparado/retomado: {$flow}{$webhookLink}",
                ]);
            })
            ->implode("\n\n");
    }

    private function buildFlowExecutionsSummary(EvolutionRegistryContact $contact): string
    {
        $executions = WhatsAppFlowExecution::query()
            ->tap(fn ($query) => PhoneNumberQuery::whereMatchesPhone($query, 'phone_normalized', $contact->phone_normalized))
            ->whereRaw('LOWER(instance_name) = ?', [strtolower(trim($contact->instance_name))])
            ->with('flow')
            ->orderByDesc('id')
            ->get();

        if ($executions->isEmpty()) {
            return 'Nenhuma execução de fluxo para este telefone e instância.';
        }

        return $executions
            ->map(function (WhatsAppFlowExecution $execution): string {
                $status = $execution->status instanceof WhatsAppFlowExecutionStatus
                    ? $execution->status->label()
                    : (string) $execution->status;
                $started = EvolutionRegistryContactResource::formatDateTime($execution->started_at);
                $flowName = $execution->flow?->name ?? '—';
                $url = WhatsAppFlowExecutionResource::getUrl('view', ['record' => $execution]);

                return "#{$execution->id} {$flowName} — {$status} | Passo {$execution->current_step}/{$execution->total_steps} | Início: {$started}\n   Detalhes: {$url}";
            })
            ->implode("\n\n");
    }
}
