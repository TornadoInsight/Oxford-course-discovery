<?php

/**
 * Plugin Name: Course Discovery
 * Description: Course discovery system — domain model, extensible filter pipeline, REST API and frontend for Oxford International's Course Discovery task.
 * Version: 1.0.0
 * Requires PHP: 8.1
 * Requires Plugins: advanced-custom-fields
 * Author: Oxford International
 * Text Domain: course-discovery
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('COURSE_DISCOVERY_FILE', __FILE__);
define('COURSE_DISCOVERY_VERSION', '1.0.0');

$autoload = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoload)) {
    add_action('admin_notices', static function (): void {
        echo '<div class="notice notice-error"><p>' .
            esc_html__('Course Discovery: run `composer install` inside the plugin directory before activating.', 'course-discovery') .
            '</p></div>';
    });

    return;
}

require_once $autoload;

(new CourseDiscovery\Plugin())->boot();
