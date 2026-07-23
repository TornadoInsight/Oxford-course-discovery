<?php

declare(strict_types=1);

namespace CourseDiscovery\Domain;

/**
 * Context passed to CourseFilter::options() so option lists can be
 * contextual — e.g. a future filter could only show providers that have
 * courses matching the *other* currently-selected filters.
 */
final class FilterContext
{
    public function __construct(
        public readonly FilterCriteria $criteria,
    ) {
    }
}
