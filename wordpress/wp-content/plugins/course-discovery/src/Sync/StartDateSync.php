<?php

declare(strict_types=1);

namespace CourseDiscovery\Sync;

use CourseDiscovery\Domain\StartDate;
use CourseDiscovery\Fields\CourseFields;
use CourseDiscovery\Migrations\StartDatesTable;
use CourseDiscovery\PostTypes\CoursePostType;

/**
 * Mirrors the `start_dates` ACF repeater into wp_course_discovery_start_dates
 * whenever a Course is saved. The repeater stays the editable source of
 * truth in wp-admin; the table exists purely as a queryable/sortable index.
 */
final class StartDateSync
{
    public function register(): void
    {
        add_action('acf/save_post', $this->onSavePost(...), 20);
        add_action('deleted_post', $this->onDeletedPost(...));
    }

    public function onSavePost(mixed $postId): void
    {
        if (!is_numeric($postId) || get_post_type((int) $postId) !== CoursePostType::SLUG) {
            return;
        }

        $courseId = (int) $postId;
        $rows = (array) get_field(CourseFields::FIELD_START_DATES, $courseId);

        $startDates = [];
        foreach ($rows as $row) {
            $raw = $row[CourseFields::FIELD_START_DATE_ROW] ?? null;
            if (!$raw) {
                continue;
            }
            $startDates[] = StartDate::fromString((string) $raw);
        }

        StartDatesTable::replaceForCourse($courseId, $startDates);
    }

    public function onDeletedPost(int $postId): void
    {
        StartDatesTable::replaceForCourse($postId, []);
    }
}
