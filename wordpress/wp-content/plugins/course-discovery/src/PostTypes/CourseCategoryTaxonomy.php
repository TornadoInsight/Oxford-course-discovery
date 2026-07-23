<?php

declare(strict_types=1);

namespace CourseDiscovery\PostTypes;

final class CourseCategoryTaxonomy
{
    public const SLUG = 'course_category';

    public function register(): void
    {
        register_taxonomy(self::SLUG, [CoursePostType::SLUG], [
            'labels' => [
                'name' => __('Categories', 'course-discovery'),
                'singular_name' => __('Category', 'course-discovery'),
            ],
            'hierarchical' => true,
            'public' => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'course-category'],
        ]);
    }
}
