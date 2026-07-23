<?php

declare(strict_types=1);

namespace CourseDiscovery\PostTypes;

final class InstructorPostType
{
    public const SLUG = 'instructor';

    public function register(): void
    {
        register_post_type(self::SLUG, [
            'labels' => [
                'name' => __('Instructors', 'course-discovery'),
                'singular_name' => __('Instructor', 'course-discovery'),
                'add_new_item' => __('Add New Instructor', 'course-discovery'),
                'edit_item' => __('Edit Instructor', 'course-discovery'),
                'search_items' => __('Search Instructors', 'course-discovery'),
            ],
            'public' => true,
            'has_archive' => true,
            'show_in_rest' => true,
            'rest_base' => 'instructors',
            'menu_icon' => 'dashicons-businessperson',
            'supports' => ['title', 'editor', 'thumbnail'],
            'rewrite' => ['slug' => 'instructors'],
        ]);
    }
}
