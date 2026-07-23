<?php

declare(strict_types=1);

namespace CourseDiscovery\Http;

use CourseDiscovery\Domain\FilterCriteria;
use CourseDiscovery\Support\Hooks;
use WP_REST_Request;

/**
 * Turns an untrusted REST request into a validated, immutable FilterCriteria.
 * The course_discovery/criteria/transform filter runs here — third parties
 * can rewrite the parsed criteria (e.g. expand a search synonym, force a
 * default category) before any CourseFilter sees it.
 */
final class CriteriaFactory
{
    public static function fromRequest(WP_REST_Request $request): FilterCriteria
    {
        $filters = [];
        foreach ((array) $request->get_param('filters') as $key => $values) {
            $filters[(string) $key] = (array) $values;
        }

        $criteria = FilterCriteria::fromArray([
            'search' => $request->get_param('search'),
            'filters' => $filters,
            'page' => $request->get_param('page'),
            'per_page' => $request->get_param('per_page'),
            'order_by' => $request->get_param('order_by'),
            'order' => $request->get_param('order'),
        ]);

        /** @var FilterCriteria $criteria */
        $criteria = apply_filters(Hooks::TRANSFORM_CRITERIA, $criteria, $request);

        return $criteria;
    }
}
