<?php

namespace App\Services;

readonly class NormalizedPurchaseContext
{
    public function __construct(
        public ?string $hotmartEvent = null,
        public ?string $productTitle = null,
        public ?string $phone = null,
        public ?string $transaction = null,
        public ?string $currency = null,
        public ?float $amount = null,
    ) {}
}
