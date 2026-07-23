<?php

declare(strict_types=1);

namespace CourseDiscovery\Filters;

use CourseDiscovery\Domain\FilterContext;
use CourseDiscovery\Domain\FilterCriteria;
use CourseDiscovery\Domain\FilterOption;
use CourseDiscovery\Fields\CourseFields;
use CourseDiscovery\Filters\Contract\CourseFilter;
use CourseDiscovery\PostTypes\ProviderPostType;
use CourseDiscovery\Query\CourseQueryBuilder;
use CourseDiscovery\Support\Hooks;
use WP_Post;

final class ProviderFilter implements CourseFilter
{
    public function key(): string
    {
        return 'providers';
    }

    public function label(): string
    {
        return __('Providers', 'course-discovery');
    }

    public function options(FilterContext $context): array
    {
        $posts = get_posts([
            'post_type' => ProviderPostType::SLUG,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        $options = array_map(
            static fn (WP_Post $post) => new FilterOption((string) $post->ID, $post->post_title),
            $posts
        );

        /** @var list<FilterOption> $options */
        $options = apply_filters(sprintf(Hooks::FILTER_OPTIONS, $this->key()), $options, $context);

        return $options;
    }

    public function apply(CourseQueryBuilder $builder, FilterCriteria $criteria): void
    {
        $builder->addMetaIn(CourseFields::FIELD_PROVIDERS, $criteria->valuesFor($this->key()));
    }
}
