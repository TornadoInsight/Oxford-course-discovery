<?php

declare(strict_types=1);

namespace CourseDiscovery\Domain;

/**
 * A single selectable option within a filter (e.g. one provider, one
 * {month}-{year} start date). `sortKey` lets filters like start-dates expose
 * a chronological ordering distinct from alphabetical `label` ordering.
 */
final class FilterOption
{
    public function __construct(
        public readonly string $value,
        public readonly string $label,
        public readonly ?int $sortKey = null,
        public readonly ?int $count = null,
    ) {
    }
}
