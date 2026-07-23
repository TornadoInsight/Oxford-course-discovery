<?php

declare(strict_types=1);

namespace CourseDiscovery\Tests\Feature;

use CourseDiscovery\Fields\CourseFields;
use CourseDiscovery\Migrations\Migration_1_0_0_CreateStartDatesTable;
use CourseDiscovery\PostTypes\CourseCategoryTaxonomy;
use CourseDiscovery\PostTypes\CoursePostType;
use WP_REST_Request;
use Yoast\WPTestUtils\WPIntegration\TestCase;

/**
 * Feature-level tests hitting the actual registered REST routes via
 * rest_do_request(), the same dispatch path a real HTTP request takes.
 */
final class RestApiTest extends TestCase
{
    public static function set_up_before_class(): void
    {
        parent::set_up_before_class();
        (new Migration_1_0_0_CreateStartDatesTable())->up();
    }

    public function test_courses_endpoint_returns_expected_shape(): void
    {
        self::factory()->post->create([
            'post_type' => CoursePostType::SLUG,
            'post_title' => 'Data Science Bootcamp',
            'post_status' => 'publish',
        ]);

        $request = new WP_REST_Request('GET', '/course-discovery/v1/courses');
        $response = rest_do_request($request);
        $data = $response->get_data();

        self::assertSame(200, $response->get_status());
        self::assertArrayHasKey('courses', $data);
        self::assertArrayHasKey('total', $data);
        self::assertSame('Data Science Bootcamp', $data['courses'][0]['name']);
    }

    public function test_courses_endpoint_applies_search_param(): void
    {
        self::factory()->post->create([
            'post_type' => CoursePostType::SLUG,
            'post_title' => 'Business English Intensive',
            'post_status' => 'publish',
        ]);
        self::factory()->post->create([
            'post_type' => CoursePostType::SLUG,
            'post_title' => 'Data Science Bootcamp',
            'post_status' => 'publish',
        ]);

        $request = new WP_REST_Request('GET', '/course-discovery/v1/courses');
        $request->set_param('search', 'business');
        $data = rest_do_request($request)->get_data();

        self::assertCount(1, $data['courses']);
        self::assertSame('Business English Intensive', $data['courses'][0]['name']);
    }

    public function test_filters_endpoint_lists_every_registered_filter(): void
    {
        $request = new WP_REST_Request('GET', '/course-discovery/v1/filters');
        $data = rest_do_request($request)->get_data();

        $keys = array_column($data['filters'], 'key');

        self::assertContains('search', $keys);
        self::assertContains('providers', $keys);
        self::assertContains('locations', $keys);
        self::assertContains('start_dates', $keys);
        self::assertContains('categories', $keys);
    }

    public function test_courses_endpoint_paginates(): void
    {
        for ($i = 0; $i < 3; $i++) {
            self::factory()->post->create(['post_type' => CoursePostType::SLUG, 'post_status' => 'publish']);
        }

        $request = new WP_REST_Request('GET', '/course-discovery/v1/courses');
        $request->set_param('per_page', 2);
        $data = rest_do_request($request)->get_data();

        self::assertCount(2, $data['courses']);
        self::assertSame(3, $data['total']);
        self::assertSame(2, $data['totalPages']);
    }
}
