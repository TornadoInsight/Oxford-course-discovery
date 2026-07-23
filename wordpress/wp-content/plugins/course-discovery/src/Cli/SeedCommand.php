<?php

declare(strict_types=1);

namespace CourseDiscovery\Cli;

use CourseDiscovery\Fields\CourseFields;
use CourseDiscovery\Fields\ProviderFields;
use CourseDiscovery\PostTypes\CourseCategoryTaxonomy;
use CourseDiscovery\PostTypes\CoursePostType;
use CourseDiscovery\PostTypes\InstructorPostType;
use CourseDiscovery\PostTypes\LocationTaxonomy;
use CourseDiscovery\PostTypes\ProviderPostType;
use DateTimeImmutable;
use WP_CLI;

/**
 * Populates demo data so the live deployment has something real to filter.
 * Idempotent — safe to run repeatedly, it reuses posts/terms by exact title.
 */
final class SeedCommand
{
    /**
     * ## EXAMPLES
     *
     *     wp course-discovery seed
     *
     * @param list<string> $args
     * @param array<string, string> $assocArgs
     */
    public function __invoke(array $args, array $assocArgs): void
    {
        $categories = $this->seedTerms(
            ['Graphic Design', 'Business', 'English Language', 'Computer Science', 'Marketing'],
            CourseCategoryTaxonomy::SLUG
        );
        $locations = $this->seedTerms(
            ['United Kingdom', 'India', 'China', 'Vietnam', 'United States'],
            LocationTaxonomy::SLUG
        );
        $instructors = $this->seedInstructors();
        $providers = $this->seedProviders($locations);
        $this->seedCourses($providers, $instructors, $categories);

        WP_CLI::success('Seeded demo Course Discovery content.');
    }

    /** @param list<string> $names @return array<string, int> */
    private function seedTerms(array $names, string $taxonomy): array
    {
        $ids = [];
        foreach ($names as $name) {
            $existing = term_exists($name, $taxonomy);
            if (is_array($existing)) {
                $ids[$name] = (int) $existing['term_id'];
                continue;
            }

            $created = wp_insert_term($name, $taxonomy);
            $ids[$name] = is_wp_error($created) ? 0 : (int) $created['term_id'];
        }

        return $ids;
    }

    /** @return list<int> */
    private function seedInstructors(): array
    {
        $names = ['Alice Wren', 'Marcus Byrne', 'Priya Nair', 'Tom Alden', 'Sofia Reyes'];

        return array_map(
            fn (string $name) => $this->findOrCreatePost(
                $name,
                InstructorPostType::SLUG,
                "{$name} is an experienced instructor with a track record of helping students succeed."
            ),
            $names
        );
    }

    /** @param array<string, int> $locations @return array<string, int> */
    private function seedProviders(array $locations): array
    {
        $providers = [
            'Oxford International London' => ['United Kingdom'],
            'De Montfort University' => ['United Kingdom'],
            'Oxford International Shanghai' => ['China'],
            'Oxford International India' => ['India'],
            'Oxford International Vietnam' => ['Vietnam'],
        ];

        $ids = [];
        foreach ($providers as $name => $providerLocations) {
            $postId = $this->findOrCreatePost($name, ProviderPostType::SLUG, 'A trusted Oxford International partner provider.');

            $termIds = array_values(array_map(static fn (string $loc) => $locations[$loc], $providerLocations));
            update_field(ProviderFields::FIELD_LOCATIONS, $termIds, $postId);
            wp_set_object_terms($postId, $termIds, LocationTaxonomy::SLUG);

            $ids[$name] = $postId;
        }

        return $ids;
    }

    /**
     * @param array<string, int> $providers
     * @param list<int> $instructors
     * @param array<string, int> $categories
     */
    private function seedCourses(array $providers, array $instructors, array $categories): void
    {
        $providerNames = array_keys($providers);

        $courses = [
            ['name' => 'Graphic Design Foundations', 'price' => 1200, 'category' => 'Graphic Design'],
            ['name' => 'Business English Intensive', 'price' => 950, 'category' => 'English Language'],
            ['name' => 'Introduction to Computer Science', 'price' => 1500, 'category' => 'Computer Science'],
            ['name' => 'Digital Marketing Strategy', 'price' => 1100, 'category' => 'Marketing'],
            ['name' => 'MBA Foundations', 'price' => 2200, 'category' => 'Business'],
            ['name' => 'UX & Product Design', 'price' => 1300, 'category' => 'Graphic Design'],
            ['name' => 'Academic English Pathway', 'price' => 890, 'category' => 'English Language'],
            ['name' => 'Data Science Bootcamp', 'price' => 1800, 'category' => 'Computer Science'],
        ];

        foreach ($courses as $index => $courseData) {
            $postId = $this->findOrCreatePost(
                $courseData['name'],
                CoursePostType::SLUG,
                "<p>{$courseData['name']} is a comprehensive programme covering fundamentals through to advanced practice, delivered by industry-experienced instructors.</p>"
            );
            wp_update_post([
                'ID' => $postId,
                'post_excerpt' => "A short introduction to {$courseData['name']}.",
            ]);

            update_field(CourseFields::FIELD_PRICE, $courseData['price'], $postId);
            update_field(CourseFields::FIELD_PROVIDERS, [$providers[$providerNames[$index % count($providerNames)]]], $postId);
            update_field(CourseFields::FIELD_INSTRUCTORS, [
                $instructors[$index % count($instructors)],
                $instructors[($index + 1) % count($instructors)],
            ], $postId);

            wp_set_object_terms($postId, [$categories[$courseData['category']]], CourseCategoryTaxonomy::SLUG);

            $startDateRows = [];
            foreach ([1, 4, 8] as $monthsAhead) {
                $date = (new DateTimeImmutable('first day of next month'))->modify("+{$monthsAhead} months");
                $startDateRows[] = [CourseFields::FIELD_START_DATE_ROW => $date->format('Y-m-d')];
            }
            update_field(CourseFields::FIELD_START_DATES, $startDateRows, $postId);

            // update_field() doesn't fire ACF's own save lifecycle, so trigger our sync explicitly.
            do_action('acf/save_post', $postId);
        }
    }

    private function findOrCreatePost(string $title, string $postType, string $content): int
    {
        $existing = get_posts([
            'post_type' => $postType,
            'title' => $title,
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
        ]);

        if ($existing !== []) {
            return (int) $existing[0];
        }

        return (int) wp_insert_post([
            'post_type' => $postType,
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => 'publish',
        ]);
    }
}
