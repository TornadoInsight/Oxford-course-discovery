<?php

declare(strict_types=1);

namespace CourseDiscovery\Frontend;

use CourseDiscovery\Domain\FilterContext;
use CourseDiscovery\Domain\FilterCriteria;
use CourseDiscovery\Filters\FilterRegistry;
use CourseDiscovery\Query\CourseRepository;

/**
 * Renders `[course_discovery]`: a server-rendered result list plus a filter
 * `<form>` that works over a plain GET request with no JavaScript at all,
 * progressively enhanced client-side (see assets/src/js/app.js) to fetch
 * from the REST API and update in place instead of reloading the page.
 */
final class CourseDiscoveryShortcode
{
    private const TAG = 'course_discovery';

    public function __construct(
        private readonly FilterRegistry $registry,
        private readonly CourseRepository $repository,
    ) {
    }

    public function register(): void
    {
        add_shortcode(self::TAG, $this->render(...));
    }

    /** @param array<string, mixed>|string $atts */
    public function render(array|string $atts = ''): string
    {
        $this->enqueueAssets();

        $criteria = FilterCriteria::fromArray([
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only GET filter state, no side effects.
            'search' => wp_unslash($_GET['search'] ?? null),
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            'filters' => $this->sanitizeFilters(wp_unslash($_GET['filters'] ?? [])),
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            'page' => wp_unslash($_GET['paged'] ?? 1),
        ]);

        $result = $this->repository->search($criteria);
        $context = new FilterContext($criteria);
        $filters = $this->registry->all();

        ob_start();
        require __DIR__ . '/templates/course-discovery.php';

        return (string) ob_get_clean();
    }

    /**
     * @param mixed $raw
     * @return array<string, list<string>>
     */
    private function sanitizeFilters(mixed $raw): array
    {
        $clean = [];
        foreach ((array) $raw as $key => $values) {
            $key = sanitize_key((string) $key);
            $clean[$key] = array_map('sanitize_text_field', (array) $values);
        }

        return $clean;
    }

    private function enqueueAssets(): void
    {
        $url = plugin_dir_url(COURSE_DISCOVERY_FILE) . 'assets/build/';
        $path = plugin_dir_path(COURSE_DISCOVERY_FILE) . 'assets/build/';

        if (file_exists($path . 'app.css')) {
            wp_enqueue_style('course-discovery', $url . 'app.css', [], (string) filemtime($path . 'app.css'));
        }

        if (file_exists($path . 'app.js')) {
            wp_enqueue_script('course-discovery', $url . 'app.js', [], (string) filemtime($path . 'app.js'), true);
            wp_localize_script('course-discovery', 'CourseDiscoveryConfig', [
                'restUrl' => esc_url_raw(rest_url('course-discovery/v1')),
            ]);
        }
    }
}
