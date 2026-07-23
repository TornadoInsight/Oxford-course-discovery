<?php

declare(strict_types=1);

namespace CourseDiscovery\Filters\Contract;

use CourseDiscovery\Domain\FilterContext;
use CourseDiscovery\Domain\FilterCriteria;
use CourseDiscovery\Domain\FilterOption;
use CourseDiscovery\Query\CourseQueryBuilder;

/**
 * A single, self-contained filter: what it's called, what options it
 * currently offers, and how it narrows a query. Implementing this and
 * registering the instance via the course_discovery/filters/register hook
 * is the entire contract for adding a new filter — nothing else in the
 * plugin needs to change.
 */
interface CourseFilter
{
    /** Stable machine key, e.g. "providers". Matches the request param and FilterCriteria key. */
    public function key(): string;

    public function label(): string;

    /** @return list<FilterOption> */
    public function options(FilterContext $context): array;

    /**
     * Narrow the query for this filter's selected value(s). Multiple values
     * selected for the same filter must be combined with OR; this filter's
     * contribution is combined with every other filter's via AND (enforced
     * by CourseQueryBuilder, not by individual filters).
     */
    public function apply(CourseQueryBuilder $builder, FilterCriteria $criteria): void;
}
