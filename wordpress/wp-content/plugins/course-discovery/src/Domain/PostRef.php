<?php

declare(strict_types=1);

namespace CourseDiscovery\Domain;

/**
 * A lightweight reference to a related post/term (Instructor, Provider,
 * Category, Location) as seen from a Course — just enough to render/link to
 * it, without pulling the whole related entity into the Course read model.
 */
final class PostRef
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
    ) {
    }
}
