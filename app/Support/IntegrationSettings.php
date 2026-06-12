<?php

namespace App\Support;

use App\Enums\WebhookPlatform;
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
        $instance = Setting::get('evolution_instance');

        return filled($instance) ? (string) $instance : null;
    }

    public static function evolutionApiKey(): ?string
    {
        return Setting::getEncrypted('evolution_api_key');
    }

    public static function whatsappTemplate(): string
    {
        return (string) Setting::get(
            'whatsapp_welcome_template',
            "¡Hola {nome}! Su acceso a la Biblioteca Bíblica Digital ya está listo.\n\nEntre con su correo {email} en:\n{link_acceso}"
        );
    }

    public static function regenerateWebhookSecret(): string
    {
        $secret = Str::random(48);
        Setting::setEncrypted('webhook_secret', $secret);

        return $secret;
    }
}
