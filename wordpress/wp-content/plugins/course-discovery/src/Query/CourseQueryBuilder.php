<?php

declare(strict_types=1);

namespace CourseDiscovery\Query;

use CourseDiscovery\PostTypes\CoursePostType;
use WP_Query;

/**
 * Domain-specific abstraction over WP_Query's arg array. Filters never touch
 * WP_Query directly — they call these intention-revealing methods, which
 * keeps the AND/OR grouping rules (top-level AND across filters, OR within a
 * filter's own selected values) enforced in exactly one place instead of
 * re-implemented per filter.
 */
final class CourseQueryBuilder
{
    /** @var list<array<string, mixed>> */
    private array $taxQuery = [];

    /** @var list<array<string, mixed>> */
    private array $metaQuery = [];

    /** @var list<int>|null Intersected across every custom-table-backed filter (e.g. start dates). */
    private ?array $restrictToIds = null;

    private ?string $search = null;
    private int $page = 1;
    private int $perPage = 12;
    private string $orderBy = 'date';
    private string $order = 'DESC';

    /** @param list<int|string> $termIds OR'd together. */
    public function addTaxTerms(string $taxonomy, array $termIds): void
    {
        if ($termIds === []) {
            return;
        }

        $this->taxQuery[] = [
            'taxonomy' => $taxonomy,
            'field' => 'term_id',
            'terms' => array_map('intval', $termIds),
            'operator' => 'IN',
        ];
    }

    /**
     * OR's together a set of "meta value is one of these" clauses for a
     * single meta key. Suited to ACF relationship/post_object fields, which
     * store a serialized array of related post IDs — LIKE against a quoted
     * value is the standard (if imperfect at scale — see README) way to
     * test array membership without unserializing every row in PHP.
     *
     * @param list<string> $values OR'd together.
     */
    public function addMetaIn(string $key, array $values): void
    {
        if ($values === []) {
            return;
        }

        $clause = ['relation' => 'OR'];
        foreach ($values as $value) {
            $clause[] = [
                'key' => $key,
                'value' => '"' . $value . '"',
                'compare' => 'LIKE',
            ];
        }

        $this->metaQuery[] = $clause;
    }

    /**
     * Restrict results to a pre-computed set of post IDs (e.g. from the
     * start-dates lookup table). Calling this more than once intersects the
     * sets, preserving top-level AND semantics across filters.
     *
     * @param list<int> $postIds
     */
    public function restrictToPostIds(array $postIds): void
    {
        $this->restrictToIds = $this->restrictToIds === null
            ? $postIds
            : array_values(array_intersect($this->restrictToIds, $postIds));
    }

    public function search(string $term): void
    {
        $this->search = $term;
    }

    public function setPage(int $page): void
    {
        $this->page = max(1, $page);
    }

    public function setPerPage(int $perPage): void
    {
        $this->perPage = max(1, $perPage);
    }

    public function orderBy(string $orderBy, string $order): void
    {
        $this->orderBy = $orderBy;
        $this->order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
    }

    /** @return array<string, mixed> Raw WP_Query args, exposed so it can pass through the query_args hook. */
    public function toQueryArgs(): array
    {
        $args = [
            'post_type' => CoursePostType::SLUG,
            'post_status' => 'publish',
            'paged' => $this->page,
            'posts_per_page' => $this->perPage,
            'orderby' => $this->orderBy,
            'order' => $this->order,
            'ignore_sticky_posts' => true,
        ];

        if ($this->search !== null && $this->search !== '') {
            $args['s'] = $this->search;
        }

        if ($this->taxQuery !== []) {
            $args['tax_query'] = array_merge(['relation' => 'AND'], $this->taxQuery);
        }

        if ($this->metaQuery !== []) {
            $args['meta_query'] = array_merge(['relation' => 'AND'], $this->metaQuery);
        }

        if ($this->restrictToIds !== null) {
            // An empty array here means a custom-table filter matched nothing;
            // post__in => [0] forces WP_Query to correctly return zero results
            // instead of ignoring an empty post__in and matching everything.
            $args['post__in'] = $this->restrictToIds === [] ? [0] : $this->restrictToIds;
        }

        return $args;
    }

    public function build(): WP_Query
    {
        return new WP_Query($this->toQueryArgs());
    }
}
