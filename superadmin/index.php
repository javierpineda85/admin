<?php
// Compatibilidad con servidores PHP sin mod_rewrite (por ejemplo, el servidor
// de pruebas integrado). En Apache la regla raíz también resuelve esta URL.
if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'GET'
    && isset($_GET['r'])
    && (string) $_GET['r'] === 'superadmin'
    && preg_match('~/superadmin/?$~i', (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH))) {
    header('Location: ' . rtrim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '/'), true, 301);
    exit;
}
$_GET['r'] = 'superadmin';
chdir(dirname(__DIR__));
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'index.php';
