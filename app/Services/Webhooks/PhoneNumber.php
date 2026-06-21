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

        if (strlen($digits) < 10) {
            return null;
        }

        return self::canonicalBrazilMobile($digits) ?? $digits;
    }

    /**
     * Variantes equivalentes para comparação (ex.: BR com/sem nono dígito).
     *
     * @return list<string>
     */
    public static function matchVariants(mixed $value): array
    {
        $digits = self::normalize($value);

        if (! $digits) {
            return [];
        }

        $variants = [$digits];

        $legacy = self::legacyBrazilMobileWithoutNinthDigit($digits);

        if ($legacy) {
            $variants[] = $legacy;
        }

        return array_values(array_unique($variants));
    }

    /**
     * BR móvel: 55 + DDD + 9 + 8 dígitos. WhatsApp/Evolution costuma omitir o 9 extra.
     */
    public static function canonicalBrazilMobile(string $digits): ?string
    {
        $digits = self::digitsOnly($digits);

        if (! str_starts_with($digits, '55') || strlen($digits) < 12) {
            return null;
        }

        $ddd = substr($digits, 2, 2);
        $local = substr($digits, 4);

        if (! preg_match('/^\d{2}$/', $ddd)) {
            return null;
        }

        if (strlen($local) === 8 && preg_match('/^[6-9]/', $local)) {
            $local = '9'.$local;
        }

        if (strlen($local) !== 9 || ! str_starts_with($local, '9')) {
            return null;
        }

        return '55'.$ddd.$local;
    }

    private static function legacyBrazilMobileWithoutNinthDigit(string $digits): ?string
    {
        $digits = self::digitsOnly($digits);

        if (! str_starts_with($digits, '55') || strlen($digits) !== 13) {
            return null;
        }

        $ddd = substr($digits, 2, 2);
        $local = substr($digits, 4);

        if (strlen($local) === 9 && str_starts_with($local, '9')) {
            return '55'.$ddd.substr($local, 1);
        }

        return null;
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

    public static function fromRemoteJid(?string $jid): ?string
    {
        if (blank($jid)) {
            return null;
        }

        $jid = strtolower(trim($jid));

        if (
            str_contains($jid, '@g.us')
            || str_contains($jid, '@broadcast')
            || str_contains($jid, 'status@')
            || str_ends_with($jid, '@newsletter')
            || str_ends_with($jid, '@lid')
        ) {
            return null;
        }

        $userPart = explode('@', $jid, 2)[0] ?? '';

        return self::normalize($userPart);
    }

    private static function digitsOnly(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }
}
