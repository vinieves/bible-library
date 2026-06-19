<?php

namespace App\Support;

use App\Enums\WebhookPlatform;
use App\Enums\WhatsAppMessageEvent;
use App\Models\WhatsAppFlow;
use App\Services\WhatsAppMessageTemplateService;
use App\Models\Setting;
use Illuminate\Support\Str;

class IntegrationSettings
{
    public static function webhookSecret(): string
    {
        $secret = Setting::getEncrypted('webhook_secret');

        if (blank($secret)) {
            $secret = Str::random(48);
            Setting::setEncrypted('webhook_secret', $secret);
        }

        return $secret;
    }

    public static function hotmartHottok(): ?string
    {
        return Setting::getEncrypted('hotmart_hottok');
    }

    public static function webhookUrl(WebhookPlatform $platform): string
    {
        return url('/api/webhooks/'.$platform->value);
    }

    public static function evolutionWebhookUrl(): string
    {
        return url('/api/webhooks/evolution');
    }

    public static function whatsappEnabled(): bool
    {
        return filter_var(Setting::get('whatsapp_enabled', '0'), FILTER_VALIDATE_BOOL);
    }

    public static function evolutionBaseUrl(): ?string
    {
        return filled(Setting::get('evolution_base_url'))
            ? rtrim((string) Setting::get('evolution_base_url'), '/')
            : null;
    }

    public static function evolutionInstance(): ?string
    {
        return static::evolutionInstanceForMessages();
    }

    public static function evolutionInstanceForMessages(): ?string
    {
        $instance = Setting::get('evolution_instance_messages') ?: Setting::get('evolution_instance');

        return filled($instance) ? (string) $instance : null;
    }

    public static function evolutionInstanceForFlows(): ?string
    {
        $instance = Setting::get('evolution_instance_flows') ?: Setting::get('evolution_instance');

        return filled($instance) ? (string) $instance : null;
    }

    /**
     * @return list<string>
     */
    public static function trustedEvolutionInstances(): array
    {
        $fromSettings = array_filter([
            static::evolutionInstanceForMessages(),
            static::evolutionInstanceForFlows(),
            filled(Setting::get('evolution_instance')) ? (string) Setting::get('evolution_instance') : null,
        ]);

        $fromFlows = WhatsAppFlow::query()
            ->where('is_active', true)
            ->whereNotNull('instance_name')
            ->where('instance_name', '!=', '')
            ->pluck('instance_name')
            ->all();

        return array_values(array_unique([...$fromSettings, ...$fromFlows]));
    }

    public static function evolutionApiKey(): ?string
    {
        return Setting::getEncrypted('evolution_api_key');
    }

    public static function whatsappTemplate(): string
    {
        return app(WhatsAppMessageTemplateService::class)
            ->body(WhatsAppMessageEvent::PurchaseApproved);
    }

    public static function evolutionApiReady(): bool
    {
        return filled(static::evolutionBaseUrl())
            && filled(static::evolutionApiKey());
    }

    public static function evolutionConfigured(): bool
    {
        return static::evolutionApiReady()
            && filled(static::evolutionInstanceForMessages());
    }

    public static function evolutionConfiguredForFlows(): bool
    {
        return static::evolutionApiReady()
            && filled(static::evolutionInstanceForFlows());
    }

    public static function regenerateWebhookSecret(): string
    {
        $secret = Str::random(48);
        Setting::setEncrypted('webhook_secret', $secret);

        return $secret;
    }
}
