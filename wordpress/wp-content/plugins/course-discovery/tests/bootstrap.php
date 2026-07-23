<?php

/**
 * Bootstrap for the integration/feature suites (phpunit-wp.xml). Loads the
 * real WordPress core test library (wp-phpunit/wp-phpunit) against the
 * dedicated course_discovery_test database, then activates this plugin
 * (and ACF) before any test runs.
 *
 * Unit tests (phpunit.xml) do NOT use this file — they run against plain
 * autoloaded classes with zero WordPress involved, which is what keeps them
 * fast and independent of a database.
 */

declare(strict_types=1);

putenv('WP_PHPUNIT__TESTS_CONFIG=' . __DIR__ . '/wp-tests-config.php');

$wpPhpunitDir = getenv('WP_PHPUNIT__DIR') ?: dirname(__DIR__) . '/vendor/wp-phpunit/wp-phpunit';

require_once $wpPhpunitDir . '/includes/functions.php';

/** Loads ACF and this plugin as if they were network-activated, before WP core finishes bootstrapping. */
function course_discovery_tests_manually_load_plugin(): void
{
    $wpContentDir = dirname(dirname(dirname(__DIR__)));

    require $wpContentDir . '/plugins/advanced-custom-fields/acf.php';
    require dirname(__DIR__) . '/course-discovery.php';
}
tests_add_filter('muplugins_loaded', 'course_discovery_tests_manually_load_plugin');

require $wpPhpunitDir . '/includes/bootstrap.php';

require_once dirname(__DIR__) . '/vendor/autoload.php';
