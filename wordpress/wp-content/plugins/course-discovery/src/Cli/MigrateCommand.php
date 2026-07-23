<?php

declare(strict_types=1);

namespace CourseDiscovery\Cli;

use CourseDiscovery\Migrations\MigrationRunner;
use WP_CLI;

final class MigrateCommand
{
    public function __construct(private readonly MigrationRunner $runner)
    {
    }

    /**
     * Runs any pending Course Discovery database migrations.
     *
     * ## EXAMPLES
     *
     *     wp course-discovery migrate
     *
     * @param list<string> $args
     * @param array<string, string> $assocArgs
     */
    public function __invoke(array $args, array $assocArgs): void
    {
        $this->runner->run();
        WP_CLI::success('Course Discovery migrations are up to date.');
    }
}
