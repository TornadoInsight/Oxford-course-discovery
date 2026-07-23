<?php

declare(strict_types=1);

namespace CourseDiscovery\Tests\Unit\Domain;

use CourseDiscovery\Domain\StartDate;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * StartDate is a high-risk value object: it's the join point between the
 * brief's required {month}-{year} input format, the ACF day-level date
 * picker, and the DATE column in wp_course_discovery_start_dates. Any drift
 * between those three representations would silently corrupt filtering.
 */
final class StartDateTest extends TestCase
{
    public function test_parses_brief_format_month_dash_year(): void
    {
        $date = StartDate::fromString('07-2026');

        self::assertSame(7, $date->month());
        self::assertSame(2026, $date->year());
    }

    public function test_key_round_trips_through_brief_format(): void
    {
        self::assertSame('07-2026', StartDate::of(2026, 7)->key());
    }

    public function test_label_is_human_readable(): void
    {
        self::assertSame('July 2026', StartDate::of(2026, 7)->label());
    }

    public function test_parses_iso_date_and_discards_day(): void
    {
        $date = StartDate::fromString('2026-07-15');

        self::assertSame('07-2026', $date->key());
    }

    public function test_to_db_date_normalises_to_first_of_month(): void
    {
        self::assertSame('2026-07-01', StartDate::of(2026, 7)->toDbDate());
    }

    public function test_sort_key_orders_chronologically_across_years(): void
    {
        $dec2026 = StartDate::of(2026, 12);
        $jan2027 = StartDate::of(2027, 1);

        self::assertTrue($jan2027->isAfter($dec2026));
        self::assertLessThan($jan2027->sortKey(), $dec2026->sortKey());
    }

    public function test_rejects_invalid_month(): void
    {
        $this->expectException(InvalidArgumentException::class);

        StartDate::of(2026, 13);
    }

    public function test_equals_compares_month_and_year_only(): void
    {
        self::assertTrue(StartDate::of(2026, 7)->equals(StartDate::fromString('2026-07-28')));
    }
}
