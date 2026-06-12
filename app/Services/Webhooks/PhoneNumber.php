<?php

namespace App\Services\Webhooks;

class PhoneNumber
{
    public static function normalize(mixed $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $value);

        if (blank($digits)) {
            return null;
        }

        if (strlen($digits) <= 11 && ! str_starts_with($digits, '55')) {
            $digits = '55'.$digits;
        }

        return $digits;
    }
}
