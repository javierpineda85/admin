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
verificar(count($datos['membresias']) >= 4 && array_column($datos['roles'], 'codigo') === ['ADMINISTRADOR', 'DOCENTE', 'ESTUDIANTE'], 'El panel expone membresías y roles institucionales');

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
$membresiaC = current(array_filter(ModeloInstituciones::mdlListarMembresias(), static function ($fila) use ($ids, $idNueva) {
    return (int)$fila['id_usuario'] === (int)$ids['C'] && (int)$fila['id_institucion'] === $idNueva;
}));
ModeloInstituciones::mdlActualizarRolesMembresia((int)$membresiaC['idUsuarioInstitucion'], ['ADMINISTRADOR', 'DOCENTE']);
verificar(ModeloInstituciones::mdlRolesUsuarioInstitucion($ids['C'], $idNueva) === ['ADMINISTRADOR', 'DOCENTE'], 'Una membresía admite varios roles institucionales');
ModeloInstituciones::mdlCambiarEstadoMembresia((int)$membresiaC['idUsuarioInstitucion'], false, 'Prueba de gestión');
verificar(ModeloInstituciones::mdlRolesUsuarioInstitucion($ids['C'], $idNueva) === [], 'Suspender una membresía corta el acceso sin borrarla');
ModeloInstituciones::mdlCambiarEstadoMembresia((int)$membresiaC['idUsuarioInstitucion'], true);
verificar(ModeloInstituciones::mdlRolesUsuarioInstitucion($ids['C'], $idNueva) === ['ADMINISTRADOR', 'DOCENTE'], 'Reactivar una membresía conserva sus roles');

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
$panelUrl = preg_replace('~/index\\.php$~', '/superadmin/', $url);
$curl = curl_init();
try {
    $r = peticion($curl, $url . '?r=login', ['login_email' => 'super@campus.example', 'login_pass' => $password]);
    verificar($r['codigo'] === 303 && $r['destino'] === 'superadmin', 'HTTP: el SuperAdmin llega a la URL limpia de su panel global');
    $r = peticion($curl, $url . '?r=superadmin');
    verificar($r['codigo'] === 301 && str_ends_with($r['destino'], '/superadmin'), 'HTTP: el enlace legacy del panel se canoniza');
    $r = peticion($curl, $panelUrl);
    verificar($r['codigo'] === 200 && str_contains($r['body'], 'Plataforma MenteMotion') && str_contains($r['body'], 'Academia Norte Actualizada'), 'HTTP: el panel global renderiza sin contexto académico');
    verificar(str_contains($r['body'], 'Membresías institucionales') && str_contains($r['body'], 'multipart/form-data') && str_contains($r['body'], 'name="logoArchivo"'), 'HTTP: el panel permite gestionar membresías y cargar logos');
    verificar(substr_count($r['body'], 'action="index.php?r=superadmin"') >= 5,
        'HTTP: los formularios de la URL limpia publican al front controller principal');
    preg_match('/name="superadmin_csrf" value="([^"]+)"/', $r['body'], $csrf);
    verificar(!empty($csrf[1]), 'HTTP: los formularios globales incluyen CSRF');
    verificar(str_contains($r['body'], 'id="modalAgregarMembresias"') && str_contains($r['body'], 'name="usuarios[]"')
        && str_contains($r['body'], 'Institución de origen'), 'HTTP: alta múltiple con casillas y filtro de institución');
    $r = peticion($curl, $url . '?r=superadmin', ['accion_superadmin'=>'agregar_membresias',
        'idInstitucion'=>$idNueva, 'usuarios'=>[$ids['A'],$ids['D']], 'roles'=>['ESTUDIANTE'], 'superadmin_csrf'=>'falso']);
    verificar($r['codigo'] === 403 && ModeloInstituciones::mdlRolesUsuarioInstitucion($ids['D'],$idNueva) === [], 'HTTP: CSRF inválido impide el alta múltiple');
    $r = peticion($curl, $panelUrl);
    preg_match('/name="superadmin_csrf" value="([^"]+)"/', $r['body'], $csrf);
    $r = peticion($curl, $url . '?r=superadmin', ['accion_superadmin'=>'agregar_membresias',
        'idInstitucion'=>$idNueva, 'usuarios'=>[$ids['A'],$ids['D']], 'roles'=>['ESTUDIANTE'], 'superadmin_csrf'=>$csrf[1]]);
    verificar($r['codigo'] === 303 && ModeloInstituciones::mdlRolesUsuarioInstitucion($ids['A'],$idNueva) === ['ESTUDIANTE']
        && ModeloInstituciones::mdlRolesUsuarioInstitucion($ids['D'],$idNueva) === ['ESTUDIANTE'], 'HTTP: agrega dos cuentas por ID con sus roles');
    $r = peticion($curl, $url . '?r=superadmin', ['accion_superadmin' => 'activar_institucion', 'idInstitucion' => $idNueva, 'superadmin_csrf' => 'falso']);
    verificar($r['codigo'] === 403, 'HTTP: un formulario global con CSRF manipulado es rechazado');
    peticion($curl, $url . '?r=logout');
    $r = peticion($curl, $url . '?r=login', ['login_email' => 'b@campus.example', 'login_pass' => $password]);
    verificar($r['destino'] === 'index.php', 'HTTP: el administrador institucional ingresa sólo a su Campus');
    $r = peticion($curl, $panelUrl);
    verificar($r['codigo'] === 403 && !str_contains($r['body'], 'Plataforma MenteMotion'), 'HTTP: el administrador institucional no puede abrir el panel global');
    $r = peticion($curl, $url . '?r=superadmin', ['accion_superadmin'=>'agregar_membresias',
        'idInstitucion'=>$idNueva, 'usuarios'=>[$ids['B']], 'roles'=>['ADMINISTRADOR'], 'superadmin_csrf'=>'falso']);
    verificar($r['codigo'] === 403 && ModeloInstituciones::mdlRolesUsuarioInstitucion($ids['B'],$idNueva) === [], 'HTTP: administrador institucional no puede asignarse acceso global por POST');
} finally {
    curl_close($curl); proc_terminate($servidor); proc_close($servidor);
}

