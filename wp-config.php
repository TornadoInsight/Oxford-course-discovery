<?php
/**
 * Bedrock-style wp-config: WordPress core lives in ./wordpress (composer-managed,
 * gitignored); this file and .env stay at the repo root and are version controlled
 * (this file) / kept local (.env).
 */

$root_dir = __DIR__;
$wp_dir   = $root_dir . '/wordpress';

// --- Minimal .env loader (no external dependency) ---------------------------
$env_file = $root_dir . '/.env';
if (file_exists($env_file)) {
    foreach (file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);
        if (!getenv($key)) {
            putenv("{$key}={$value}");
            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
        }
    }
}

function env(string $key, mixed $default = null): mixed
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

// --- Database -----------------------------------------------------------------
define('DB_NAME', env('DB_NAME', 'course_discovery'));
define('DB_USER', env('DB_USER', 'course_discovery'));
define('DB_PASSWORD', env('DB_PASSWORD', ''));
define('DB_HOST', env('DB_HOST', '127.0.0.1') . (env('DB_PORT') ? ':' . env('DB_PORT') : ''));
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');

// --- Auth keys/salts ------------------------------------------------------------
define('AUTH_KEY', env('AUTH_KEY', 'put-your-unique-phrase-here'));
define('SECURE_AUTH_KEY', env('SECURE_AUTH_KEY', 'put-your-unique-phrase-here'));
define('LOGGED_IN_KEY', env('LOGGED_IN_KEY', 'put-your-unique-phrase-here'));
define('NONCE_KEY', env('NONCE_KEY', 'put-your-unique-phrase-here'));
define('AUTH_SALT', env('AUTH_SALT', 'put-your-unique-phrase-here'));
define('SECURE_AUTH_SALT', env('SECURE_AUTH_SALT', 'put-your-unique-phrase-here'));
define('LOGGED_IN_SALT', env('LOGGED_IN_SALT', 'put-your-unique-phrase-here'));
define('NONCE_SALT', env('NONCE_SALT', 'put-your-unique-phrase-here'));

$table_prefix = 'wp_';

// --- Environment ----------------------------------------------------------------
define('WP_ENV', env('WP_ENV', 'production'));
define('WP_DEBUG', filter_var(env('WP_DEBUG', false), FILTER_VALIDATE_BOOLEAN));
define('WP_DEBUG_LOG', WP_DEBUG);
define('WP_DEBUG_DISPLAY', WP_DEBUG);
define('SCRIPT_DEBUG', WP_DEBUG);

if (env('WP_HOME')) {
    define('WP_HOME', env('WP_HOME'));
}
if (env('WP_SITEURL')) {
    define('WP_SITEURL', env('WP_SITEURL'));
}

// Content directory lives inside wordpress/, but is addressed relative to WP_HOME
define('WP_CONTENT_DIR', $wp_dir . '/wp-content');
define('WP_CONTENT_URL', env('WP_HOME', '') . '/wp-content');

define('DISALLOW_FILE_EDIT', true);

if (!defined('ABSPATH')) {
    define('ABSPATH', $wp_dir . '/');
}

require_once ABSPATH . 'wp-settings.php';
