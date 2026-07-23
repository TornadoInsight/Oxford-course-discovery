<?php

declare(strict_types=1);

namespace CourseDiscovery\Filters;

use CourseDiscovery\Domain\FilterContext;
use CourseDiscovery\Domain\FilterCriteria;
use CourseDiscovery\Domain\FilterOption;
use CourseDiscovery\Filters\Contract\CourseFilter;
use CourseDiscovery\PostTypes\CourseCategoryTaxonomy;
use CourseDiscovery\Query\CourseQueryBuilder;
use CourseDiscovery\Support\Hooks;
use WP_Term;

final class CategoryFilter implements CourseFilter
{
    public function key(): string
    {
        return 'categories';
    }

    public function label(): string
    {
        return __('Categories', 'course-discovery');
    }

    public function options(FilterContext $context): array
    {
        $terms = get_terms([
            'taxonomy' => CourseCategoryTaxonomy::SLUG,
            'hide_empty' => true,
        ]);
        $terms = is_array($terms) ? $terms : [];

        $options = array_map(
            static fn (WP_Term $term) => new FilterOption((string) $term->term_id, $term->name),
            $terms
        );

        /** @var list<FilterOption> $options */
        $options = apply_filters(sprintf(Hooks::FILTER_OPTIONS, $this->key()), $options, $context);

        return $options;
    }

    public function apply(CourseQueryBuilder $builder, FilterCriteria $criteria): void
    {
        $builder->addTaxTerms(CourseCategoryTaxonomy::SLUG, $criteria->valuesFor($this->key()));
    }
}
