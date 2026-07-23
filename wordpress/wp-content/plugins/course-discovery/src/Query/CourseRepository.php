<?php

declare(strict_types=1);

namespace CourseDiscovery\Query;

use CourseDiscovery\Domain\Course;
use CourseDiscovery\Domain\FilterCriteria;
use CourseDiscovery\Domain\Price;
use CourseDiscovery\Fields\CourseFields;
use CourseDiscovery\Filters\FilterRegistry;
use CourseDiscovery\Migrations\StartDatesTable;
use CourseDiscovery\PostTypes\CourseCategoryTaxonomy;
use CourseDiscovery\PostTypes\CoursePostType;
use CourseDiscovery\PostTypes\LocationTaxonomy;
use CourseDiscovery\Support\Hooks;
use WP_Post;
use WP_Query;
use WP_Term;

/**
 * The only place that turns a WP_Post into a Course domain entity, and the
 * only place that runs a Course search. REST controllers and any future
 * template code depend on this, never on WP_Query/WP_Post directly.
 */
final class CourseRepository
{
    public function __construct(private readonly FilterRegistry $registry)
    {
    }

    public function search(FilterCriteria $criteria): SearchResult
    {
        $builder = new CourseQueryBuilder();

        foreach ($this->registry->all() as $filter) {
            $filter->apply($builder, $criteria);
        }

        $builder->setPage($criteria->page());
        $builder->setPerPage($criteria->perPage());
        [$orderBy, $order] = apply_filters(Hooks::QUERY_ORDER, ['date', $criteria->order()], $criteria);
        $builder->orderBy((string) $orderBy, (string) $order);

        $args = apply_filters(Hooks::QUERY_ARGS, $builder->toQueryArgs(), $criteria);
        $wpQuery = new WP_Query($args);

        $courses = array_map($this->mapPost(...), $wpQuery->posts);

        return new SearchResult($courses, (int) $wpQuery->found_posts, $criteria->page(), $criteria->perPage());
    }

    public function find(int $courseId): ?Course
    {
        $post = get_post($courseId);
        if (!$post instanceof WP_Post || $post->post_type !== CoursePostType::SLUG) {
            return null;
        }

        return $this->mapPost($post);
    }

    private function mapPost(WP_Post $post): Course
    {
        $providerIds = array_map('intval', (array) get_field(CourseFields::FIELD_PROVIDERS, $post->ID));
        $instructorIds = array_map('intval', (array) get_field(CourseFields::FIELD_INSTRUCTORS, $post->ID));

        return new Course(
            id: $post->ID,
            name: get_the_title($post),
            slug: $post->post_name,
            permalink: (string) get_permalink($post),
            shortDescription: get_the_excerpt($post),
            longDescription: (string) apply_filters('the_content', $post->post_content),
            price: Price::fromNullable(get_field(CourseFields::FIELD_PRICE, $post->ID)),
            instructors: array_map($this->toPostRef(...), $instructorIds),
            providers: array_map($this->toPostRef(...), $providerIds),
            locations: $this->toRefs(get_the_terms($post->ID, LocationTaxonomy::SLUG)),
            categories: $this->toRefs(get_the_terms($post->ID, CourseCategoryTaxonomy::SLUG)),
            startDates: StartDatesTable::forCourse($post->ID),
        );
    }

    private function toPostRef(int $postId): \CourseDiscovery\Domain\PostRef
    {
        $post = get_post($postId);

        return new \CourseDiscovery\Domain\PostRef(
            $postId,
            $post ? get_the_title($post) : '',
            $post ? $post->post_name : ''
        );
    }

    /**
     * @param list<WP_Term>|false|\WP_Error $terms
     * @return list<\CourseDiscovery\Domain\PostRef>
     */
    private function toRefs(mixed $terms): array
    {
        if (!is_array($terms)) {
            return [];
        }

        return array_values(array_map(
            static fn (WP_Term $term) => new \CourseDiscovery\Domain\PostRef($term->term_id, $term->name, $term->slug),
            $terms
        ));
    }
}
