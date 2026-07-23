<?php

declare(strict_types=1);

namespace CourseDiscovery\Tests\Integration\Filters;

use CourseDiscovery\Domain\FilterCriteria;
use CourseDiscovery\Domain\StartDate;
use CourseDiscovery\Filters\Contract\CourseFilter;
use CourseDiscovery\Filters\StartDateFilter;
use CourseDiscovery\Migrations\Migration_1_0_0_CreateStartDatesTable;
use CourseDiscovery\Migrations\StartDatesTable;
use CourseDiscovery\PostTypes\CoursePostType;
use CourseDiscovery\Query\CourseQueryBuilder;
use CourseDiscovery\Tests\Support\FilterContractTestCase;

final class StartDateFilterTest extends FilterContractTestCase
{
    public static function set_up_before_class(): void
    {
        parent::set_up_before_class();
        (new Migration_1_0_0_CreateStartDatesTable())->up();
    }

    protected function makeFilter(): CourseFilter
    {
        return new StartDateFilter();
    }

    public function test_options_are_chronologically_sorted_regardless_of_insertion_order(): void
    {
        $course = self::factory()->post->create(['post_type' => CoursePostType::SLUG]);

        StartDatesTable::replaceForCourse($course, [
            StartDate::of(2027, 1),
            StartDate::of(2026, 9),
            StartDate::of(2026, 12),
        ]);

        $options = (new StartDateFilter())->options(
            new \CourseDiscovery\Domain\FilterContext(FilterCriteria::fromArray([]))
        );
        $keys = array_map(static fn ($option) => $option->value, $options);

        self::assertSame(['09-2026', '12-2026', '01-2027'], $keys);
    }

    public function test_apply_restricts_to_courses_with_a_matching_start_date(): void
    {
        $matching = self::factory()->post->create(['post_type' => CoursePostType::SLUG]);
        $nonMatching = self::factory()->post->create(['post_type' => CoursePostType::SLUG]);

        StartDatesTable::replaceForCourse($matching, [StartDate::of(2026, 9)]);
        StartDatesTable::replaceForCourse($nonMatching, [StartDate::of(2026, 12)]);

        $builder = new CourseQueryBuilder();
        $criteria = FilterCriteria::fromArray(['filters' => ['start_dates' => ['09-2026']]]);

        (new StartDateFilter())->apply($builder, $criteria);

        self::assertSame([$matching], $builder->toQueryArgs()['post__in']);
    }
}
