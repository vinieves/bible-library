<?php

namespace App\Services\Webhooks;

use App\Contracts\WebhookAdapterInterface;
use App\Enums\WebhookPlatform;
use InvalidArgumentException;

class WebhookAdapterResolver
{
    public function resolve(WebhookPlatform $platform): WebhookAdapterInterface
    {
        return match ($platform) {
            WebhookPlatform::Hotmart => app(HotmartWebhookAdapter::class),
            WebhookPlatform::Generic => app(GenericWebhookAdapter::class),
        };
    }

    public function resolveFromRoute(string $platform): WebhookAdapterInterface
    {
        $resolved = WebhookPlatform::tryFromRoute($platform);

        if (! $resolved) {
            throw new InvalidArgumentException("Plataforma de webhook desconhecida: {$platform}");
        }

        return $this->resolve($resolved);
    }
}