sesionPara($ids['Super']);
$origenAntes = $pdo->query('SELECT * FROM usuarios_instituciones WHERE id_institucion='.(int)$mm.' ORDER BY idUsuarioInstitucion')->fetchAll(PDO::FETCH_ASSOC);
$resultado = ModeloInstituciones::mdlAgregarMembresias($idNueva, [$ids['A'],$ids['A'],$ids['C']], ['ESTUDIANTE']);
verificar($resultado === ['agregadas'=>0,'existentes'=>2], 'Repetir una selección no duplica membresías');
verificar(ModeloInstituciones::mdlRolesUsuarioInstitucion($ids['C'],$idNueva) === ['ADMINISTRADOR','DOCENTE'], 'Alta múltiple conserva roles de miembros existentes');
ModeloInstituciones::mdlCambiarEstadoMembresia((int)$membresiaC['idUsuarioInstitucion'], false, 'Conservar suspensión');
ModeloInstituciones::mdlAgregarMembresias($idNueva, [$ids['C']], ['ESTUDIANTE']);
verificar(ModeloInstituciones::mdlRolesUsuarioInstitucion($ids['C'],$idNueva) === []
    && $pdo->query('SELECT motivoBaja FROM usuarios_instituciones WHERE idUsuarioInstitucion='.(int)$membresiaC['idUsuarioInstitucion'])->fetchColumn() === 'Conservar suspensión', 'Alta múltiple no reactiva membresías suspendidas');
ModeloInstituciones::mdlCambiarEstadoMembresia((int)$membresiaC['idUsuarioInstitucion'], true);
verificar($origenAntes === $pdo->query('SELECT * FROM usuarios_instituciones WHERE id_institucion='.(int)$mm.' ORDER BY idUsuarioInstitucion')->fetchAll(PDO::FETCH_ASSOC), 'Las membresías de origen permanecen intactas');

foreach ([[], [$ids['SinRol'],2147483647], ['2abc'], array_fill(0,201,$ids['SinRol'])] as $seleccionInvalida) {
    $rechazado = false;
    try { ModeloInstituciones::mdlAgregarMembresias($idNueva,$seleccionInvalida,['ESTUDIANTE']); }
    catch (InvalidArgumentException $e) { $rechazado = true; }
    verificar($rechazado && ModeloInstituciones::mdlRolesUsuarioInstitucion($ids['SinRol'],$idNueva) === [], 'Selección inválida rechazada sin altas parciales');
}
$pdo->prepare('UPDATE usuarios SET activo=0 WHERE idUsuario=?')->execute([$ids['SinRol']]);
$rechazado = false;
try { ModeloInstituciones::mdlAgregarMembresias($idNueva,[$ids['SinRol']],['ESTUDIANTE']); }
catch (InvalidArgumentException $e) { $rechazado = true; }
verificar($rechazado && !in_array($ids['SinRol'],array_map('intval',array_column(ModeloInstituciones::mdlUsuariosParaMembresias(),'idUsuario')),true), 'Cuenta inactiva no se ofrece ni puede agregarse por POST');
$pdo->prepare('UPDATE usuarios SET activo=1 WHERE idUsuario=?')->execute([$ids['SinRol']]);
ModeloInstituciones::mdlCambiarEstadoInstitucion($idNueva,false,'Prueba de alta múltiple');
$rechazado = false;
try { ModeloInstituciones::mdlAgregarMembresias($idNueva,[$ids['SinRol']],['ESTUDIANTE']); }
catch (InvalidArgumentException $e) { $rechazado = true; }
verificar($rechazado, 'Institución suspendida rechaza altas múltiples');
ModeloInstituciones::mdlCambiarEstadoInstitucion($idNueva,true);
$rechazado = false;
try { ModeloInstituciones::mdlAgregarMembresias($idNueva,[$ids['SinRol']],[]); }
catch (InvalidArgumentException $e) { $rechazado = true; }
verificar($rechazado, 'Alta múltiple exige al menos un rol');
sesionPara($ids['B']);
$rechazado = false;
try { ModeloInstituciones::mdlAgregarMembresias($idNueva,[$ids['B']],['ADMINISTRADOR']); }
catch (RuntimeException $e) { $rechazado = true; }
verificar($rechazado, 'El modelo también exige SuperAdmin para el alta múltiple');

echo "Panel SuperAdmin y administración global comprobados.\n";
ob_end_flush();
