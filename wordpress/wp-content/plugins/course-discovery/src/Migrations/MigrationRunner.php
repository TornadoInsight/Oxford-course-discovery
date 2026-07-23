<?php

declare(strict_types=1);

namespace CourseDiscovery\Migrations;

/**
 * Tracks which migrations have run via a wp_options row (course_discovery_db_version)
 * and applies any that haven't. Idempotent — safe to call on every request
 * (cheap: one autoloaded option read) or explicitly via `wp course-discovery migrate`.
 */
final class MigrationRunner
{
    private const OPTION = 'course_discovery_db_version';

    /** @param list<Migration> $migrations */
    public function __construct(private readonly array $migrations)
    {
    }

    public function run(): void
    {
        $applied = get_option(self::OPTION, []);
        if (!is_array($applied)) {
            $applied = [];
        }

        $changed = false;
        foreach ($this->migrations as $migration) {
            if (in_array($migration->version(), $applied, true)) {
                continue;
            }

            $migration->up();
            $applied[] = $migration->version();
            $changed = true;
        }

        if ($changed) {
            update_option(self::OPTION, $applied);
        }
    }
}
