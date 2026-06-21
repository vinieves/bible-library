<?php

namespace App\Services;

use App\Enums\EvolutionRegistryEventDirection;
use App\Models\EvolutionRegistryContact;
use App\Models\EvolutionRegistryEvent;
use App\Models\EvolutionWebhookLog;
use App\Models\WhatsAppFlowExecution;
use App\Models\WhatsAppInboundContact;
use App\Services\Webhooks\PhoneNumberQuery;
use Illuminate\Support\Facades\DB;

class EvolutionRegistryService
{
    public function __construct(
        private readonly EvolutionWebhookPayloadAnalyzer $analyzer,
    ) {}

    public function recordFromWebhookLog(EvolutionWebhookLog $log): void
    {
        if (EvolutionRegistryEvent::query()->where('evolution_webhook_log_id', $log->id)->exists()) {
            return;
        }

        $payload = is_array($log->payload) ? $log->payload : [];
        $entries = $this->analyzer->analyze($payload, $log->route_slug);
        $occurredAt = $log->created_at ?? now();
        $instance = filled($log->instance) ? trim((string) $log->instance) : null;

        DB::transaction(function () use ($entries, $log, $payload, $occurredAt, $instance): void {
            foreach ($entries as $entry) {
                $contact = null;

                if (filled($entry['phone_normalized']) && filled($instance)) {
                    $contact = $this->upsertContact(
                        phone: (string) $entry['phone_normalized'],
                        instance: $instance,
                        contactName: $entry['contact_name'],
                        remoteJid: $entry['remote_jid'],
                        direction: $entry['direction'],
                        messagePreview: $entry['message_preview'],
                        occurredAt: $occurredAt,
                    );
                }

                EvolutionRegistryEvent::query()->create([
                    'registry_contact_id' => $contact?->id,
                    'evolution_webhook_log_id' => $log->id,
                    'event' => $log->event ?? 'UNKNOWN',
                    'instance_name' => $instance,
                    'phone_normalized' => $entry['phone_normalized'],
                    'remote_jid' => $entry['remote_jid'],
                    'direction' => $entry['direction'],
                    'contact_name' => $entry['contact_name'],
                    'summary' => $entry['summary'],
                    'message_preview' => $entry['message_preview'],
                    'from_me' => $entry['from_me'],
                    'route_slug' => $log->route_slug,
                    'flow_triggered' => false,
                    'occurred_at' => $occurredAt,
                    'payload' => $payload ?: null,
                ]);
            }
        });
    }

    public function syncContactMetadata(EvolutionRegistryContact $contact): void
    {
        $hasInbound = WhatsAppInboundContact::query()
            ->tap(fn ($query) => PhoneNumberQuery::whereMatchesPhone($query, 'phone_normalized', $contact->phone_normalized))
            ->exists();

        $flowCount = WhatsAppFlowExecution::query()
            ->tap(fn ($query) => PhoneNumberQuery::whereMatchesPhone($query, 'phone_normalized', $contact->phone_normalized))
            ->whereRaw('LOWER(instance_name) = ?', [strtolower(trim($contact->instance_name))])
            ->count();

        $contact->update([
            'has_inbound_contact' => $hasInbound,
            'flow_executions_count' => $flowCount,
        ]);
    }

    private function upsertContact(
        string $phone,
        string $instance,
        ?string $contactName,
        ?string $remoteJid,
        EvolutionRegistryEventDirection $direction,
        ?string $messagePreview,
        \DateTimeInterface $occurredAt,
    ): EvolutionRegistryContact {
        /** @var EvolutionRegistryContact $contact */
        $contact = EvolutionRegistryContact::query()->firstOrCreate(
            [
                'phone_normalized' => $phone,
                'instance_name' => $instance,
            ],
            [
                'contact_name' => $contactName,
                'remote_jid' => $remoteJid,
                'events_count' => 0,
                'inbound_count' => 0,
                'outbound_count' => 0,
                'flow_executions_count' => 0,
                'has_inbound_contact' => false,
                'first_seen_at' => $occurredAt,
                'last_event_at' => $occurredAt,
            ],
        );

        $updates = [
            'events_count' => $contact->events_count + 1,
            'last_event_at' => $occurredAt,
        ];

        if (filled($contactName)) {
            $updates['contact_name'] = $contactName;
        }

        if (filled($remoteJid)) {
            $updates['remote_jid'] = $remoteJid;
        }

        if (filled($messagePreview)) {
            $updates['last_message_preview'] = $messagePreview;
        }

        if ($direction === EvolutionRegistryEventDirection::Inbound) {
            $updates['inbound_count'] = $contact->inbound_count + 1;
            $updates['last_inbound_at'] = $occurredAt;
        }

        if ($direction === EvolutionRegistryEventDirection::Outbound) {
            $updates['outbound_count'] = $contact->outbound_count + 1;
            $updates['last_outbound_at'] = $occurredAt;
        }

        $contact->update($updates);

        $this->syncContactMetadata($contact);

        return $contact->fresh();
    }

    public function markFlowTriggeredForWebhookLog(?int $logId): void
    {
        if (! $logId) {
            return;
        }

        EvolutionRegistryEvent::query()
            ->where('evolution_webhook_log_id', $logId)
            ->update(['flow_triggered' => true]);
    }
}
