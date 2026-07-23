<?php

declare(strict_types=1);

namespace CourseDiscovery\Http;

use CourseDiscovery\Domain\Course;
use CourseDiscovery\Domain\FilterContext;
use CourseDiscovery\Domain\FilterOption;
use CourseDiscovery\Filters\Contract\CourseFilter;
use CourseDiscovery\Filters\FilterRegistry;
use CourseDiscovery\Query\CourseRepository;
use CourseDiscovery\Support\Hooks;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Thin HTTP boundary: parse request -> CriteriaFactory -> CourseRepository ->
 * array. No filter/query logic lives here, which is what makes the pipeline
 * independently unit/integration-testable without spinning up HTTP.
 */
final class RestController
{
    private const NAMESPACE = 'course-discovery/v1';

    public function __construct(
        private readonly FilterRegistry $registry,
        private readonly CourseRepository $repository,
    ) {
    }

    public function register(): void
    {
        add_action('rest_api_init', $this->registerRoutes(...));
    }

    private function registerRoutes(): void
    {
        register_rest_route(self::NAMESPACE, '/courses', [
            'methods' => 'GET',
            'callback' => $this->getCourses(...),
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(self::NAMESPACE, '/filters', [
            'methods' => 'GET',
            'callback' => $this->getFilters(...),
            'permission_callback' => '__return_true',
        ]);
    }

    public function getCourses(WP_REST_Request $request): WP_REST_Response
    {
        $criteria = CriteriaFactory::fromRequest($request);
        $result = $this->repository->search($criteria);

        return new WP_REST_Response([
            'courses' => array_map(
                static fn (Course $course) => apply_filters(Hooks::TRANSFORM_COURSE, $course->toArray(), $course),
                $result->courses
            ),
            'total' => $result->total,
            'totalPages' => $result->totalPages(),
            'page' => $result->page,
            'perPage' => $result->perPage,
        ]);
    }

    public function getFilters(WP_REST_Request $request): WP_REST_Response
    {
        $criteria = CriteriaFactory::fromRequest($request);
        $context = new FilterContext($criteria);

        $filters = [];
        foreach ($this->registry->all() as $filter) {
            $options = $filter->options($context);
            if ($options === [] && $filter->key() !== 'search') {
                continue;
            }

            $filters[] = [
                'key' => $filter->key(),
                'label' => $filter->label(),
                'options' => array_map(
                    static fn (FilterOption $option) => [
                        'value' => $option->value,
                        'label' => $option->label,
                        'sortKey' => $option->sortKey,
                        'count' => $option->count,
                    ],
                    $options
                ),
            ];
        }

        return new WP_REST_Response(['filters' => $filters]);
    }
}
