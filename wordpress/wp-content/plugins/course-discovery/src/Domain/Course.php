<?php

declare(strict_types=1);

namespace CourseDiscovery\Domain;

/**
 * Read model for a single course. Assembled by CourseRepository from a
 * WP_Post plus its related data — nothing downstream of the repository
 * (REST transformers, templates) should touch WP_Post or raw postmeta
 * directly.
 *
 * @phpstan-type CourseArray array{
 *   id: int, name: string, slug: string, permalink: string,
 *   shortDescription: string, longDescription: string,
 *   price: array{amount: float, currency: string, formatted: string}|null,
 *   instructors: list<array{id: int, name: string, slug: string}>,
 *   providers: list<array{id: int, name: string, slug: string}>,
 *   locations: list<array{id: int, name: string, slug: string}>,
 *   categories: list<array{id: int, name: string, slug: string}>,
 *   startDates: list<array{key: string, label: string}>
 * }
 */
final class Course
{
    /**
     * @param list<PostRef> $instructors
     * @param list<PostRef> $providers
     * @param list<PostRef> $locations
     * @param list<PostRef> $categories
     * @param list<StartDate> $startDates
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly string $permalink,
        public readonly string $shortDescription,
        public readonly string $longDescription,
        public readonly ?Price $price,
        public readonly array $instructors,
        public readonly array $providers,
        public readonly array $locations,
        public readonly array $categories,
        public readonly array $startDates,
    ) {
    }

    /** @return list<StartDate> Sorted chronologically, nearest first. */
    public function upcomingStartDates(): array
    {
        $dates = $this->startDates;
        usort($dates, static fn (StartDate $a, StartDate $b) => $a->sortKey() <=> $b->sortKey());

        return $dates;
    }

    /** @return CourseArray */
    public function toArray(): array
    {
        $refMapper = static fn (PostRef $ref) => ['id' => $ref->id, 'name' => $ref->name, 'slug' => $ref->slug];

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'permalink' => $this->permalink,
            'shortDescription' => $this->shortDescription,
            'longDescription' => $this->longDescription,
            'price' => $this->price === null ? null : [
                'amount' => $this->price->amount(),
                'currency' => $this->price->currency(),
                'formatted' => $this->price->format(),
            ],
            'instructors' => array_map($refMapper, $this->instructors),
            'providers' => array_map($refMapper, $this->providers),
            'locations' => array_map($refMapper, $this->locations),
            'categories' => array_map($refMapper, $this->categories),
            'startDates' => array_map(
                static fn (StartDate $d) => ['key' => $d->key(), 'label' => $d->label()],
                $this->upcomingStartDates()
            ),
        ];
    }
}
