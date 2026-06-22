<?php

namespace App\Services;

use App\Enums\WhatsAppFlowTriggerType;
use App\Models\WhatsAppFlow;
use App\Models\WhatsAppMessageTrigger;
use App\Support\MessageTriggerNormalizer;

class WhatsAppMessageTriggerMatcher
{
    public function resolveFlowForInboundMessage(?string $instanceName, ?string $messageText): ?WhatsAppFlow
    {
        if (blank($instanceName)) {
            return null;
        }

        $normalizedMessage = MessageTriggerNormalizer::normalize($messageText);

        if ($normalizedMessage) {
            $flow = $this->resolveMessageTriggerFlow($instanceName, $normalizedMessage);

            if ($flow) {
                return $flow;
            }
        }

        return $this->resolveFirstMessageFlowForInstance($instanceName);
    }

    public function findMatchingTrigger(?string $messageText): ?WhatsAppMessageTrigger
    {
        $normalizedMessage = MessageTriggerNormalizer::normalize($messageText);

        if (! $normalizedMessage) {
            return null;
        }

        return WhatsAppMessageTrigger::query()
            ->where('is_active', true)
            ->where('message_hash', MessageTriggerNormalizer::hash($normalizedMessage))
            ->first();
    }

    private function resolveMessageTriggerFlow(string $instanceName, string $normalizedMessage): ?WhatsAppFlow
    {
        $triggerIds = WhatsAppMessageTrigger::query()
            ->where('is_active', true)
            ->where('message_hash', MessageTriggerNormalizer::hash($normalizedMessage))
            ->pluck('id');

        if ($triggerIds->isEmpty()) {
            return null;
        }

        $flows = WhatsAppFlow::query()
            ->where('trigger_type', WhatsAppFlowTriggerType::MessageTrigger)
            ->whereIn('message_trigger_id', $triggerIds)
            ->where('is_active', true)
            ->where('steps_count', '>', 0)
            ->get();

        return $flows->first(function (WhatsAppFlow $flow) use ($instanceName): bool {
            $resolved = $flow->resolveInstanceName();

            return filled($resolved) && strcasecmp($resolved, $instanceName) === 0;
        })?->loadMissing('messageTrigger');
    }

    private function resolveFirstMessageFlowForInstance(string $instanceName): ?WhatsAppFlow
    {
        $flows = WhatsAppFlow::query()
            ->where('trigger_type', WhatsAppFlowTriggerType::FirstMessage)
            ->where('is_active', true)
            ->where('steps_count', '>', 0)
            ->get();

        return $flows->first(function (WhatsAppFlow $flow) use ($instanceName): bool {
            $resolved = $flow->resolveInstanceName();

            return filled($resolved) && strcasecmp($resolved, $instanceName) === 0;
        })?->loadMissing('messageTrigger');
    }
}
