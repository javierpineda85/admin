<?php
require_once("config.php");
require_once("controladores/seguridad-solicitudes.php");
require_once("controladores/institucion.controller.php");
require_once("controladores/superadmin.controller.php");
require_once("controladores/auth.controller.php");
require_once("controladores/permisos.controller.php");
require_once("controladores/rutas.controller.php");
require_once("controladores/plantilla.controller.php");
require_once("controladores/usuarios.controller.php");
require_once("controladores/cursos.controller.php");
require_once("controladores/materias.controller.php");
require_once("controladores/lecciones.controller.php");
require_once("controladores/calificaciones.controller.php");
require_once("controladores/asistencias.controller.php");
require_once("controladores/actividades.controller.php");
require_once("controladores/notificaciones.controller.php");
require_once("controladores/panel.controller.php");
require_once("controladores/perfiles.controller.php");
require_once("controladores/mensajes.controller.php");
require_once("controladores/descargas.controller.php");

if (!SeguridadSolicitudes::origenPostValido()) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Solicitud rechazada por validación de origen.');
}

// URL canónica del panel global: conservar compatibilidad con enlaces antiguos
// sin dejar expuesto el parámetro interno de dispatch en el navegador.
$rutaSolicitada = trim((string) ($_GET['r'] ?? ''));
$pathSolicitado = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'GET'
    && $rutaSolicitada === 'superadmin'
    && preg_match('~/index\\.php$~i', $pathSolicitado)) {
    $baseAplicacion = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'))), '/.');
    if (preg_match('~/superadmin$~i', $baseAplicacion)) {
        $baseAplicacion = preg_replace('~/superadmin$~i', '', $baseAplicacion);
    }
    header('Location: ' . ($baseAplicacion === '' ? '' : $baseAplicacion) . '/superadmin', true, 301);
    exit;
}



if (!ControladorAuth::validarSesionActual()) {
    header('Location: index.php?r=login', true, 303);
    exit;
}
ControladorInstitucion::procesarAntesDeRenderizar();
ControladorDescargas::crtProcesar();

$plantilla= new PlantillaController();
 // ejecutar metodo
$plantilla->crtGetPlantilla();


?>
