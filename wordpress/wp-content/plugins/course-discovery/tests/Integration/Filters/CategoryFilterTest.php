<?php

declare(strict_types=1);

namespace CourseDiscovery\Tests\Integration\Filters;

use CourseDiscovery\Domain\FilterCriteria;
use CourseDiscovery\Filters\CategoryFilter;
use CourseDiscovery\Filters\Contract\CourseFilter;
use CourseDiscovery\PostTypes\CourseCategoryTaxonomy;
use CourseDiscovery\PostTypes\CoursePostType;
use CourseDiscovery\Query\CourseQueryBuilder;
use CourseDiscovery\Tests\Support\FilterContractTestCase;

final class CategoryFilterTest extends FilterContractTestCase
{
    protected function makeFilter(): CourseFilter
    {
        return new CategoryFilter();
    }

    public function test_options_lists_only_terms_with_at_least_one_course(): void
    {
        register_post_type(CoursePostType::SLUG, ['public' => true]);
        register_taxonomy(CourseCategoryTaxonomy::SLUG, [CoursePostType::SLUG]);

        $emptyTerm = self::factory()->term->create(['taxonomy' => CourseCategoryTaxonomy::SLUG, 'name' => 'Empty Category']);
        $usedTerm = self::factory()->term->create(['taxonomy' => CourseCategoryTaxonomy::SLUG, 'name' => 'Used Category']);
        $course = self::factory()->post->create(['post_type' => CoursePostType::SLUG]);
        wp_set_object_terms($course, [$usedTerm], CourseCategoryTaxonomy::SLUG);

        $labels = array_map(
            static fn ($option) => $option->label,
            (new CategoryFilter())->options(new \CourseDiscovery\Domain\FilterContext(FilterCriteria::fromArray([])))
        );

        self::assertContains('Used Category', $labels);
        self::assertNotContains('Empty Category', $labels);
    }

    public function test_apply_restricts_query_to_selected_category_terms(): void
    {
        register_taxonomy(CourseCategoryTaxonomy::SLUG, [CoursePostType::SLUG]);
        $termId = self::factory()->term->create(['taxonomy' => CourseCategoryTaxonomy::SLUG]);

        $builder = new CourseQueryBuilder();
        $criteria = FilterCriteria::fromArray(['filters' => ['categories' => [(string) $termId]]]);

        (new CategoryFilter())->apply($builder, $criteria);

        $args = $builder->toQueryArgs();
        self::assertSame([$termId], $args['tax_query'][0]['terms']);
    }
}
