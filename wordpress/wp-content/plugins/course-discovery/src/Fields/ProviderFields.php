<?php

declare(strict_types=1);

namespace CourseDiscovery\Fields;

use CourseDiscovery\PostTypes\LocationTaxonomy;
use CourseDiscovery\PostTypes\ProviderPostType;

/**
 * ACF field group for `provider` — this is where Locations are actually
 * assigned (source of truth); Course's `location` terms are a derived
 * mirror, see Sync\LocationSync.
 */
final class ProviderFields
{
    public const FIELD_LOCATIONS = 'locations';

    public function register(): void
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group([
            'key' => 'group_course_discovery_provider',
            'title' => __('Provider Details', 'course-discovery'),
            'fields' => [
                [
                    'key' => 'field_provider_locations',
                    'label' => __('Locations', 'course-discovery'),
                    'name' => self::FIELD_LOCATIONS,
                    'type' => 'taxonomy',
                    'instructions' => __('Courses linked to this provider will inherit these locations automatically.', 'course-discovery'),
                    'taxonomy' => LocationTaxonomy::SLUG,
                    'field_type' => 'checkbox',
                    'add_term' => true,
                    'save_terms' => true,
                    'load_terms' => true,
                    'return_format' => 'id',
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => ProviderPostType::SLUG,
                    ],
                ],
            ],
        ]);
    }
}
