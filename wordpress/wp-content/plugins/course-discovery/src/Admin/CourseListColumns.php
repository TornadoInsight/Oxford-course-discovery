<?php

declare(strict_types=1);

namespace CourseDiscovery\Admin;

use CourseDiscovery\Fields\CourseFields;
use CourseDiscovery\PostTypes\CoursePostType;
use CourseDiscovery\PostTypes\LocationTaxonomy;

/**
 * Minor wp-admin ergonomics: surface Providers/Locations/Price directly in
 * the Courses list table instead of requiring an edit-screen visit to see
 * them.
 */
final class CourseListColumns
{
    public function register(): void
    {
        add_filter('manage_' . CoursePostType::SLUG . '_posts_columns', $this->addColumns(...));
        add_action('manage_' . CoursePostType::SLUG . '_posts_custom_column', $this->renderColumn(...), 10, 2);
    }

    /** @param array<string, string> $columns @return array<string, string> */
    public function addColumns(array $columns): array
    {
        $withExtras = [];
        foreach ($columns as $key => $label) {
            $withExtras[$key] = $label;
            if ($key === 'title') {
                $withExtras['providers'] = __('Providers', 'course-discovery');
                $withExtras['locations'] = __('Locations', 'course-discovery');
                $withExtras['price'] = __('Price', 'course-discovery');
            }
        }

        return $withExtras;
    }

    public function renderColumn(string $column, int $postId): void
    {
        match ($column) {
            'providers' => $this->renderProviders($postId),
            'locations' => $this->renderLocations($postId),
            'price' => $this->renderPrice($postId),
            default => null,
        };
    }

    private function renderProviders(int $postId): void
    {
        $providerIds = array_map('intval', (array) get_field(CourseFields::FIELD_PROVIDERS, $postId));
        $names = array_filter(array_map(static fn (int $id) => get_the_title($id), $providerIds));

        echo esc_html($names === [] ? '—' : implode(', ', $names));
    }

    private function renderLocations(int $postId): void
    {
        $terms = get_the_terms($postId, LocationTaxonomy::SLUG);
        echo esc_html(is_array($terms) ? implode(', ', wp_list_pluck($terms, 'name')) : '—');
    }

    private function renderPrice(int $postId): void
    {
        $price = get_field(CourseFields::FIELD_PRICE, $postId);
        echo $price === '' || $price === null ? '—' : esc_html(number_format((float) $price, 2));
    }
}
