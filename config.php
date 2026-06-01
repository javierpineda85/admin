<?php
/* Esto lo usamos para normalizar rutas y definir configuración base del sistema. */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$folderPath = dirname($_SERVER['SCRIPT_NAME']);
$urlPath = $_SERVER['REQUEST_URI'];
$url = substr($urlPath, strlen($folderPath));

define('URL', $url);

date_default_timezone_set('America/Argentina/Mendoza');
