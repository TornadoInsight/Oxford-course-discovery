<?php

declare(strict_types=1);

namespace CourseDiscovery\Migrations;

use CourseDiscovery\Domain\StartDate;

/**
 * Small gateway around wp_course_discovery_start_dates — the one place in
 * the codebase that knows this table's schema.
 *
 * Why this table exists at all: getting a *distinct, chronologically sorted*
 * list of {month}-{year} values across every course, or finding every course
 * matching a set of start dates, is exactly what postmeta (even via an ACF
 * repeater) is bad at — repeater rows are spread across N differently-keyed
 * meta rows with no usable index, so a DISTINCT+ORDER BY would mean loading
 * and de-duplicating every course's meta in PHP. A real indexed DATE column
 * makes both queries a single, fast, indexed SQL statement.
 */
final class StartDatesTable
{
    public static function name(): string
    {
        global $wpdb;

        return $wpdb->prefix . 'course_discovery_start_dates';
    }

    /** @param list<StartDate> $startDates */
    public static function replaceForCourse(int $courseId, array $startDates): void
    {
        global $wpdb;
        $table = self::name();

        $wpdb->delete($table, ['course_id' => $courseId], ['%d']);

        $seen = [];
        foreach ($startDates as $startDate) {
            $dbDate = $startDate->toDbDate();
            if (isset($seen[$dbDate])) {
                continue; // de-dupe repeater rows that resolve to the same month
            }
            $seen[$dbDate] = true;

            $wpdb->insert($table, [
                'course_id' => $courseId,
                'start_date' => $dbDate,
                'created_at' => current_time('mysql'),
            ], ['%d', '%s', '%s']);
        }
    }

    /** @return list<StartDate> A single course's start dates, chronological. */
    public static function forCourse(int $courseId): array
    {
        global $wpdb;
        $table = self::name();

        $rows = $wpdb->get_col($wpdb->prepare(
            "SELECT start_date FROM {$table} WHERE course_id = %d ORDER BY start_date ASC",
            $courseId
        ));

        return array_map(static fn (string $date) => StartDate::fromString($date), $rows);
    }

    /** @return list<StartDate> Every distinct start date across all courses, chronological. */
    public static function distinctSorted(): array
    {
        global $wpdb;
        $table = self::name();

        $rows = $wpdb->get_col("SELECT DISTINCT start_date FROM {$table} ORDER BY start_date ASC");

        return array_map(static fn (string $date) => StartDate::fromString($date), $rows);
    }

    /**
     * @param list<string> $dbDates Y-m-d values (first-of-month).
     * @return list<int> Course IDs that have at least one of the given start dates.
     */
    public static function courseIdsForDates(array $dbDates): array
    {
        if ($dbDates === []) {
            return [];
        }

        global $wpdb;
        $table = self::name();
        $placeholders = implode(',', array_fill(0, count($dbDates), '%s'));

        $sql = $wpdb->prepare(
            "SELECT DISTINCT course_id FROM {$table} WHERE start_date IN ({$placeholders})",
            $dbDates
        );

        return array_map('intval', $wpdb->get_col($sql));
    }
}
