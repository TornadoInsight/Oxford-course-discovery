<?php

declare(strict_types=1);

namespace CourseDiscovery\Tests\Unit\Domain;

use CourseDiscovery\Domain\FilterCriteria;
use PHPUnit\Framework\TestCase;

final class FilterCriteriaTest extends TestCase
{
    public function test_from_array_parses_filters_search_and_paging(): void
    {
        $criteria = FilterCriteria::fromArray([
            'search' => ' business ',
            'filters' => ['providers' => ['3', '5'], 'categories' => []],
            'page' => '2',
            'per_page' => '24',
        ]);

        self::assertSame('business', $criteria->search());
        self::assertSame(['3', '5'], $criteria->valuesFor('providers'));
        self::assertFalse($criteria->has('categories'), 'Empty filter arrays should not register as set.');
        self::assertSame(2, $criteria->page());
        self::assertSame(24, $criteria->perPage());
    }

    public function test_blank_search_is_normalised_to_null(): void
    {
        self::assertNull(FilterCriteria::fromArray(['search' => '  '])->search());
    }

    public function test_per_page_is_clamped_between_one_and_one_hundred(): void
    {
        self::assertSame(1, FilterCriteria::fromArray(['per_page' => '0'])->perPage());
        self::assertSame(100, FilterCriteria::fromArray(['per_page' => '9999'])->perPage());
    }

    public function test_page_cannot_go_below_one(): void
    {
        self::assertSame(1, FilterCriteria::fromArray(['page' => '-5'])->page());
    }

    public function test_with_values_is_immutable(): void
    {
        $original = FilterCriteria::fromArray(['filters' => ['providers' => ['1']]]);
        $modified = $original->withValues('providers', ['2', '3']);

        self::assertSame(['1'], $original->valuesFor('providers'));
        self::assertSame(['2', '3'], $modified->valuesFor('providers'));
    }

    public function test_with_values_empty_array_unsets_the_key(): void
    {
        $original = FilterCriteria::fromArray(['filters' => ['providers' => ['1']]]);

        self::assertFalse($original->withValues('providers', [])->has('providers'));
    }

    public function test_order_defaults_to_ascending_and_normalises_input(): void
    {
        self::assertSame('ASC', FilterCriteria::fromArray([])->order());
        self::assertSame('DESC', FilterCriteria::fromArray(['order' => 'desc'])->order());
        self::assertSame('ASC', FilterCriteria::fromArray(['order' => 'nonsense'])->order());
    }
}
