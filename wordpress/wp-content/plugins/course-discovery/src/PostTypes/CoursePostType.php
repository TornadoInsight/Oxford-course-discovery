<?php

declare(strict_types=1);

namespace CourseDiscovery\PostTypes;

/**
 * The `course` post type.
 *
 * Name -> post_title, Short description -> post_excerpt, Long description ->
 * post_content: all three are native WP fields rather than ACF/postmeta, so
 * they get core's indexed search (`WP_Query`'s `s` param already searches
 * title/excerpt/content) for free instead of reinventing it.
 */
final class CoursePostType
{
    public const SLUG = 'course';

    public function register(): void
    {
        register_post_type(self::SLUG, [
            'labels' => [
                'name' => __('Courses', 'course-discovery'),
                'singular_name' => __('Course', 'course-discovery'),
                'add_new_item' => __('Add New Course', 'course-discovery'),
                'edit_item' => __('Edit Course', 'course-discovery'),
                'search_items' => __('Search Courses', 'course-discovery'),
            ],
            'public' => true,
            'has_archive' => true,
            'show_in_rest' => true,
            'rest_base' => 'courses',
            'menu_icon' => 'dashicons-welcome-learn-more',
            'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions'],
            'rewrite' => ['slug' => 'courses'],
        ]);
    }
}
