<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

ob_start();
require __DIR__ . '/multi_institucion_contexto.php';
require_once __DIR__ . '/../controladores/superadmin.controller.php';

sesionPara($ids['B']);
$denegado = false;
try {
    ModeloInstituciones::mdlListarInstituciones();
} catch (RuntimeException $e) {
    $denegado = true;
}
verificar($denegado, 'El administrador institucional no puede consultar el panel global');

sesionPara($ids['Super']);
verificar(ControladorInstitucion::rutaDestino() === 'superadmin', 'El SuperAdmin sin membresías ingresa al panel global');
$_GET['r'] = 'superadmin';
verificar(ControladorPermisos::puedeAccederRuta('superadmin'), 'La ruta global reconoce únicamente al SuperAdmin');
$datos = ControladorSuperAdmin::crtDatosPanel();
verificar((int) $datos['resumen']['instituciones'] === 2, 'El panel global muestra las dos instituciones iniciales');

$idNueva = ModeloInstituciones::mdlCrearInstitucion([
    'nombre' => 'Academia Norte',
    'slug' => 'academia-norte',
    'logo' => 'img/instituciones/academia-norte.png',
]);
verificar($idNueva > 0, 'El SuperAdmin crea una institución');
ModeloInstituciones::mdlActualizarInstitucion($idNueva, [
    'nombre' => 'Academia Norte Actualizada',
    'slug' => 'academia-norte',
    'logo' => 'img/instituciones/academia-norte.png',
]);
$lista = ModeloInstituciones::mdlListarInstituciones();
$nueva = current(array_filter($lista, static function ($fila) use ($idNueva) {
    return (int) $fila['idInstitucion'] === $idNueva;
}));
verificar($nueva && $nueva['nombre'] === 'Academia Norte Actualizada', 'El SuperAdmin edita la institución');

ModeloInstituciones::mdlAsignarAdministrador($idNueva, 'c@campus.example');
verificar(ModeloInstituciones::mdlRolesUsuarioInstitucion($ids['C'], $idNueva) === ['ADMINISTRADOR'], 'La asignación crea membresía y rol ADMINISTRADOR');

ModeloInstituciones::mdlCambiarEstadoInstitucion($idNueva, false, 'Ensayo de suspensión');
verificar(!in_array($idNueva, array_map('intval', array_column(ModeloInstituciones::mdlMembresiasActivas($ids['C']), 'idInstitucion')), true), 'La suspensión corta el acceso institucional');
ModeloInstituciones::mdlCambiarEstadoInstitucion($idNueva, true);
verificar(in_array($idNueva, array_map('intval', array_column(ModeloInstituciones::mdlMembresiasActivas($ids['C']), 'idInstitucion')), true), 'La reactivación restablece las membresías activas');

$slugDuplicado = false;
try {
    ModeloInstituciones::mdlCrearInstitucion(['nombre' => 'Duplicada', 'slug' => 'academia-norte']);
} catch (InvalidArgumentException $e) {
    $slugDuplicado = true;
}
verificar($slugDuplicado, 'El slug institucional se mantiene único');

$datos = ControladorSuperAdmin::crtDatosPanel();
$nueva = current(array_filter($datos['instituciones'], static function ($fila) use ($idNueva) {
    return (int) $fila['idInstitucion'] === $idNueva;
}));
verificar((int) $datos['resumen']['instituciones'] === 3 && (int) $nueva['totalAdministradores'] === 1, 'Los indicadores globales reflejan instituciones y administradores');

[$servidor, $url, $sesiones] = levantarServidor('LOCAL');
$curl = curl_init();
try {
    $r = peticion($curl, $url . '?r=login', ['login_email' => 'super@campus.example', 'login_pass' => $password]);
    verificar($r['codigo'] === 303 && $r['destino'] === 'index.php?r=superadmin', 'HTTP: el SuperAdmin llega a su panel global');
    $r = peticion($curl, $url . '?r=superadmin');
    verificar($r['codigo'] === 200 && str_contains($r['body'], 'Plataforma MenteMotion') && str_contains($r['body'], 'Academia Norte Actualizada'), 'HTTP: el panel global renderiza sin contexto académico');
    preg_match('/name="superadmin_csrf" value="([^"]+)"/', $r['body'], $csrf);
    verificar(!empty($csrf[1]), 'HTTP: los formularios globales incluyen CSRF');
    $r = peticion($curl, $url . '?r=superadmin', ['accion_superadmin' => 'activar_institucion', 'idInstitucion' => $idNueva, 'superadmin_csrf' => 'falso']);
    verificar($r['codigo'] === 403, 'HTTP: un formulario global con CSRF manipulado es rechazado');
    peticion($curl, $url . '?r=logout');
    $r = peticion($curl, $url . '?r=login', ['login_email' => 'b@campus.example', 'login_pass' => $password]);
    verificar($r['destino'] === 'index.php', 'HTTP: el administrador institucional ingresa sólo a su Campus');
    $r = peticion($curl, $url . '?r=superadmin');
    verificar($r['codigo'] === 403 && !str_contains($r['body'], 'Plataforma MenteMotion'), 'HTTP: el administrador institucional no puede abrir el panel global');
} finally {
    curl_close($curl); proc_terminate($servidor); proc_close($servidor);
}

echo "Panel SuperAdmin y administración global comprobados.\n";
ob_end_flush();
