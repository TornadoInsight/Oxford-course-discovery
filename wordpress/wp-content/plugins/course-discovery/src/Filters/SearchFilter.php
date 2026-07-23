<?php

declare(strict_types=1);

namespace CourseDiscovery\Filters;

use CourseDiscovery\Domain\FilterContext;
use CourseDiscovery\Domain\FilterCriteria;
use CourseDiscovery\Filters\Contract\CourseFilter;
use CourseDiscovery\Query\CourseQueryBuilder;

/**
 * Plain-text search against name/short description/long description —
 * post_title/post_excerpt/post_content are native WP fields, so this is
 * just core's own `s` search parameter (see README for its limitations at
 * scale).
 */
final class SearchFilter implements CourseFilter
{
    public function key(): string
    {
        return 'search';
    }

    public function label(): string
    {
        return __('Search', 'course-discovery');
    }

    /** No enumerable options for free text. */
    public function options(FilterContext $context): array
    {
        return [];
    }

    public function apply(CourseQueryBuilder $builder, FilterCriteria $criteria): void
    {
        $term = $criteria->search();
        if ($term !== null && $term !== '') {
            $builder->search($term);
        }
    }
}
