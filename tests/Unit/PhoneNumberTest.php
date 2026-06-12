<?php

namespace Tests\Unit;

use App\Services\Webhooks\PhoneNumber;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    public function test_it_keeps_international_colombian_number_from_hotmart(): void
    {
        $phone = PhoneNumber::fromHotmartBuyer([
            'checkout_phone' => '573165247626',
            'address' => ['country_iso' => 'CO'],
        ]);

        $this->assertSame('573165247626', $phone);
    }

    public function test_it_builds_brazilian_number_with_area_code(): void
    {
        $phone = PhoneNumber::fromHotmartBuyer([
            'checkout_phone' => '999999999',
            'checkout_phone_code' => '31',
            'address' => ['country_iso' => 'BR'],
        ]);

        $this->assertSame('5531999999999', $phone);
    }

    public function test_it_does_not_force_brazil_prefix_on_short_international_numbers(): void
    {
        $phone = PhoneNumber::fromHotmartBuyer([
            'checkout_phone' => '3165247626',
            'address' => ['country_iso' => 'CO'],
        ]);

        $this->assertNull($phone);
    }

    public function test_normalize_keeps_full_international_number(): void
    {
        $this->assertSame('573165247626', PhoneNumber::normalize('57 316 524 7626'));
    }
}
