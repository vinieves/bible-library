<?php

namespace App\Support;

class MessageTriggerNormalizer
{
    public static function normalize(mixed $text): ?string
    {
        if (! filled($text)) {
            return null;
        }

        $normalized = html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $normalized = preg_replace('/\s+/u', ' ', trim($normalized)) ?? trim($normalized);

        if ($normalized === '') {
            return null;
        }

        return mb_strtolower($normalized);
    }
}
