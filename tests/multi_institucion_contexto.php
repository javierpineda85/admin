<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
ob_start(); // Permite comprobar rotación real de sesión después de imprimir resultados.
define('INSTITUCIONES_CONTEXTO_ACTIVO', true);
define('DB_NAME', 'campus_mt_fase2_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)));
define('WP_DB_NAME', DB_NAME);
define('WP_DB_USER', getenv('DB_USER') ?: 'root');
define('WP_TABLE_PREFIX', 'prueba_wp_');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/soporte/sql.php';
require_once __DIR__ . '/../controladores/institucion.controller.php';
require_once __DIR__ . '/../controladores/auth.controller.php';
require_once __DIR__ . '/../controladores/permisos.controller.php';

function verificar($valor, $mensaje) {
    if (!$valor) { throw new RuntimeException($mensaje); }
    echo "OK: $mensaje\n";
}
$origen = getenv('CAMPUS_TEST_ORIGEN') ?: 'classroom';
if (!preg_match('/^[a-zA-Z0-9_]+$/', $origen)) { throw new RuntimeException('Origen inválido'); }
$admin = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4', DB_USER, DB_PASSWORD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$base = DB_NAME;
$admin->exec("CREATE DATABASE `$base` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo = Conexion::conectar();
// Solo esquema y fixtures sintéticos; no copiar datos personales de la instalación.
foreach (['usuarios', 'perfiles', 'cursos'] as $tabla) {
    $pdo->exec("CREATE TABLE `$tabla` LIKE `$origen`.`$tabla`");
}
ejecutarMigracion($pdo, __DIR__ . '/../sql/2026-09-14_multi_institucion_01_expandir.sql');
ModeloInstituciones::mdlVerificarEsquema();
ModeloUsuarios::mdlAsegurarColumnasIntegracionWordPress();
$mm = (int) $pdo->query("SELECT idInstitucion FROM instituciones WHERE slug='mentemotion'")->fetchColumn();
$pdo->exec("INSERT INTO instituciones(nombre,slug) VALUES ('Instituto Demo','instituto-demo')");
$demo = (int) $pdo->lastInsertId();
$ids = [];
$password = 'Ensayo-' . bin2hex(random_bytes(12));
foreach (['A','B','C','D','Super','SinRol'] as $letra) {
    $pdo->prepare("INSERT INTO usuarios(nombreUsuario,apellidoUsuario,email,pass,resetPass,imgUsuario,activo,rol) VALUES (?,'Ensayo',?,?,0,'',1,'ADMINISTRADOR')")
        ->execute([$letra, strtolower($letra) . '@campus.example', password_hash($password, PASSWORD_DEFAULT)]);
    $ids[$letra] = (int) $pdo->lastInsertId();
}
foreach ([['A',$mm,['DOCENTE']],['A',$demo,['ESTUDIANTE']],['B',$demo,['ADMINISTRADOR','DOCENTE']],['C',$mm,['ESTUDIANTE']],['SinRol',$mm,[]]] as [$letra,$institucion,$roles]) {
    $pdo->prepare('INSERT INTO usuarios_instituciones(id_usuario,id_institucion) VALUES (?,?)')->execute([$ids[$letra],$institucion]);
    $miembro = (int) $pdo->lastInsertId();
    foreach ($roles as $rol) {
        $pdo->prepare('INSERT INTO usuarios_instituciones_roles(id_usuario_institucion,id_rol) SELECT ?,idRol FROM roles WHERE codigo=?')->execute([$miembro,$rol]);
    }
}
$pdo->prepare('UPDATE usuarios SET esSuperAdmin=1 WHERE idUsuario=?')->execute([$ids['Super']]);
function sesionPara($id) {
    ControladorInstitucion::limpiar();
    $_SESSION = ['logueado'=>true, 'ultima_actividad'=>time(), 'usuario'=>['id'=>$id,'rol'=>'ADMINISTRADOR']];
    return ControladorInstitucion::refrescar();
}
verificar(sesionPara($ids['A']), 'Identidad global A válida');
verificar(ControladorInstitucion::id() === 0 && ControladorInstitucion::rutaDestino()==='seleccionar-institucion', 'A debe elegir entre dos instituciones');
verificar(ControladorInstitucion::seleccionar($mm, ControladorInstitucion::csrf(), ControladorInstitucion::version()), 'A selecciona MenteMotion');
verificar(ControladorInstitucion::roles()===['DOCENTE'], 'Roles obtenidos de membresía, no de usuarios.rol');
verificar(ControladorPermisos::rolesReales()===['DOCENTE'] && ControladorPermisos::rolReal()==='DOCENTE', 'Permisos leen el rol de la membresía actual');
verificar(ControladorPermisos::puedeAccederRuta('crear-actividad') && !ControladorPermisos::puedeAccederRuta('crear-usuario'), 'Rutas de docente no heredan permisos administrativos legacy');
$csrfViejo=ControladorInstitucion::csrf(); $versionVieja=ControladorInstitucion::version(); $sesionVieja=session_id();
$_SESSION['vista_estudiante']=true; $_SESSION['vista_estudiante_id']=$ids['C'];
verificar(ControladorInstitucion::seleccionar($demo,$csrfViejo,$versionVieja),'A cambia a Demo');
verificar(ControladorInstitucion::roles()===['ESTUDIANTE'] && $_SESSION['institucion_slug']==='instituto-demo', 'El cambio recarga roles y slug');
verificar(session_id()!==$sesionVieja && !isset($_SESSION['vista_estudiante_id'],$_SESSION['usuario']['rol']), 'Rotación de sesión y limpieza de permisos anteriores');
verificar(!ControladorInstitucion::seleccionar($mm,$csrfViejo,$versionVieja), 'Formulario de pestaña antigua rechazado');
verificar(!ControladorInstitucion::seleccionar($mm,'csrf-falso',ControladorInstitucion::version()), 'CSRF inválido rechazado');
sesionPara($ids['B']);
verificar(ControladorInstitucion::id()===$demo && ControladorInstitucion::roles()===['ADMINISTRADOR','DOCENTE'], 'B obtiene automáticamente Demo con ambos roles');
verificar(!ControladorInstitucion::esSuperAdmin(), 'Administrador institucional no es SuperAdmin');
verificar(ControladorPermisos::rolesReales()===['ADMINISTRADOR','DOCENTE'], 'El sistema conserva múltiples roles institucionales simultáneos');
verificar(ControladorPermisos::tieneRol('ADMINISTRADOR') && ControladorPermisos::tieneRol('DOCENTE'), 'La membresía B satisface ambos roles');
verificar(ControladorPermisos::rolReal()==='ADMINISTRADOR' && ControladorPermisos::puedeAccederRuta('crear-usuario'), 'El rol principal es determinista y la autorización usa el conjunto');
verificar(ControladorPermisos::usuarioTieneRolEnInstitucion($ids['A'], ['ESTUDIANTE'], $demo), 'La validación de asignaciones acepta el rol en la institución indicada');
verificar(!ControladorPermisos::usuarioTieneRolEnInstitucion($ids['C'], ['ESTUDIANTE'], $demo), 'La validación de asignaciones rechaza membresías de otra institución');
sesionPara($ids['C']);
verificar(ControladorInstitucion::id()===$mm, 'C selecciona automáticamente su única institución');
verificar(!ControladorInstitucion::seleccionar($demo,ControladorInstitucion::csrf(),ControladorInstitucion::version()), 'C no puede seleccionar Demo mediante ID manipulado');
$_SESSION['institucion_id']=$demo; $_SESSION['institucion_roles']=['ADMINISTRADOR'];
ControladorInstitucion::refrescar();
verificar(ControladorInstitucion::id()===$mm && ControladorInstitucion::roles()===['ESTUDIANTE'],'La sesión falsificada se reemplaza por la membresía real');
sesionPara($ids['D']);
verificar(ControladorInstitucion::rutaDestino()==='sin-acceso-institucional' && ControladorInstitucion::roles()===[], 'D sin membresías conserva identidad sin acceso institucional');
sesionPara($ids['SinRol']);
verificar(ControladorInstitucion::id()===$mm && ControladorInstitucion::roles()===[], 'Membresía sin roles no hereda administrador legacy');
sesionPara($ids['Super']);
verificar(ControladorInstitucion::esSuperAdmin() && ControladorInstitucion::id()===0, 'SuperAdmin no recibe una membresía artificial');
sesionPara($ids['A']);
ControladorInstitucion::seleccionar($demo,ControladorInstitucion::csrf(),ControladorInstitucion::version());
$csrfViejo=ControladorInstitucion::csrf(); $versionVieja=ControladorInstitucion::version();
$pdo->prepare('UPDATE usuarios_instituciones SET activo=0 WHERE id_usuario=? AND id_institucion=?')->execute([$ids['A'],$demo]);
verificar(!ControladorInstitucion::seleccionar($mm,$csrfViejo,$versionVieja), 'Revocación con sesión abierta invalida el formulario');
ControladorInstitucion::refrescar();
verificar(ControladorInstitucion::id()===$mm, 'Tras la baja solo queda disponible MenteMotion');
$pdo->prepare('UPDATE instituciones SET activo=0 WHERE idInstitucion=?')->execute([$mm]);
ControladorInstitucion::refrescar();
verificar(ControladorInstitucion::id()===0 && ControladorInstitucion::roles()===[], 'Suspensión institucional elimina el contexto');
$pdo->prepare('UPDATE instituciones SET activo=1 WHERE idInstitucion=?')->execute([$mm]);
$pdo->prepare('UPDATE usuarios SET activo=0 WHERE idUsuario=?')->execute([$ids['A']]);
verificar(!ControladorInstitucion::refrescar(), 'Cuenta global desactivada pierde acceso');
$pdo->prepare('UPDATE usuarios SET activo=1 WHERE idUsuario=?')->execute([$ids['A']]);
$pdo->prepare('UPDATE usuarios_instituciones SET activo=1 WHERE id_usuario=?')->execute([$ids['A']]);

// WP auténtica mediante tablas de prueba; no se contacta WordPress de producción.
$pdo->exec('CREATE TABLE prueba_wp_users (ID BIGINT PRIMARY KEY,user_login VARCHAR(60),user_pass VARCHAR(255),user_email VARCHAR(100),user_status INT,display_name VARCHAR(100))');
$pdo->exec('CREATE TABLE prueba_wp_usermeta (umeta_id BIGINT AUTO_INCREMENT PRIMARY KEY,user_id BIGINT,meta_key VARCHAR(255),meta_value LONGTEXT)');
$pdo->prepare('INSERT INTO prueba_wp_users VALUES (101,?,?,?,?,?)')->execute(['a',password_hash($password,PASSWORD_DEFAULT),'a@campus.example',0,'A Ensayo']);
$pdo->prepare('INSERT INTO prueba_wp_users VALUES (102,?,?,?,?,?)')->execute(['nuevo',password_hash($password,PASSWORD_DEFAULT),'nuevo@campus.example',0,'Nuevo WP']);
$pdo->prepare('INSERT INTO prueba_wp_usermeta(user_id,meta_key,meta_value) VALUES (101,?,?)')->execute(['prueba_wp_capabilities','administrator']);
$autenticarWp = new ReflectionMethod(ControladorAuth::class,'autenticarWordPress');
$error=null; $args=['a@campus.example',$password,&$error];
$usuarioWp=$autenticarWp->invokeArgs(null,$args);
verificar((int)$usuarioWp['idUsuario']===$ids['A'], 'WordPress reutiliza la misma identidad por email');
verificar($pdo->query("SELECT COUNT(*) FROM usuarios WHERE email='a@campus.example'")->fetchColumn()==1,'WordPress no duplica A');
sesionPara($ids['A']);
ControladorInstitucion::seleccionar($mm,ControladorInstitucion::csrf(),ControladorInstitucion::version());
verificar(ControladorInstitucion::roles()===['DOCENTE'] && !ControladorInstitucion::esSuperAdmin(), 'administrator en WordPress no cambia permisos institucionales');
$args=['nuevo@campus.example',$password,&$error];
$nuevo=$autenticarWp->invokeArgs(null,$args);
verificar($nuevo && $nuevo['rol']==='', 'WordPress sin metadatos académicos crea solo identidad');
verificar(ModeloInstituciones::mdlMembresiasActivas($nuevo['idUsuario'])===[], 'Identidad nueva de WP no obtiene membresías automáticas');
$args=['a@campus.example','incorrecta',&$error];
verificar(!$autenticarWp->invokeArgs(null,$args), 'WordPress rechaza contraseña incorrecta');

$wp = ModeloUsuarios::mdlObtenerUsuarioWordPressPorEmail('a@campus.example');
$wp['user_email']='c@campus.example';
verificar(!ModeloUsuarios::mdlSincronizarUsuarioWordPress($wp),'Conflicto entre wpUserId y email de otra cuenta rechazado');
$wp['user_email']='a@campus.example'; $wp['user_status']=1;
verificar(!ModeloUsuarios::mdlSincronizarUsuarioWordPress($wp),'WordPress inactivo no autentica identidad');
$wp['user_status']=0;
$pdo->prepare('UPDATE usuarios SET activo=0 WHERE idUsuario=?')->execute([$ids['A']]);
verificar(!ModeloUsuarios::mdlSincronizarUsuarioWordPress($wp),'WordPress no reactiva una cuenta local dada de baja');
$pdo->prepare('UPDATE usuarios SET activo=1 WHERE idUsuario=?')->execute([$ids['A']]);
$wp['ID']=103; $wp['user_email']=WP_SUPER_ADMIN_EMAILS[0] ?? 'privilegiado@campus.example';
$privilegiado=ModeloUsuarios::mdlSincronizarUsuarioWordPress($wp);
verificar($privilegiado && (int)$privilegiado['esSuperAdmin']===0 && $privilegiado['rol']==='', 'Lista legacy de emails WP no concede privilegios');

// Se continúa en el mismo ensayo con pruebas HTTP de las rutas reales.
require __DIR__ . '/soporte/instituciones_http.php';
echo "Base sintética conservada: $base\n";
echo "Fases 2 y 3: contexto, autenticación y permisos institucionales comprobados; aislamiento académico pendiente.\n";
ob_end_flush();
