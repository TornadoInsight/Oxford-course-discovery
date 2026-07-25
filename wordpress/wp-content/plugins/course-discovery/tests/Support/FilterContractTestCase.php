<?php

declare(strict_types=1);

namespace CourseDiscovery\Tests\Support;

use CourseDiscovery\Domain\FilterContext;
use CourseDiscovery\Domain\FilterCriteria;
use CourseDiscovery\Domain\FilterOption;
use CourseDiscovery\Filters\Contract\CourseFilter;
use CourseDiscovery\Query\CourseQueryBuilder;
use Yoast\WPTestUtils\WPIntegration\TestCase;

/**
 * The contract every CourseFilter must satisfy, independent of what it
 * actually filters by. A new filter's test only needs to implement
 * makeFilter() (and add its own filter-specific behavioural test) to get
 * this baseline coverage for free — see tests/Integration/Filters for
 * examples.
 *
 * This is the answer to "how can new filters be tested consistently":
 * extend this class instead of writing CourseFilter tests from scratch.
 */
abstract class FilterContractTestCase extends TestCase
{
    abstract protected function makeFilter(): CourseFilter;

    public function test_key_is_a_non_empty_machine_slug(): void
    {
        self::assertMatchesRegularExpression('/^[a-z_]+$/', $this->makeFilter()->key());
    }

    public function test_label_is_non_empty(): void
    {
        self::assertNotSame('', trim($this->makeFilter()->label()));
    }

    public function test_options_returns_only_filter_option_instances(): void
    {
        $context = new FilterContext(FilterCriteria::fromArray([]));

        self::assertContainsOnlyInstancesOf(FilterOption::class, $this->makeFilter()->options($context));
    }

    public function test_apply_with_no_selected_values_does_not_change_the_query(): void
    {
        $filter = $this->makeFilter();
        $builder = new CourseQueryBuilder();
        $before = $builder->toQueryArgs();

        $filter->apply($builder, FilterCriteria::fromArray([]));

        self::assertSame($before, $builder->toQueryArgs(), 'A filter with nothing selected must be a no-op.');
    }
}
