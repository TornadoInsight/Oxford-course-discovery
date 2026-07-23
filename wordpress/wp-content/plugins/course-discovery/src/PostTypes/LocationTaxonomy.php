<?php

declare(strict_types=1);

namespace CourseDiscovery\PostTypes;

/**
 * `location` is a non-hierarchical taxonomy whose terms are assigned to
 * Providers (the source of truth) and mirrored onto Courses by
 * LocationSync, so that filtering courses by location is a plain indexed
 * tax_query instead of a per-request join across providers.
 *
 * It's registered against both post types so the tax_query works on
 * `course`, but the edit-screen meta box is only shown on `provider` — see
 * removeCourseMetaBox() — since a course's locations are derived, not
 * directly editable.
 */
final class LocationTaxonomy
{
    public const SLUG = 'location';

    public function register(): void
    {
        register_taxonomy(self::SLUG, [ProviderPostType::SLUG, CoursePostType::SLUG], [
            'labels' => [
                'name' => __('Locations', 'course-discovery'),
                'singular_name' => __('Location', 'course-discovery'),
            ],
            'hierarchical' => false,
            'public' => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'location'],
        ]);

        add_action('admin_menu', $this->removeCourseMetaBox(...));
    }

    private function removeCourseMetaBox(): void
    {
        remove_meta_box('locationdiv', CoursePostType::SLUG, 'side');
    }
}
