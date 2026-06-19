<?php
/* Esto lo usamos para normalizar rutas y definir configuración base del sistema. */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$folderPath = dirname($_SERVER['SCRIPT_NAME']);
$urlPath = $_SERVER['REQUEST_URI'] ?? '';
$url = substr($urlPath, strlen($folderPath));

define('URL', $url);

date_default_timezone_set('America/Argentina/Mendoza');

if (!function_exists('config_env')) {
    function config_env($key, $default = null)
    {
        $value = getenv($key);
        return $value === false ? $default : $value;
    }
}

$configLocal = __DIR__ . DIRECTORY_SEPARATOR . 'config.local.php';
if (is_file($configLocal)) {
    require_once $configLocal;
}

if (!defined('AUTH_MODE')) {
    define('AUTH_MODE', strtoupper((string) config_env('AUTH_MODE', 'LOCAL')));
}

if (!defined('SESSION_INACTIVITY_TIMEOUT')) {
    define('SESSION_INACTIVITY_TIMEOUT', max(60, (int) config_env('SESSION_INACTIVITY_TIMEOUT', 1800)));
}

if (!defined('DB_HOST')) {
    define('DB_HOST', (string) config_env('DB_HOST', 'localhost'));
}
if (!defined('DB_PORT')) {
    define('DB_PORT', (string) config_env('DB_PORT', '3306'));
}
if (!defined('DB_NAME')) {
    define('DB_NAME', (string) config_env('DB_NAME', 'classroom'));
}
if (!defined('DB_USER')) {
    define('DB_USER', (string) config_env('DB_USER', 'root'));
}
if (!defined('DB_PASSWORD')) {
    define('DB_PASSWORD', (string) config_env('DB_PASSWORD', ''));
}

if (!defined('WP_DB_HOST')) {
    define('WP_DB_HOST', (string) config_env('WP_DB_HOST', 'localhost'));
}
if (!defined('WP_DB_PORT')) {
    define('WP_DB_PORT', (string) config_env('WP_DB_PORT', '3306'));
}
if (!defined('WP_DB_NAME')) {
    define('WP_DB_NAME', (string) config_env('WP_DB_NAME', ''));
}
if (!defined('WP_DB_USER')) {
    define('WP_DB_USER', (string) config_env('WP_DB_USER', ''));
}
if (!defined('WP_DB_PASSWORD')) {
    define('WP_DB_PASSWORD', (string) config_env('WP_DB_PASSWORD', ''));
}
if (!defined('WP_TABLE_PREFIX')) {
    define('WP_TABLE_PREFIX', (string) config_env('WP_TABLE_PREFIX', 'wp_'));
}
if (!defined('WP_ROOT_PATH')) {
    define('WP_ROOT_PATH', rtrim((string) config_env('WP_ROOT_PATH', ''), '/\\'));
}

if (!defined('WP_SUPER_ADMIN_EMAILS')) {
    $wpSuperAdminEmailsRaw = (string) config_env('WP_SUPER_ADMIN_EMAILS', 'profejavierpineda@gmail.com');
    $wpSuperAdminEmails = array_values(array_filter(array_map('trim', explode(',', $wpSuperAdminEmailsRaw))));
    define('WP_SUPER_ADMIN_EMAILS', $wpSuperAdminEmails);
}
