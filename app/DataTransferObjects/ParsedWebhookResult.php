<?php

namespace App\DataTransferObjects;

readonly class ParsedWebhookResult
{
    private function __construct(
        public bool $ignored,
        public ?NormalizedPurchaseData $data,
        public ?string $reason,
    ) {}

    public static function approved(NormalizedPurchaseData $data): self
    {
        return new self(false, $data, null);
    }

    public static function ignored(string $reason): self
    {
        return new self(true, null, $reason);
    }
}
