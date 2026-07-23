<?php

declare(strict_types=1);

namespace CourseDiscovery\Fields;

use CourseDiscovery\PostTypes\CoursePostType;
use CourseDiscovery\PostTypes\InstructorPostType;
use CourseDiscovery\PostTypes\ProviderPostType;

/**
 * ACF field group for the `course` post type, registered in PHP (not the
 * ACF UI) so field definitions are version controlled and portable across
 * environments without exporting JSON from a live database.
 *
 * Name/short description/long description are deliberately absent here —
 * they map to native post_title/post_excerpt/post_content (see
 * CoursePostType).
 */
final class CourseFields
{
    public const FIELD_PRICE = 'price';
    public const FIELD_PROVIDERS = 'providers';
    public const FIELD_INSTRUCTORS = 'instructors';
    public const FIELD_START_DATES = 'start_dates';
    public const FIELD_START_DATE_ROW = 'date';

    public function register(): void
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group([
            'key' => 'group_course_discovery_course',
            'title' => __('Course Details', 'course-discovery'),
            'fields' => [
                [
                    'key' => 'field_course_price',
                    'label' => __('Price', 'course-discovery'),
                    'name' => self::FIELD_PRICE,
                    'type' => 'number',
                    'instructions' => __('Single numeric price. Extendable to a price range in future.', 'course-discovery'),
                    'min' => 0,
                    'step' => 0.01,
                ],
                [
                    'key' => 'field_course_providers',
                    'label' => __('Providers', 'course-discovery'),
                    'name' => self::FIELD_PROVIDERS,
                    'type' => 'relationship',
                    'instructions' => __('Course location(s) are derived automatically from the selected provider(s).', 'course-discovery'),
                    'post_type' => [ProviderPostType::SLUG],
                    'filters' => ['search'],
                    'return_format' => 'id',
                ],
                [
                    'key' => 'field_course_instructors',
                    'label' => __('Instructors', 'course-discovery'),
                    'name' => self::FIELD_INSTRUCTORS,
                    'type' => 'relationship',
                    'post_type' => [InstructorPostType::SLUG],
                    'filters' => ['search'],
                    'return_format' => 'id',
                ],
                [
                    'key' => 'field_course_start_dates',
                    'label' => __('Start Dates', 'course-discovery'),
                    'name' => self::FIELD_START_DATES,
                    'type' => 'repeater',
                    'instructions' => __('One row per intake. Only the month and year are used.', 'course-discovery'),
                    'layout' => 'table',
                    'button_label' => __('Add Start Date', 'course-discovery'),
                    'sub_fields' => [
                        [
                            'key' => 'field_course_start_date_row',
                            'label' => __('Month / Year', 'course-discovery'),
                            'name' => self::FIELD_START_DATE_ROW,
                            'type' => 'date_picker',
                            'display_format' => 'F Y',
                            'return_format' => 'Y-m-d',
                            'first_day' => 1,
                        ],
                    ],
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => CoursePostType::SLUG,
                    ],
                ],
            ],
        ]);
    }
}
