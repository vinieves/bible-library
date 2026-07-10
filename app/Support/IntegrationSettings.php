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

    public static function emailEnabled(): bool
    {
        return filter_var(Setting::get('email_enabled', '0'), FILTER_VALIDATE_BOOL);
    }

    public static function smtpHost(): string
    {
        return (string) Setting::get('smtp_host', 'smtp.hostinger.com');
    }

    public static function smtpPort(): int
    {
        return (int) Setting::get('smtp_port', 465);
    }

    public static function smtpEncryption(): string
    {
        $encryption = (string) Setting::get('smtp_encryption', 'ssl');

        return in_array($encryption, ['ssl', 'tls'], true) ? $encryption : 'ssl';
    }

    public static function smtpUsername(): ?string
    {
        $username = Setting::get('smtp_username');

        return filled($username) ? (string) $username : null;
    }

    public static function smtpPassword(): ?string
    {
        return Setting::getEncrypted('smtp_password');
    }

    public static function mailFromAddress(): ?string
    {
        $address = Setting::get('mail_from_address') ?: static::smtpUsername();

        return filled($address) ? (string) $address : null;
    }

    public static function mailFromName(): string
    {
        return (string) Setting::get('mail_from_name', config('app.name', 'Biblioteca Bíblica Digital'));
    }

    public static function emailSmtpConfigured(): bool
    {
        return filled(static::smtpHost())
            && filled(static::smtpUsername())
            && filled(static::smtpPassword())
            && filled(static::mailFromAddress());
    }

    public static function emailLogoUrl(): ?string
    {
        $path = Setting::get('email_logo_path');

        if (filled($path)) {
            return static::absoluteAssetUrl('/storage/'.ltrim((string) $path, '/'));
        }

        $url = Setting::get('email_logo_url');

        if (blank($url)) {
            return null;
        }

        $url = trim((string) $url);

        if (str_starts_with($url, '/')) {
            return static::absoluteAssetUrl($url);
        }

        return $url;
    }

    private static function absoluteAssetUrl(string $path): string
    {
        return rtrim((string) config('app.url'), '/').'/'.ltrim($path, '/');
    }

    public static function emailButtonColor(): string
    {
        $color = (string) Setting::get('email_button_color', '#000000');

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) ? $color : '#000000';
    }

    public static function emailButtonText(): string
    {
        return (string) Setting::get('email_button_text', 'Acceder ahora');
    }

    public static function emailCheckoutButtonText(): string
    {
        return (string) Setting::get('email_checkout_button_text', 'Completar compra');
    }

    public static function emailReplyTo(): ?string
    {
        $replyTo = Setting::get('email_reply_to');

        return filled($replyTo) ? (string) $replyTo : null;
    }
}
