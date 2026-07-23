<?php
/**
 * Router for `php -S`, used only for local development.
 * WordPress's own wp-load.php already looks one directory above ABSPATH for
 * wp-config.php, so serving with docroot=wordpress/ "just works" against the
 * wp-config.php that lives at the project root.
 */

$wp_dir = __DIR__ . '/wordpress';
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file   = $wp_dir . $path;

// Let the built-in server serve real files (assets, uploads) as-is.
if ($path !== '/' && file_exists($file) && !is_dir($file)) {
    return false;
}

chdir($wp_dir);
require $wp_dir . '/index.php';
