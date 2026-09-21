<?php

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

ini_set('session.save_path', sys_get_temp_dir());
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../modelos/usuarios.modelo.php';

function comprobarFase4($condicion, $mensaje)
{
    if (!$condicion) { throw new RuntimeException($mensaje); }
    echo "OK: $mensaje\n";
}

$pdo = Conexion::conectar();
comprobarFase4($pdo instanceof PDO, 'La conexión principal está disponible para la prueba');
comprobarFase4((bool) $pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES) === false, 'PDO usa sentencias preparadas nativas');

$rechazoAlta = false;
try {
    ModeloUsuarios::mdlGuardarUsuario('usuarios; DROP TABLE usuarios', []);
} catch (InvalidArgumentException $e) {
    $rechazoAlta = true;
}
comprobarFase4($rechazoAlta, 'El alta de usuarios rechaza nombres de tabla manipulados');

$rechazoEdicion = false;
try {
    ModeloUsuarios::mdlModificarUsuario('usuarios JOIN roles', []);
} catch (InvalidArgumentException $e) {
    $rechazoEdicion = true;
}
comprobarFase4($rechazoEdicion, 'La edición de usuarios rechaza nombres de tabla manipulados');

$conexion = file_get_contents(__DIR__ . '/../modelos/conexion.php');
comprobarFase4(!str_contains($conexion, "echo 'Error de conexion") && !str_contains($conexion, '$e->getMessage()'), 'Los errores de base no se exponen en la respuesta');
comprobarFase4(!str_contains($conexion, 'function consultas('), 'Se eliminó la API que ejecutaba SQL arbitrario sin parámetros');

$cabeceras = file_get_contents(__DIR__ . '/../controladores/seguridad-solicitudes.php');
comprobarFase4(
    str_contains($cabeceras, 'Content-Security-Policy:')
        && str_contains($cabeceras, 'X-Content-Type-Options: nosniff')
        && str_contains($cabeceras, 'Strict-Transport-Security:'),
    'La aplicación define CSP, nosniff y HSTS para HTTPS productivo'
);

$gitignore = file_get_contents(__DIR__ . '/../.gitignore');
comprobarFase4(
    str_contains($gitignore, '/classroom.sql') && str_contains($gitignore, '/classroom al 28-04-24.sql'),
    'Los volcados locales con datos quedan excluidos del control de versiones'
);
