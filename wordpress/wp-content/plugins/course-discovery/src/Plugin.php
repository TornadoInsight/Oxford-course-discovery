<?php

declare(strict_types=1);

namespace CourseDiscovery;

use CourseDiscovery\Admin\CourseListColumns;
use CourseDiscovery\Cli\MigrateCommand;
use CourseDiscovery\Cli\SeedCommand;
use CourseDiscovery\Fields\CourseFields;
use CourseDiscovery\Fields\ProviderFields;
use CourseDiscovery\Filters\CategoryFilter;
use CourseDiscovery\Filters\FilterRegistry;
use CourseDiscovery\Filters\LocationFilter;
use CourseDiscovery\Filters\ProviderFilter;
use CourseDiscovery\Filters\SearchFilter;
use CourseDiscovery\Filters\StartDateFilter;
use CourseDiscovery\Frontend\CourseDiscoveryShortcode;
use CourseDiscovery\Http\RestController;
use CourseDiscovery\Migrations\Migration_1_0_0_CreateStartDatesTable;
use CourseDiscovery\Migrations\MigrationRunner;
use CourseDiscovery\PostTypes\CourseCategoryTaxonomy;
use CourseDiscovery\PostTypes\CoursePostType;
use CourseDiscovery\PostTypes\InstructorPostType;
use CourseDiscovery\PostTypes\LocationTaxonomy;
use CourseDiscovery\PostTypes\ProviderPostType;
use CourseDiscovery\Query\CourseRepository;
use CourseDiscovery\Support\Hooks;
use CourseDiscovery\Sync\LocationSync;
use CourseDiscovery\Sync\StartDateSync;

/**
 * Composition root: wires concrete implementations together and hooks them
 * into WordPress at the right lifecycle points. Nothing here is business
 * logic — it only exists so every other class can depend on abstractions
 * and be unit-tested without WordPress ever booting.
 */
final class Plugin
{
    private readonly FilterRegistry $filterRegistry;
    private readonly CourseRepository $courseRepository;

    public function __construct()
    {
        $this->filterRegistry = new FilterRegistry();
        $this->courseRepository = new CourseRepository($this->filterRegistry);
    }

    public function boot(): void
    {
        register_activation_hook(COURSE_DISCOVERY_FILE, $this->runMigrations(...));

        add_action('plugins_loaded', $this->runMigrations(...), 5);
        add_action('plugins_loaded', $this->registerSyncHooks(...), 10);
        add_action('init', $this->registerPostTypes(...), 10);
        add_action('init', $this->registerFilters(...), 20);
        add_action('acf/init', $this->registerAcfFields(...));

        (new RestController($this->filterRegistry, $this->courseRepository))->register();
        (new CourseDiscoveryShortcode($this->filterRegistry, $this->courseRepository))->register();
        (new CourseListColumns())->register();

        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::add_command('course-discovery migrate', new MigrateCommand($this->migrationRunner()));
            \WP_CLI::add_command('course-discovery seed', new SeedCommand());
        }
    }

    private function registerPostTypes(): void
    {
        (new CoursePostType())->register();
        (new InstructorPostType())->register();
        (new ProviderPostType())->register();
        (new CourseCategoryTaxonomy())->register();
        (new LocationTaxonomy())->register();
    }

    private function registerAcfFields(): void
    {
        (new CourseFields())->register();
        (new ProviderFields())->register();
    }

    /** Populates the FilterRegistry, then lets third parties add their own via Hooks::REGISTER_FILTERS. */
    private function registerFilters(): void
    {
        $this->filterRegistry->register(new SearchFilter());
        $this->filterRegistry->register(new ProviderFilter());
        $this->filterRegistry->register(new LocationFilter());
        $this->filterRegistry->register(new StartDateFilter());
        $this->filterRegistry->register(new CategoryFilter());

        do_action(Hooks::REGISTER_FILTERS, $this->filterRegistry);
    }

    private function registerSyncHooks(): void
    {
        (new LocationSync())->register();
        (new StartDateSync())->register();
    }

    private function runMigrations(): void
    {
        $this->migrationRunner()->run();
    }

    private function migrationRunner(): MigrationRunner
    {
        return new MigrationRunner([
            new Migration_1_0_0_CreateStartDatesTable(),
        ]);
    }
}
