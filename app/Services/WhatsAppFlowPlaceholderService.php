<?php

namespace App\Services;

class WhatsAppFlowPlaceholderService
{
    public function render(?string $text, ?string $contactName): string
    {
        if (blank($text)) {
            return '';
        }

        return str_replace(
            ['{nome}'],
            [$this->resolveFirstName($contactName)],
            $text,
        );
    }

    public function resolveFirstName(?string $contactName): string
    {
        $name = trim((string) $contactName);

        if ($name === '') {
            return '';
        }

        $first = explode(' ', preg_replace('/\s+/', ' ', $name) ?? $name)[0] ?? $name;

        return trim($first);
    }
}
