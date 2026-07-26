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

if ($path !== '/') {
    // A real directory (e.g. /wp-admin/) needs its own index.php run explicitly —
    // the built-in server has no directory-index resolution when a router script
    // is in play, so without this, "/wp-admin/" silently fell through to WP's
    // front-end index.php below and rendered the homepage instead of wp-admin.
    if (is_dir($file)) {
        $indexFile = rtrim($file, '/') . '/index.php';
        if (file_exists($indexFile)) {
            chdir(dirname($indexFile));
            require $indexFile;
            return true;
        }
    } elseif (file_exists($file)) {
        // Let the built-in server serve real files (assets, uploads) as-is.
        return false;
    }
}

chdir($wp_dir);
require $wp_dir . '/index.php';
