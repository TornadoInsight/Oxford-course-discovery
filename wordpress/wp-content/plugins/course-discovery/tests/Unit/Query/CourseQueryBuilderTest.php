<?php

declare(strict_types=1);

namespace CourseDiscovery\Tests\Unit\Query;

use CourseDiscovery\Query\CourseQueryBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Verifies the AND-across-filters / OR-within-a-filter grouping rule from
 * the brief's worked example, purely at the WP_Query-args level — no
 * WordPress bootstrap required, since toQueryArgs() never touches the DB.
 */
final class CourseQueryBuilderTest extends TestCase
{
    public function test_single_filter_values_are_or_ed_via_tax_query(): void
    {
        $builder = new CourseQueryBuilder();
        $builder->addTaxTerms('location', ['7', '8']);

        $args = $builder->toQueryArgs();

        self::assertSame('AND', $args['tax_query']['relation']);
        self::assertSame('IN', $args['tax_query'][0]['operator']);
        self::assertSame([7, 8], $args['tax_query'][0]['terms']);
    }

    public function test_two_different_filters_are_and_ed_together(): void
    {
        // Mirrors the brief's example: (provider OR) AND (location OR) AND (category).
        $builder = new CourseQueryBuilder();
        $builder->addMetaIn('providers', ['3', '5']);
        $builder->addTaxTerms('location', ['7', '8']);
        $builder->addTaxTerms('course_category', ['2']);

        $args = $builder->toQueryArgs();

        self::assertSame('AND', $args['meta_query']['relation']);
        self::assertSame('AND', $args['tax_query']['relation']);
        // location clause + category clause, AND'd (plus the 'relation' key itself).
        self::assertCount(2, array_filter($args['tax_query'], 'is_array'));
    }

    public function test_meta_in_or_s_multiple_values_for_one_key(): void
    {
        $builder = new CourseQueryBuilder();
        $builder->addMetaIn('providers', ['3', '5']);

        $args = $builder->toQueryArgs();

        self::assertSame('OR', $args['meta_query'][0]['relation']);
        self::assertCount(2, array_filter($args['meta_query'][0], 'is_array'));
    }

    public function test_empty_value_lists_are_ignored(): void
    {
        $builder = new CourseQueryBuilder();
        $builder->addTaxTerms('location', []);
        $builder->addMetaIn('providers', []);

        $args = $builder->toQueryArgs();

        self::assertArrayNotHasKey('tax_query', $args);
        self::assertArrayNotHasKey('meta_query', $args);
    }

    public function test_restrict_to_post_ids_intersects_across_calls(): void
    {
        $builder = new CourseQueryBuilder();
        $builder->restrictToPostIds([1, 2, 3]);
        $builder->restrictToPostIds([2, 3, 4]);

        self::assertSame([2, 3], array_values($builder->toQueryArgs()['post__in']));
    }

    public function test_restrict_to_post_ids_with_empty_result_forces_no_matches(): void
    {
        $builder = new CourseQueryBuilder();
        $builder->restrictToPostIds([]);

        self::assertSame([0], $builder->toQueryArgs()['post__in']);
    }

    public function test_search_sets_the_s_arg(): void
    {
        $builder = new CourseQueryBuilder();
        $builder->search('business english');

        self::assertSame('business english', $builder->toQueryArgs()['s']);
    }

    public function test_pagination_and_order_are_applied(): void
    {
        $builder = new CourseQueryBuilder();
        $builder->setPage(3);
        $builder->setPerPage(24);
        $builder->orderBy('title', 'desc');

        $args = $builder->toQueryArgs();

        self::assertSame(3, $args['paged']);
        self::assertSame(24, $args['posts_per_page']);
        self::assertSame('title', $args['orderby']);
        self::assertSame('DESC', $args['order']);
    }
}
