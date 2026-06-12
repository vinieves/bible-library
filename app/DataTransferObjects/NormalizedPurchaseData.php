<?php

namespace App\DataTransferObjects;

readonly class NormalizedPurchaseData
{
    /**
     * @param  list<string>  $productCodeCandidates
     */
    public function __construct(
        public string $email,
        public ?string $name,
        public ?string $phone,
        public string $productCode,
        public ?float $amount,
        public string $externalReference,
        public string $eventId,
        public array $rawPayload = [],
        public array $productCodeCandidates = [],
    ) {}

    /**
     * @return list<string>
     */
    public function productCodesForLookup(): array
    {
        $codes = $this->productCodeCandidates !== []
            ? $this->productCodeCandidates
            : [$this->productCode];

        return array_values(array_unique(array_filter(
            array_map(fn ($code) => trim((string) $code), $codes),
            fn (string $code) => filled($code),
        )));
    }
}
