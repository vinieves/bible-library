<?php

namespace App\Services\Webhooks;

class PhoneNumber
{
    public static function normalize(mixed $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        $digits = self::digitsOnly((string) $value);

        if (blank($digits)) {
            return null;
        }

        return strlen($digits) >= 10 ? $digits : null;
    }

    /**
     * @param  array<string, mixed>  $buyer
     */
    public static function fromHotmartBuyer(array $buyer): ?string
    {
        $checkoutPhone = trim((string) ($buyer['checkout_phone'] ?? ''));
        $areaCode = trim((string) ($buyer['checkout_phone_code'] ?? ''));
        $countryIso = strtoupper(trim((string) data_get($buyer, 'address.country_iso', '')));

        if (filled($checkoutPhone)) {
            $normalized = self::normalizeHotmartCheckoutPhone($checkoutPhone, $areaCode, $countryIso);

            if ($normalized) {
                return $normalized;
            }
        }

        foreach (['phone', 'phone_number'] as $field) {
            $normalized = self::normalize($buyer[$field] ?? null);

            if ($normalized) {
                return $normalized;
            }
        }

        return null;
    }

    public static function normalizeHotmartCheckoutPhone(
        string $checkoutPhone,
        ?string $areaCode = null,
        ?string $countryIso = null,
    ): ?string {
        $digits = self::digitsOnly($checkoutPhone);

        if (blank($digits)) {
            return null;
        }

        $areaCode = self::digitsOnly((string) $areaCode);
        $countryIso = strtoupper(trim((string) $countryIso));

        // Hotmart já envia DDI + número em checkout_phone quando o número está completo
        // (vendas internacionais e muitos checkouts BR). Não remontar nesses casos.
        if (strlen($digits) >= 11) {
            return $digits;
        }

        // BR legado: checkout_phone local (9 dígitos) + checkout_phone_code (DDD).
        if (($countryIso === 'BR' || filled($areaCode)) && filled($areaCode)) {
            return self::normalizeBrazilianPhone($digits, $areaCode);
        }

        return null;
    }

    private static function normalizeBrazilianPhone(string $digits, string $areaCode = ''): ?string
    {
        $local = ltrim($digits, '0');

        if (str_starts_with($local, '55') && strlen($local) >= 11) {
            return $local;
        }

        if (filled($areaCode) && ! str_starts_with($local, $areaCode)) {
            $local = $areaCode.$local;
        }

        if (! str_starts_with($local, '55')) {
            $local = '55'.$local;
        }

        return strlen($local) >= 12 ? $local : null;
    }

    private static function digitsOnly(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }
}
