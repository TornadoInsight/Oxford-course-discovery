<?php

declare(strict_types=1);

namespace CourseDiscovery\Domain;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * A course start date, always normalised to a {month}-{year} granularity —
 * the day component is not meaningful for this domain and is discarded.
 *
 * This is the value object both the ACF repeater (day-level date picker) and
 * the `wp_course_discovery_start_dates` lookup table (a real DATE column)
 * collapse down to, so filtering/sorting/display all agree on one
 * representation.
 */
final class StartDate
{
    private function __construct(
        private readonly int $year,
        private readonly int $month,
    ) {
        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException("Invalid month: {$month}");
        }
    }

    public static function of(int $year, int $month): self
    {
        return new self($year, $month);
    }

    /** Accepts "07-2026" ({month}-{year}, as specified in the brief) or any format DateTimeImmutable can parse. */
    public static function fromString(string $value): self
    {
        if (preg_match('/^(\d{1,2})-(\d{4})$/', trim($value), $matches) === 1) {
            return new self((int) $matches[2], (int) $matches[1]);
        }

        $date = new DateTimeImmutable($value);

        return new self((int) $date->format('Y'), (int) $date->format('n'));
    }

    public static function fromDateTime(DateTimeImmutable $date): self
    {
        return new self((int) $date->format('Y'), (int) $date->format('n'));
    }

    public function year(): int
    {
        return $this->year;
    }

    public function month(): int
    {
        return $this->month;
    }

    /** {month}-{year} as specified by the brief, e.g. "07-2026". */
    public function key(): string
    {
        return sprintf('%02d-%04d', $this->month, $this->year);
    }

    /** Human label, e.g. "July 2026". */
    public function label(): string
    {
        return $this->toDateTime()->format('F Y');
    }

    /** For persistence into the DATE column backing the start-dates lookup table. */
    public function toDbDate(): string
    {
        return $this->toDateTime()->format('Y-m-d');
    }

    public function toDateTime(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromFormat('Y-n-j', "{$this->year}-{$this->month}-1")
            ?: throw new InvalidArgumentException('Unable to build date.');
    }

    /** Monotonically increasing integer suitable for chronological sorting. */
    public function sortKey(): int
    {
        return ($this->year * 100) + $this->month;
    }

    public function equals(self $other): bool
    {
        return $this->sortKey() === $other->sortKey();
    }

    public function isAfter(self $other): bool
    {
        return $this->sortKey() > $other->sortKey();
    }
}
