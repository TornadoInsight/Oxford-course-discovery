<?php

declare(strict_types=1);

namespace CourseDiscovery\Tests\Integration\Query;

use CourseDiscovery\Domain\FilterCriteria;
use CourseDiscovery\Fields\CourseFields;
use CourseDiscovery\Fields\ProviderFields;
use CourseDiscovery\Filters\CategoryFilter;
use CourseDiscovery\Filters\FilterRegistry;
use CourseDiscovery\Filters\LocationFilter;
use CourseDiscovery\Filters\ProviderFilter;
use CourseDiscovery\Migrations\Migration_1_0_0_CreateStartDatesTable;
use CourseDiscovery\PostTypes\CourseCategoryTaxonomy;
use CourseDiscovery\PostTypes\CoursePostType;
use CourseDiscovery\PostTypes\LocationTaxonomy;
use CourseDiscovery\PostTypes\ProviderPostType;
use CourseDiscovery\Query\CourseRepository;
use CourseDiscovery\Sync\LocationSync;
use Yoast\WPTestUtils\WPIntegration\TestCase;

/**
 * Reproduces the brief's worked example exactly:
 *
 *   (provider = uosd OR provider = dmu)
 *   AND (location = india OR location = china)
 *   AND (category = graphic design)
 *
 * This is the single highest-value regression test in the suite — filter
 * grouping is called out explicitly in the brief as needing particular
 * attention, and it's the one behaviour that's easy to silently break while
 * "improving" an individual filter in isolation.
 */
final class CourseRepositorySearchTest extends TestCase
{
    public static function set_up_before_class(): void
    {
        parent::set_up_before_class();
        (new Migration_1_0_0_CreateStartDatesTable())->up();
    }

    public function test_and_across_filters_or_within_a_filter(): void
    {
        $india = self::factory()->term->create(['taxonomy' => LocationTaxonomy::SLUG, 'name' => 'India']);
        $china = self::factory()->term->create(['taxonomy' => LocationTaxonomy::SLUG, 'name' => 'China']);
        $vietnam = self::factory()->term->create(['taxonomy' => LocationTaxonomy::SLUG, 'name' => 'Vietnam']);

        $graphicDesign = self::factory()->term->create(['taxonomy' => CourseCategoryTaxonomy::SLUG, 'name' => 'Graphic Design']);
        $business = self::factory()->term->create(['taxonomy' => CourseCategoryTaxonomy::SLUG, 'name' => 'Business']);

        $uosd = $this->makeProvider('UOSD', [$india]);
        $dmu = $this->makeProvider('DMU', [$china]);
        $otherProvider = $this->makeProvider('Other Provider', [$vietnam]);

        // Matches: provider in (uosd, dmu) AND location in (india, china) AND category = graphic design.
        $matching1 = $this->makeCourse([$uosd], $graphicDesign);
        $matching2 = $this->makeCourse([$dmu], $graphicDesign);

        // Wrong category -> excluded despite matching provider/location.
        $wrongCategory = $this->makeCourse([$uosd], $business);

        // Wrong provider (and therefore wrong location) -> excluded despite matching category.
        $wrongProviderAndLocation = $this->makeCourse([$otherProvider], $graphicDesign);

        $registry = new FilterRegistry();
        $registry->register(new ProviderFilter());
        $registry->register(new LocationFilter());
        $registry->register(new CategoryFilter());

        $criteria = FilterCriteria::fromArray([
            'filters' => [
                'providers' => [(string) $uosd, (string) $dmu],
                'locations' => [(string) $india, (string) $china],
                'categories' => [(string) $graphicDesign],
            ],
        ]);

        $result = (new CourseRepository($registry))->search($criteria);
        $ids = array_map(static fn ($course) => $course->id, $result->courses);

        sort($ids);
        $expected = [$matching1, $matching2];
        sort($expected);

        self::assertSame($expected, $ids);
        self::assertNotContains($wrongCategory, $ids);
        self::assertNotContains($wrongProviderAndLocation, $ids);
    }

    /** @param list<int> $locationTermIds */
    private function makeProvider(string $name, array $locationTermIds): int
    {
        $providerId = self::factory()->post->create(['post_type' => ProviderPostType::SLUG, 'post_title' => $name]);
        update_field(ProviderFields::FIELD_LOCATIONS, $locationTermIds, $providerId);
        wp_set_object_terms($providerId, $locationTermIds, LocationTaxonomy::SLUG);

        return $providerId;
    }

    private function makeCourse(array $providerIds, int $categoryTermId): int
    {
        $courseId = self::factory()->post->create(['post_type' => CoursePostType::SLUG]);
        update_field(CourseFields::FIELD_PROVIDERS, $providerIds, $courseId);
        wp_set_object_terms($courseId, [$categoryTermId], CourseCategoryTaxonomy::SLUG);

        (new LocationSync())->syncCourse($courseId);

        return $courseId;
    }
}
