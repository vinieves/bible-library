<?php

namespace App\Services;

use App\DataTransferObjects\EvolutionInboundMessageData;
use App\Enums\WhatsAppFlowExecutionStatus;
use App\Models\WhatsAppFlowExecution;
use App\Models\WhatsAppInboundContact;
use App\Services\Webhooks\PhoneNumberQuery;

class WhatsAppFlowContactNameService
{
    public function syncFromInboundMessage(EvolutionInboundMessageData $message): void
    {
        if (blank($message->pushName)) {
            return;
        }

        $name = trim($message->pushName);

        WhatsAppInboundContact::query()
            ->tap(fn ($query) => PhoneNumberQuery::whereMatchesPhone($query, 'phone_normalized', $message->phoneNormalized))
            ->update(['push_name' => $name]);

        WhatsAppFlowExecution::query()
            ->tap(fn ($query) => PhoneNumberQuery::whereMatchesPhone($query, 'phone_normalized', $message->phoneNormalized))
            ->when(
                filled($message->instance),
                fn ($query) => $query->whereRaw('LOWER(instance_name) = ?', [strtolower(trim($message->instance))]),
            )
            ->whereIn('status', [
                WhatsAppFlowExecutionStatus::Pending,
                WhatsAppFlowExecutionStatus::Running,
                WhatsAppFlowExecutionStatus::Waiting,
            ])
            ->update(['contact_name' => $name]);
    }

    public function resolveForPhone(string $phoneNormalized): ?string
    {
        $contact = WhatsAppInboundContact::query()
            ->tap(fn ($query) => PhoneNumberQuery::whereMatchesPhone($query, 'phone_normalized', $phoneNormalized))
            ->first();

        if (filled($contact?->push_name)) {
            return trim((string) $contact->push_name);
        }

        return null;
    }
}
