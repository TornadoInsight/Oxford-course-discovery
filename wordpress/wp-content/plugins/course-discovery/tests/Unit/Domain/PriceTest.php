<?php

declare(strict_types=1);

namespace CourseDiscovery\Tests\Unit\Domain;

use CourseDiscovery\Domain\Price;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PriceTest extends TestCase
{
    public function test_formats_with_currency_symbol(): void
    {
        $price = Price::fromFloat(1200, 'GBP');

        self::assertSame('£1,200.00', $price->format());
    }

    public function test_defaults_to_gbp(): void
    {
        self::assertSame('GBP', Price::fromFloat(10)->currency());
    }

    public function test_rejects_negative_amounts(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Price::fromFloat(-1);
    }

    public function test_from_nullable_returns_null_for_empty_input(): void
    {
        self::assertNull(Price::fromNullable(null));
        self::assertNull(Price::fromNullable(''));
    }

    public function test_from_nullable_parses_numeric_strings(): void
    {
        $price = Price::fromNullable('950');

        self::assertNotNull($price);
        self::assertSame(950.0, $price->amount());
    }

    public function test_equals_compares_amount_and_currency(): void
    {
        self::assertTrue(Price::fromFloat(10, 'GBP')->equals(Price::fromFloat(10, 'GBP')));
        self::assertFalse(Price::fromFloat(10, 'GBP')->equals(Price::fromFloat(10, 'USD')));
    }
}
