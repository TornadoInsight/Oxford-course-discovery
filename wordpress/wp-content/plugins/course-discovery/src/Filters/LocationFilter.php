<?php

declare(strict_types=1);

namespace CourseDiscovery\Filters;

use CourseDiscovery\Domain\FilterContext;
use CourseDiscovery\Domain\FilterCriteria;
use CourseDiscovery\Domain\FilterOption;
use CourseDiscovery\Filters\Contract\CourseFilter;
use CourseDiscovery\PostTypes\LocationTaxonomy;
use CourseDiscovery\Query\CourseQueryBuilder;
use CourseDiscovery\Support\Hooks;
use WP_Term;

/**
 * Filters by the `location` taxonomy terms mirrored onto Course from its
 * Providers — see Sync\LocationSync for how that mirror stays in sync.
 */
final class LocationFilter implements CourseFilter
{
    public function key(): string
    {
        return 'locations';
    }

    public function label(): string
    {
        return __('Locations', 'course-discovery');
    }

    public function options(FilterContext $context): array
    {
        $terms = get_terms([
            'taxonomy' => LocationTaxonomy::SLUG,
            'hide_empty' => true,
        ]);
        $terms = is_array($terms) ? $terms : [];

        $options = array_map(
            static fn (WP_Term $term) => new FilterOption((string) $term->term_id, $term->name),
            $terms
        );
        usort($options, static fn (FilterOption $a, FilterOption $b) => strcasecmp($a->label, $b->label));

        /** @var list<FilterOption> $options */
        $options = apply_filters(sprintf(Hooks::FILTER_OPTIONS, $this->key()), $options, $context);

        return $options;
    }

    public function apply(CourseQueryBuilder $builder, FilterCriteria $criteria): void
    {
        $builder->addTaxTerms(LocationTaxonomy::SLUG, $criteria->valuesFor($this->key()));
    }
}
