<?php

namespace App\Services;

use App\Support\IntegrationSettings;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class EmailBodyRenderer
{
    public const ACCESS_MARKER_PREFIX = '[[EMAIL_BTN:access|';

    public const CHECKOUT_MARKER_PREFIX = '[[EMAIL_BTN:checkout|';

    public const MARKER_SUFFIX = ']]';

    private bool $useCidForInlineImages = false;

    public static function accessMarker(string $url): string
    {
        return self::ACCESS_MARKER_PREFIX.$url.self::MARKER_SUFFIX;
    }

    public static function checkoutMarker(string $url): string
    {
        return self::CHECKOUT_MARKER_PREFIX.$url.self::MARKER_SUFFIX;
    }

    public static function cidForPath(string $storagePath): string
    {
        $normalized = app(EmailAttachmentResolver::class)->normalizeRelativePath($storagePath);

        return 'email-img-'.substr(hash('sha256', $normalized), 0, 20);
    }

    /**
     * @param  array<string, string>  $inlineImages
     */
    public function bodyToHtml(string $bodyWithMarkers, array $inlineImages = [], bool $useCid = false): string
    {
        $previous = $this->useCidForInlineImages;
        $this->useCidForInlineImages = $useCid;

        try {
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
        } finally {
            $this->useCidForInlineImages = $previous;
        }
    }

    /**
     * @param  array<string, string>  $inlineImages
     */
    public function renderFromMarkedBody(string $bodyWithMarkers, array $inlineImages = [], bool $useCid = false): string
    {
        return $this->renderEmail($this->bodyToHtml($bodyWithMarkers, $inlineImages, $useCid));
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
            '/\{imagen:([^\}\s]+)\}/i',
            function (array $matches) use ($inlineImages): string {
                $path = $this->findInlineImagePath($matches[1], $inlineImages);

                if ($path === null) {
                    return $matches[0];
                }

                return '[[EMAIL_IMG|'.$path.']]';
            },
            $body,
        ) ?? $body;
    }

    /**
     * @param  array<string, string>  $inlineImages
     */
    private function findInlineImagePath(string $requested, array $inlineImages): ?string
    {
        $requested = strtolower(trim($requested));
        $requestedSlug = Str::slug($requested);

        foreach ($inlineImages as $key => $path) {
            if (! filled($path)) {
                continue;
            }

            $keySlug = strtolower((string) $key);
            $pathSlug = strtolower((string) pathinfo((string) $path, PATHINFO_FILENAME));
            $pathBaseSlug = Str::slug($pathSlug);

            if (
                $keySlug === $requested
                || $pathSlug === $requested
                || ($requestedSlug !== '' && (
                    Str::slug($keySlug) === $requestedSlug
                    || $pathBaseSlug === $requestedSlug
                ))
            ) {
                return (string) $path;
            }
        }

        return null;
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
        if ($this->useCidForInlineImages) {
            $src = 'cid:'.self::cidForPath($storagePath);
        } else {
            $normalized = app(EmailAttachmentResolver::class)->normalizeRelativePath($storagePath);
            $src = e(IntegrationSettings::publicStorageUrl($normalized));
        }

        return <<<HTML
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 16px 0;">
<tr>
<td align="center">
<img src="{$src}" alt="" width="560" style="display: block; max-width: 100%; height: auto; border-radius: 8px;">
</td>
</tr>
</table>
HTML;
    }
}
