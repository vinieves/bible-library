<?php

namespace App\DataTransferObjects;

readonly class NormalizedPurchaseData
{
    public function __construct(
        public string $email,
        public ?string $name,
        public ?string $phone,
        public string $productCode,
        public ?float $amount,
        public string $externalReference,
        public string $eventId,
        public array $rawPayload = [],
    ) {}
}
