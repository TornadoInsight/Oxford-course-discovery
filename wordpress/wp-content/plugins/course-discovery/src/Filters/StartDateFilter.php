<?php

declare(strict_types=1);

namespace CourseDiscovery\Filters;

use CourseDiscovery\Domain\FilterContext;
use CourseDiscovery\Domain\FilterCriteria;
use CourseDiscovery\Domain\FilterOption;
use CourseDiscovery\Domain\StartDate;
use CourseDiscovery\Filters\Contract\CourseFilter;
use CourseDiscovery\Migrations\StartDatesTable;
use CourseDiscovery\Query\CourseQueryBuilder;
use CourseDiscovery\Support\Hooks;

/**
 * Filters by {month}-{year} start date, backed by the dedicated
 * wp_course_discovery_start_dates table (see Migrations\StartDatesTable for
 * why postmeta alone can't serve a chronologically sorted, distinct option
 * list efficiently).
 */
final class StartDateFilter implements CourseFilter
{
    public function key(): string
    {
        return 'start_dates';
    }

    public function label(): string
    {
        return __('Start Dates', 'course-discovery');
    }

    /** Chronological order, as required by the brief — the table's index gives us this for free. */
    public function options(FilterContext $context): array
    {
        $options = array_map(
            static fn (StartDate $date) => new FilterOption($date->key(), $date->label(), $date->sortKey()),
            StartDatesTable::distinctSorted()
        );

        /** @var list<FilterOption> $options */
        $options = apply_filters(sprintf(Hooks::FILTER_OPTIONS, $this->key()), $options, $context);

        return $options;
    }

    public function apply(CourseQueryBuilder $builder, FilterCriteria $criteria): void
    {
        $keys = $criteria->valuesFor($this->key());
        if ($keys === []) {
            return;
        }

        $dbDates = array_map(
            static fn (string $key) => StartDate::fromString($key)->toDbDate(),
            $keys
        );

        $builder->restrictToPostIds(StartDatesTable::courseIdsForDates($dbDates));
    }
}
