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

    public function bodyToHtml(string $bodyWithMarkers): string
    {
        $pattern = '/\[\[EMAIL_BTN:(access|checkout)\|([^\]]+)\]\]/';

        $segments = preg_split($pattern, $bodyWithMarkers, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($segments === false) {
            return nl2br(e($bodyWithMarkers), false);
        }

        $html = '';

        foreach ($segments as $index => $segment) {
            if ($index % 3 === 0) {
                $html .= nl2br(e($segment), false);

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

    public function renderEmail(string $bodyHtml): string
    {
        return View::make('mail.hotmart-transactional', [
            'bodyHtml' => $bodyHtml,
            'logoUrl' => IntegrationSettings::emailLogoUrl(),
            'brandName' => IntegrationSettings::mailFromName(),
            'siteUrl' => rtrim((string) config('app.url'), '/'),
        ])->render();
    }

    public function renderFromMarkedBody(string $bodyWithMarkers): string
    {
        return $this->renderEmail($this->bodyToHtml($bodyWithMarkers));
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
}
