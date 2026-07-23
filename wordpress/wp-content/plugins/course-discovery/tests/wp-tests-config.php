<?php

/**
 * Config for the WordPress core test suite (wp-phpunit/wp-phpunit), used
 * only by the integration/feature test suites (phpunit-wp.xml). Points at a
 * dedicated test database — never the dev database, since the WP test suite
 * truncates tables between tests.
 */

define('DB_NAME', getenv('WP_TESTS_DB_NAME') ?: 'course_discovery_test');
define('DB_USER', getenv('WP_TESTS_DB_USER') ?: 'course_discovery');
define('DB_PASSWORD', getenv('WP_TESTS_DB_PASSWORD') ?: 'beeeb191f88d41d23d6b6d928baa53ab');
define('DB_HOST', getenv('WP_TESTS_DB_HOST') ?: '127.0.0.1');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');

$table_prefix = 'wptests_';

define('WP_TESTS_DOMAIN', 'example.org');
define('WP_TESTS_EMAIL', 'admin@example.org');
define('WP_TESTS_TITLE', 'Course Discovery Test Suite');

define('WP_PHP_BINARY', 'php');
define('WPLANG', '');

define('AUTH_KEY', 'test-auth-key');
define('SECURE_AUTH_KEY', 'test-secure-auth-key');
define('LOGGED_IN_KEY', 'test-logged-in-key');
define('NONCE_KEY', 'test-nonce-key');
define('AUTH_SALT', 'test-auth-salt');
define('SECURE_AUTH_SALT', 'test-secure-auth-salt');
define('LOGGED_IN_SALT', 'test-logged-in-salt');
define('NONCE_SALT', 'test-nonce-salt');
