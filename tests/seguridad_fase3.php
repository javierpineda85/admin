<?php

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

if (!defined('APP_BASE_URL')) { define('APP_BASE_URL', 'https://campus.mentemotion.com'); }
require_once __DIR__ . '/../controladores/seguridad-archivos.php';
require_once __DIR__ . '/../controladores/seguridad-html.php';
require_once __DIR__ . '/../controladores/seguridad-solicitudes.php';

function comprobarFase3($condicion, $mensaje)
{
    if (!$condicion) { throw new RuntimeException($mensaje); }
    echo "OK: $mensaje\n";
}

$archivoPdf = tempnam(sys_get_temp_dir(), 'campus_pdf_');
$archivoFalso = tempnam(sys_get_temp_dir(), 'campus_fake_');
try {
    file_put_contents($archivoPdf, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF\n");
    $pdf = SeguridadArchivos::validarAdjuntoTemporal($archivoPdf, filesize($archivoPdf), 'material.pdf', ['pdf']);
    comprobarFase3($pdf['extension'] === 'pdf' && $pdf['mimeType'] === 'application/pdf', 'Un PDF real supera la validación por contenido');

    file_put_contents($archivoFalso, "<?php echo 'ejecutado';");
    $rechazado = false;
    try {
        SeguridadArchivos::validarAdjuntoTemporal($archivoFalso, filesize($archivoFalso), 'material.pdf', ['pdf']);
    } catch (InvalidArgumentException $e) {
        $rechazado = true;
    }
    comprobarFase3($rechazado, 'Un script renombrado como PDF es rechazado');

    $html = '<p onclick=alert(1)>Texto <strong>válido</strong><script>alert(2)</script>'
        . '<a href="javascript&#58;alert(3)" target="_blank">enlace</a>'
        . '<img src="x" onerror="alert(4)" style="background:url(javascript:alert(5))"></p>';
    $limpio = SeguridadHtml::sanitizarFragmento($html);
    comprobarFase3(str_contains($limpio, '<strong>válido</strong>'), 'El contenido enriquecido permitido se conserva');
    comprobarFase3(!preg_match('/script|onclick|onerror|javascript|style=/i', $limpio), 'El contenido enriquecido elimina scripts, eventos y URLs activas');
    comprobarFase3(SeguridadHtml::urlHttpSegura('https://example.invalid/recurso', false) !== '', 'Los recursos HTTPS válidos se aceptan');
    comprobarFase3(SeguridadHtml::urlHttpSegura('javascript&#58;alert(1)', false) === '', 'Las URLs con esquema activo codificado se rechazan');

    comprobarFase3(SeguridadSolicitudes::origenPostValido([
        'REQUEST_METHOD' => 'POST',
        'HTTP_SEC_FETCH_SITE' => 'same-origin',
        'HTTP_ORIGIN' => 'https://campus.mentemotion.com',
    ]), 'Un POST del origen del Campus se acepta');
    comprobarFase3(!SeguridadSolicitudes::origenPostValido([
        'REQUEST_METHOD' => 'POST',
        'HTTP_SEC_FETCH_SITE' => 'cross-site',
        'HTTP_ORIGIN' => 'https://ataque.example',
    ]), 'Un POST iniciado desde otro sitio se rechaza');
    comprobarFase3(!SeguridadSolicitudes::origenPostValido([
        'REQUEST_METHOD' => 'POST',
        'HTTP_SEC_FETCH_SITE' => 'same-origin',
        'HTTP_ORIGIN' => 'https://otro.example',
    ]), 'Un encabezado Origin que no coincide se rechaza');

    $proteccionLecciones = file_get_contents(__DIR__ . '/../uploads/lecciones/.htaccess');
    $proteccionMensajes = file_get_contents(__DIR__ . '/../uploads/mensajes/.htaccess');
    comprobarFase3(str_contains($proteccionLecciones, 'Require all denied') && str_contains($proteccionMensajes, 'Require all denied'), 'Las carpetas de adjuntos no permiten acceso web directo');
    $mensajes = file_get_contents(__DIR__ . '/../controladores/mensajes.controller.php');
    $lecciones = file_get_contents(__DIR__ . '/../controladores/lecciones.controller.php');
    comprobarFase3(str_contains($mensajes, 'guardarAdjuntoSubido') && str_contains($lecciones, 'guardarAdjuntoSubido'), 'Mensajes y lecciones usan la validación central de adjuntos');
    $config = file_get_contents(__DIR__ . '/../config.php');
    comprobarFase3(str_contains($config, "'samesite' => 'Lax'") && str_contains($config, "'httponly' => true"), 'La cookie de sesión usa SameSite y HttpOnly');
} finally {
    @unlink($archivoPdf);
    @unlink($archivoFalso);
}
