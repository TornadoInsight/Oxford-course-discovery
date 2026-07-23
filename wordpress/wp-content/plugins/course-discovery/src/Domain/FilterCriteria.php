<?php

declare(strict_types=1);

namespace CourseDiscovery\Domain;

/**
 * Immutable representation of "what the user asked for" — the parsed,
 * validated request, independent of HTTP/WP_Query concerns. Built once by
 * CriteriaFactory, then optionally rewritten by third parties via the
 * course_discovery/criteria/transform filter before any CourseFilter sees it.
 *
 * @phpstan-type ValueMap array<string, list<string>>
 */
final class FilterCriteria
{
    /** @param ValueMap $values Filter key => list of selected raw values (OR'd within a key). */
    private function __construct(
        private readonly ?string $search,
        private readonly array $values,
        private readonly int $page,
        private readonly int $perPage,
        private readonly string $orderBy,
        private readonly string $order,
    ) {
    }

    /** @param array<string, mixed> $raw Untrusted input, e.g. a REST request's query params. */
    public static function fromArray(array $raw): self
    {
        $values = [];
        foreach ((array) ($raw['filters'] ?? []) as $key => $rawValues) {
            $list = array_values(array_filter(array_map('strval', (array) $rawValues), static fn (string $v) => $v !== ''));
            if ($list !== []) {
                $values[(string) $key] = $list;
            }
        }

        $search = isset($raw['search']) ? trim((string) $raw['search']) : null;

        return new self(
            search: $search === '' ? null : $search,
            values: $values,
            page: max(1, (int) ($raw['page'] ?? 1)),
            perPage: min(100, max(1, (int) ($raw['per_page'] ?? 12))),
            orderBy: (string) ($raw['order_by'] ?? 'relevance'),
            order: strtoupper((string) ($raw['order'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC',
        );
    }

    public function search(): ?string
    {
        return $this->search;
    }

    /** @return list<string> */
    public function valuesFor(string $key): array
    {
        return $this->values[$key] ?? [];
    }

    public function has(string $key): bool
    {
        return isset($this->values[$key]) && $this->values[$key] !== [];
    }

    /** @return ValueMap */
    public function all(): array
    {
        return $this->values;
    }

    public function page(): int
    {
        return $this->page;
    }

    public function perPage(): int
    {
        return $this->perPage;
    }

    public function orderBy(): string
    {
        return $this->orderBy;
    }

    public function order(): string
    {
        return $this->order;
    }

    /** @param list<string> $values */
    public function withValues(string $key, array $values): self
    {
        $clone = clone $this;
        $newValues = $this->values;
        if ($values === []) {
            unset($newValues[$key]);
        } else {
            $newValues[$key] = array_values($values);
        }

        return new self($this->search, $newValues, $this->page, $this->perPage, $this->orderBy, $this->order);
    }

    public function withSearch(?string $search): self
    {
        return new self($search, $this->values, $this->page, $this->perPage, $this->orderBy, $this->order);
    }

    public function withOrder(string $orderBy, string $order): self
    {
        return new self($this->search, $this->values, $this->page, $this->perPage, $orderBy, $order);
    }
}
