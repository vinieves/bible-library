<?php

namespace App\Services;

use App\Support\IntegrationSettings;
use Illuminate\Support\Facades\View;

class EmailBodyRenderer
{
  public const ACCESS_MARKER_PREFIX = '[[EMAIL_BTN:access|';

    public const CHECKOUT_MARKER_PREFIX = '[[EMAIL_BTN:checkout|';

    public const MARKER_SUFFIX = ']]';

    public static function accessMarker(string $url): string
    {
        return self::ACCESS_MARKER_PREFIX.$url.self::MARKER_SUFFIX;
    }

    public static function checkoutMarker(string $url): string
    {
        return self::CHECKOUT_MARKER_PREFIX.$url.self::MARKER_SUFFIX;
    }

    public function bodyToHtml(string $bodyWithMarkers, array $inlineImages = []): string
    {
        $bodyWithMarkers = $this->resolveInlineImagePlaceholders($bodyWithMarkers, $inlineImages);

        $pattern = '/\[\[EMAIL_BTN:(access|checkout)\|([^\]]+)\]\]/';

        $segments = preg_split($pattern, $bodyWithMarkers, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($segments === false) {
            return $this->renderTextSegment($bodyWithMarkers);
        }

        $html = '';

        foreach ($segments as $index => $segment) {
            if ($index % 3 === 0) {
                $html .= $this->renderTextSegment($segment);

                continue;
            }

            if ($index % 3 === 2) {
                continue;
            }

            $type = $segment;
            $url = $segments[$index + 1] ?? '';

            if (filled($url)) {
                $html .= $this->buttonHtml(
                    $url,
                    $type === 'checkout'
                        ? IntegrationSettings::emailCheckoutButtonText()
                        : IntegrationSettings::emailButtonText(),
                );
            }
        }

        return $html;
    }

    public function renderFromMarkedBody(string $bodyWithMarkers, array $inlineImages = []): string
    {
        return $this->renderEmail($this->bodyToHtml($bodyWithMarkers, $inlineImages));
    }

    public function buttonHtml(string $url, string $label): string
    {
        $color = IntegrationSettings::emailButtonColor();
        $safeUrl = e($url);
        $safeLabel = e($label);

        return <<<HTML
<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin: 20px auto;">
<tr>
<td align="center" style="border-radius: 8px; background-color: {$color};">
<a href="{$safeUrl}" target="_blank" rel="noopener noreferrer" style="display: inline-block; padding: 14px 32px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 16px; font-weight: 600; color: #ffffff; text-decoration: none; border-radius: 8px;">{$safeLabel}</a>
</td>
</tr>
</table>
HTML;
    }

    public function renderEmail(string $bodyHtml): string
    {
        return View::make('mail.hotmart-transactional', [
            'bodyHtml' => $bodyHtml,
            'logoUrl' => IntegrationSettings::emailLogoUrl(),
            'brandName' => IntegrationSettings::mailFromName(),
            'siteUrl' => rtrim((string) config('app.url'), '/'),
        ])->render();
    }

    /**
     * @param  array<string, string>  $inlineImages
     */
    private function resolveInlineImagePlaceholders(string $body, array $inlineImages): string
    {
        if ($inlineImages === []) {
            return $body;
        }

        return preg_replace_callback(
            '/\{imagen:([a-z0-9\-_]+)\}/i',
            function (array $matches) use ($inlineImages): string {
                $slug = strtolower(str_replace('_', '-', $matches[1]));

                foreach ($inlineImages as $key => $path) {
                    $keySlug = is_string($key) ? strtolower(str_replace('_', '-', (string) $key)) : '';

                    if ($keySlug === $slug && filled($path)) {
                        return '[[EMAIL_IMG|'.$path.']]';
                    }
                }

                return $matches[0];
            },
            $body,
        ) ?? $body;
    }

    private function renderTextSegment(string $segment): string
    {
        $pattern = '/\[\[EMAIL_IMG\|([^\]]+)\]\]/';
        $parts = preg_split($pattern, $segment, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($parts === false) {
            return nl2br(e($segment), false);
        }

        $html = '';

        foreach ($parts as $index => $part) {
            if ($index % 2 === 0) {
                $html .= nl2br(e($part), false);

                continue;
            }

            $html .= $this->inlineImageHtml($part);
        }

        return $html;
    }

    private function inlineImageHtml(string $storagePath): string
    {
        $url = e(IntegrationSettings::publicStorageUrl($storagePath));

        return <<<HTML
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 16px 0;">
<tr>
<td align="center">
<img src="{$url}" alt="" width="560" style="display: block; max-width: 100%; height: auto; border-radius: 8px;">
</td>
</tr>
</table>
HTML;
    }
}
