<?php

declare(strict_types=1);

namespace CourseDiscovery\Support;

/**
 * Central registry of every WordPress action/filter hook this plugin defines.
 *
 * Third-party code should depend on these constants rather than hardcoded
 * strings so renames are refactor-safe.
 */
final class Hooks
{
    /** Fired once, after the FilterRegistry exists, so third parties can register filters. */
    public const REGISTER_FILTERS = 'course_discovery/filters/register';

    /** Filters the option list for a single filter key: apply_filters(sprintf(self::FILTER_OPTIONS, $key), ...). */
    public const FILTER_OPTIONS = 'course_discovery/filters/%s/options';

    /** Filters the FilterCriteria built from an incoming request, before any filter is applied. */
    public const TRANSFORM_CRITERIA = 'course_discovery/criteria/transform';

    /** Filters the final WP_Query args array, immediately before the query runs. */
    public const QUERY_ARGS = 'course_discovery/query/args';

    /** Filters the `orderby`/`order` portion of the query args. */
    public const QUERY_ORDER = 'course_discovery/query/order';

    /** Filters the array shape returned by the REST courses endpoint for a single course. */
    public const TRANSFORM_COURSE = 'course_discovery/rest/course';

    private function __construct()
    {
    }
}
