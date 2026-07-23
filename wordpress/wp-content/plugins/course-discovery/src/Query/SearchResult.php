<?php

declare(strict_types=1);

namespace CourseDiscovery\Query;

use CourseDiscovery\Domain\Course;

final class SearchResult
{
    /** @param list<Course> $courses */
    public function __construct(
        public readonly array $courses,
        public readonly int $total,
        public readonly int $page,
        public readonly int $perPage,
    ) {
    }

    public function totalPages(): int
    {
        return $this->perPage > 0 ? (int) ceil($this->total / $this->perPage) : 0;
    }
}
