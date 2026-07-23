<?php

declare(strict_types=1);

namespace CourseDiscovery\PostTypes;

/**
 * The `provider` post type — the source of truth for a course's location(s).
 * See LocationTaxonomy for how a provider's assigned locations get mirrored
 * onto its courses.
 */
final class ProviderPostType
{
    public const SLUG = 'provider';

    public function register(): void
    {
        register_post_type(self::SLUG, [
            'labels' => [
                'name' => __('Providers', 'course-discovery'),
                'singular_name' => __('Provider', 'course-discovery'),
                'add_new_item' => __('Add New Provider', 'course-discovery'),
                'edit_item' => __('Edit Provider', 'course-discovery'),
                'search_items' => __('Search Providers', 'course-discovery'),
            ],
            'public' => true,
            'has_archive' => true,
            'show_in_rest' => true,
            'rest_base' => 'providers',
            'menu_icon' => 'dashicons-building',
            'supports' => ['title', 'editor', 'thumbnail'],
            'rewrite' => ['slug' => 'providers'],
        ]);
    }
}
