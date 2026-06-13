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

define('AUTH_MODE', strtoupper((string) config_env('AUTH_MODE', 'LOCAL')));

define('DB_HOST', (string) config_env('DB_HOST', 'localhost'));
define('DB_PORT', (string) config_env('DB_PORT', '3306'));
define('DB_NAME', (string) config_env('DB_NAME', 'classroom'));
define('DB_USER', (string) config_env('DB_USER', 'root'));
define('DB_PASSWORD', (string) config_env('DB_PASSWORD', ''));

define('WP_DB_HOST', (string) config_env('WP_DB_HOST', 'localhost'));
define('WP_DB_PORT', (string) config_env('WP_DB_PORT', '3306'));
define('WP_DB_NAME', (string) config_env('WP_DB_NAME', ''));
define('WP_DB_USER', (string) config_env('WP_DB_USER', ''));
define('WP_DB_PASSWORD', (string) config_env('WP_DB_PASSWORD', ''));
define('WP_TABLE_PREFIX', (string) config_env('WP_TABLE_PREFIX', 'wp_'));
define('WP_ROOT_PATH', rtrim((string) config_env('WP_ROOT_PATH', ''), '/\\'));

$wpSuperAdminEmailsRaw = (string) config_env('WP_SUPER_ADMIN_EMAILS', 'profejavierpineda@gmail.com');
$wpSuperAdminEmails = array_values(array_filter(array_map('trim', explode(',', $wpSuperAdminEmailsRaw))));
define('WP_SUPER_ADMIN_EMAILS', $wpSuperAdminEmails);
