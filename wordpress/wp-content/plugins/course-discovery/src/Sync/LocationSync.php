<?php

declare(strict_types=1);

namespace CourseDiscovery\Sync;

use CourseDiscovery\Fields\CourseFields;
use CourseDiscovery\Fields\ProviderFields;
use CourseDiscovery\PostTypes\CoursePostType;
use CourseDiscovery\PostTypes\LocationTaxonomy;
use CourseDiscovery\PostTypes\ProviderPostType;

/**
 * Keeps a Course's `location` terms mirrored from its Providers' `location`
 * terms, so LocationFilter can run a plain indexed tax_query against Course
 * instead of resolving providers -> locations on every request.
 *
 * Runs whenever a Course is saved (its provider selection may have changed)
 * or a Provider is saved (its locations may have changed, which then needs
 * to cascade to every course already linked to it).
 */
final class LocationSync
{
    public function register(): void
    {
        add_action('acf/save_post', $this->onSavePost(...), 20);
    }

    public function onSavePost(mixed $postId): void
    {
        if (!is_numeric($postId)) {
            return;
        }

        $postId = (int) $postId;

        match (get_post_type($postId)) {
            CoursePostType::SLUG => $this->syncCourse($postId),
            ProviderPostType::SLUG => $this->syncCoursesLinkedToProvider($postId),
            default => null,
        };
    }

    public function syncCourse(int $courseId): void
    {
        $providerIds = array_map('intval', (array) get_field(CourseFields::FIELD_PROVIDERS, $courseId));

        $locationTermIds = [];
        foreach ($providerIds as $providerId) {
            $providerLocations = array_map('intval', (array) get_field(ProviderFields::FIELD_LOCATIONS, $providerId));
            foreach ($providerLocations as $termId) {
                $locationTermIds[$termId] = true;
            }
        }

        wp_set_object_terms($courseId, array_keys($locationTermIds), LocationTaxonomy::SLUG);
    }

    private function syncCoursesLinkedToProvider(int $providerId): void
    {
        // ACF relationship fields serialize as an array of string post IDs; quoting the
        // needle avoids "1" spuriously matching "12" (still a LIKE-on-serialized-meta
        // limitation worth documenting — see README Performance section).
        $courseIds = get_posts([
            'post_type' => CoursePostType::SLUG,
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => CourseFields::FIELD_PROVIDERS,
                    'value' => '"' . $providerId . '"',
                    'compare' => 'LIKE',
                ],
            ],
        ]);

        foreach ($courseIds as $courseId) {
            $this->syncCourse((int) $courseId);
        }
    }
}
