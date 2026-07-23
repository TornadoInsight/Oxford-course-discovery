<?php

declare(strict_types=1);

namespace CourseDiscovery\Migrations;

final class Migration_1_0_0_CreateStartDatesTable implements Migration
{
    public function version(): string
    {
        return '1.0.0_create_start_dates_table';
    }

    public function up(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table = StartDatesTable::name();
        $charsetCollate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            course_id BIGINT UNSIGNED NOT NULL,
            start_date DATE NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY course_start_date (course_id, start_date),
            KEY start_date (start_date),
            KEY course_id (course_id)
        ) {$charsetCollate};";

        dbDelta($sql);
    }
}
