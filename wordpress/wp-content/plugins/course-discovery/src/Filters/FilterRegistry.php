<?php

declare(strict_types=1);

namespace CourseDiscovery\Filters;

use CourseDiscovery\Filters\Contract\CourseFilter;

/**
 * Holds every registered CourseFilter. Populated once, at plugin boot, by
 * firing the course_discovery/filters/register action — see Plugin::boot().
 * Third-party code hooks that same action to add its own filters:
 *
 *   add_action('course_discovery/filters/register', function (FilterRegistry $registry) {
 *       $registry->register(new MyCustomFilter());
 *   });
 */
final class FilterRegistry
{
    /** @var array<string, CourseFilter> */
    private array $filters = [];

    public function register(CourseFilter $filter): void
    {
        $this->filters[$filter->key()] = $filter;
    }

    /** @return list<CourseFilter> */
    public function all(): array
    {
        return array_values($this->filters);
    }

    public function get(string $key): ?CourseFilter
    {
        return $this->filters[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return isset($this->filters[$key]);
    }
}
