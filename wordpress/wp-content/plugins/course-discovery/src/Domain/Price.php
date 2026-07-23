<?php

declare(strict_types=1);

namespace CourseDiscovery\Domain;

use InvalidArgumentException;

/**
 * A single numeric course price.
 *
 * Deliberately narrow today (per the brief's note that price "can be extended
 * to support range or multiple price points"): a future `PriceRange` or
 * `PriceSchedule` value object can sit alongside this one without touching
 * anything that only knows how to format a `Price`, as long as it exposes the
 * same `format()`/`amount()` contract call sites rely on.
 */
final class Price
{
    private function __construct(
        private readonly float $amount,
        private readonly string $currency,
    ) {
        if ($amount < 0) {
            throw new InvalidArgumentException('Price amount cannot be negative.');
        }
    }

    public static function fromFloat(float $amount, string $currency = 'GBP'): self
    {
        return new self($amount, $currency);
    }

    public static function fromNullable(mixed $amount, string $currency = 'GBP'): ?self
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        return self::fromFloat((float) $amount, $currency);
    }

    public function amount(): float
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function format(): string
    {
        $symbol = match ($this->currency) {
            'GBP' => '£',
            'USD' => '$',
            'EUR' => '€',
            default => $this->currency . ' ',
        };

        return $symbol . number_format($this->amount, 2);
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }
}
