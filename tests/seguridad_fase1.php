<?php

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

require_once __DIR__ . '/../controladores/seguridad-archivos.php';

function comprobarFase1($condicion, $mensaje)
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
    echo "OK: $mensaje\n";
}

$directorioTemporal = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'campus_seguridad_' . bin2hex(random_bytes(6));
if (!mkdir($directorioTemporal, 0700, true) && !is_dir($directorioTemporal)) {
    throw new RuntimeException('No se pudo preparar la prueba temporal.');
}

try {
    $png = $directorioTemporal . DIRECTORY_SEPARATOR . 'imagen-con-nombre.php';
    file_put_contents($png, base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
        true
    ));
    comprobarFase1(
        SeguridadArchivos::validarImagenTemporal($png, filesize($png)) === 'png',
        'La extensión final se obtiene del contenido y no del nombre recibido'
    );

    $script = $directorioTemporal . DIRECTORY_SEPARATOR . 'archivo.php';
    file_put_contents($script, '<?php echo "no ejecutar";');
    $rechazado = false;
    try {
        SeguridadArchivos::validarImagenTemporal($script, filesize($script));
    } catch (InvalidArgumentException $e) {
        $rechazado = true;
    }
    comprobarFase1($rechazado, 'Un archivo PHP no puede validarse como imagen');
} finally {
    foreach ([$png ?? '', $script ?? ''] as $archivo) {
        if ($archivo !== '' && is_file($archivo)) { unlink($archivo); }
    }
    if (is_dir($directorioTemporal)) { rmdir($directorioTemporal); }
}

// Carga solamente la clase real en un espacio aislado. No conecta DB ni envía correo.
$fuenteAuth = file_get_contents(__DIR__ . '/../controladores/auth.controller.php');
$inicioClase = strpos($fuenteAuth, 'class ControladorAuth');
if ($inicioClase === false) { throw new RuntimeException('No se encontró ControladorAuth.'); }
$fuenteClase = preg_replace('/\?>\s*$/', '', substr($fuenteAuth, $inicioClase));

eval(<<<'PHP'
namespace PruebaSeguridadFase1;
class ModeloUsuarios {
    public static int $actualizaciones = 0;
    public static function mdlActualizarPassword($id, $password) { self::$actualizaciones++; return 'ok'; }
}
class CorreoCampus { public static function remitente() { return 'prueba@example.invalid'; } }
PHP);
eval('namespace PruebaSeguridadFase1; use \\Throwable; ' . $fuenteClase);

$_SESSION = ['forgot_success' => 'anterior', 'forgot_temp_password' => 'anterior'];
$_POST = ['forgot_email' => 'persona@example.invalid'];
$resultado = \PruebaSeguridadFase1\ControladorAuth::crtRecuperarPassword();
comprobarFase1($resultado === false, 'La recuperación automática permanece deshabilitada');
comprobarFase1(\PruebaSeguridadFase1\ModeloUsuarios::$actualizaciones === 0, 'La recuperación no modifica contraseñas');
comprobarFase1(!isset($_SESSION['forgot_temp_password']), 'La recuperación no expone contraseñas temporales');
comprobarFase1(
    str_contains((string) ($_SESSION['forgot_error'] ?? ''), 'temporalmente deshabilitada'),
    'La página informa la contención vigente'
);

foreach (['usuarios', 'secciones', 'instituciones'] as $carpeta) {
    $regla = file_get_contents(__DIR__ . '/../img/' . $carpeta . '/.htaccess');
    comprobarFase1(
        str_contains($regla, 'Require all denied') && str_contains($regla, 'php'),
        'La carpeta img/' . $carpeta . ' bloquea scripts ejecutables'
    );
}

$reglaRaiz = file_get_contents(__DIR__ . '/../.htaccess');
comprobarFase1(
    str_contains($reglaRaiz, 'docs|sql|tests|tmp') && str_contains($reglaRaiz, 'config'),
    'La configuración web bloquea artefactos internos'
);

$despliegue = file_get_contents(__DIR__ . '/../.github/workflows/deploy-hostinger.yml');
comprobarFase1(
    str_contains($despliegue, 'sql/*|tests/*|tmp/*') && str_contains($despliegue, '|*.sql|'),
    'El despliegue excluye pruebas, SQL y archivos temporales'
);
foreach (['img/secciones/.htaccess', 'img/usuarios/.htaccess', 'img/instituciones/.htaccess'] as $reglaImagen) {
    comprobarFase1(
        str_contains($despliegue, $reglaImagen),
        'El despliegue incluye la protección ' . $reglaImagen
    );
}
