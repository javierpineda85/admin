<?php

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
ob_start();

$basePrueba = 'campus_seguridad_auth_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3));
if (!preg_match('/^[a-zA-Z0-9_]+$/D', $basePrueba)) { throw new RuntimeException('Nombre de base inválido.'); }
ini_set('session.save_path', sys_get_temp_dir());
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../modelos/usuarios.modelo.php';
require_once __DIR__ . '/../modelos/seguridad-auth.modelo.php';
require_once __DIR__ . '/../modelos/correo.php';
require_once __DIR__ . '/../controladores/institucion.controller.php';
require_once __DIR__ . '/../controladores/auth.controller.php';

function comprobarAuth($condicion, $mensaje)
{
    if (!$condicion) { throw new RuntimeException($mensaje); }
    echo "OK: $mensaje\n";
}

$admin = new PDO(
    'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4',
    DB_USER,
    DB_PASSWORD,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$admin->exec("CREATE DATABASE `$basePrueba` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

try {
    $pdo = Conexion::conectar();
    $pdo->exec("USE `$basePrueba`");
    $pdo->exec("CREATE TABLE usuarios (
        idUsuario INT NOT NULL AUTO_INCREMENT,
        nombreUsuario VARCHAR(50) NOT NULL,
        apellidoUsuario VARCHAR(50) NOT NULL,
        email VARCHAR(254) NOT NULL,
        pass VARCHAR(255) NOT NULL,
        resetPass TINYINT NOT NULL DEFAULT 0,
        imgUsuario VARCHAR(255) NOT NULL DEFAULT '',
        activo TINYINT NOT NULL DEFAULT 1,
        rol VARCHAR(30) NOT NULL DEFAULT 'ESTUDIANTE',
        origenAuth VARCHAR(20) NOT NULL DEFAULT 'LOCAL',
        ultimaConexion DATETIME NULL,
        PRIMARY KEY (idUsuario),
        UNIQUE KEY uq_usuario_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $claveOriginal = 'Clave-Segura-2026';
    $pdo->prepare("INSERT INTO usuarios(nombreUsuario,apellidoUsuario,email,pass) VALUES ('Persona','Prueba','persona@example.invalid',?)")
        ->execute([password_hash($claveOriginal, PASSWORD_DEFAULT)]);
    $idUsuario = (int) $pdo->lastInsertId();

    $token1 = ModeloSeguridadAuth::crearRecuperacion($idUsuario, '127.0.0.1', 3600);
    comprobarAuth(preg_match('/^[a-f0-9]{64}$/D', $token1) === 1, 'El token usa 256 bits aleatorios representados en hexadecimal');
    $guardado = $pdo->query('SELECT tokenHash FROM auth_recuperaciones ORDER BY idRecuperacion DESC LIMIT 1')->fetchColumn();
    comprobarAuth($guardado === hash('sha256', $token1) && $guardado !== $token1, 'La base guarda solamente el hash del token');
    comprobarAuth(ModeloSeguridadAuth::buscarRecuperacion($token1) !== null, 'El token nuevo es válido antes de usarse');

    $token2 = ModeloSeguridadAuth::crearRecuperacion($idUsuario, '127.0.0.1', 3600);
    comprobarAuth(ModeloSeguridadAuth::buscarRecuperacion($token1) === null, 'Solicitar otro enlace invalida el anterior');
    comprobarAuth(ModeloSeguridadAuth::buscarRecuperacion($token2) !== null, 'El enlace más reciente permanece válido');

    $claveNueva = 'Nueva-Clave-2026';
    comprobarAuth(
        ModeloSeguridadAuth::consumirRecuperacion($token2, password_hash($claveNueva, PASSWORD_DEFAULT)) === $idUsuario,
        'El token válido permite cambiar la contraseña una sola vez'
    );
    $hashNuevo = $pdo->query('SELECT pass FROM usuarios WHERE idUsuario=' . $idUsuario)->fetchColumn();
    comprobarAuth(password_verify($claveNueva, $hashNuevo), 'La contraseña nueva queda almacenada con password_hash');
    comprobarAuth(ModeloSeguridadAuth::consumirRecuperacion($token2, password_hash('Otra-2026', PASSWORD_DEFAULT)) === 0, 'El token usado no puede repetirse');

    $tokenVencido = ModeloSeguridadAuth::crearRecuperacion($idUsuario, '127.0.0.1', 3600);
    $pdo->exec("UPDATE auth_recuperaciones SET venceEn=DATE_SUB(NOW(), INTERVAL 1 MINUTE) WHERE usadoEn IS NULL");
    comprobarAuth(ModeloSeguridadAuth::buscarRecuperacion($tokenVencido) === null, 'Un token vencido es rechazado');

    for ($i = 0; $i < 3; $i++) { ModeloSeguridadAuth::registrarFallo('recuperacion', 'persona|127.0.0.1'); }
    comprobarAuth(ModeloSeguridadAuth::limitado('recuperacion', 'persona|127.0.0.1', 3, 3600), 'El límite persistente bloquea al alcanzar el máximo');
    ModeloSeguridadAuth::limpiarIntentos('recuperacion', 'persona|127.0.0.1');
    comprobarAuth(!ModeloSeguridadAuth::limitado('recuperacion', 'persona|127.0.0.1', 3, 3600), 'Los intentos pueden limpiarse después de una validación exitosa');

    $metodoLogin = new ReflectionMethod(ControladorAuth::class, 'autenticarLocal');
    $error = null;
    $hashActual = (string) $hashNuevo;
    $porHash = $metodoLogin->invokeArgs(null, ['persona@example.invalid', $hashActual, &$error]);
    comprobarAuth($porHash === null, 'El hash almacenado ya no funciona como contraseña');
    $correcto = $metodoLogin->invokeArgs(null, ['persona@example.invalid', $claveNueva, &$error]);
    comprobarAuth(is_array($correcto) && (int) $correcto['idUsuario'] === $idUsuario, 'La contraseña real continúa autenticando correctamente');

    $_SESSION = [
        'logueado' => true,
        'usuario' => ['id' => $idUsuario],
        'auth_fingerprint' => hash('sha256', $hashNuevo),
    ];
    comprobarAuth(ControladorAuth::validarSesionActual(), 'La sesión permanece activa mientras las credenciales no cambian');
    $hashPosterior = password_hash('Clave-Posterior-2026', PASSWORD_DEFAULT);
    $pdo->prepare('UPDATE usuarios SET pass = :pass WHERE idUsuario = :idUsuario')
        ->execute([':pass' => $hashPosterior, ':idUsuario' => $idUsuario]);
    comprobarAuth(
        !ControladorAuth::validarSesionActual() && empty($_SESSION['logueado']),
        'Cambiar la contraseña invalida las sesiones que conservaban el hash anterior'
    );

    $url = 'https://campus.example.invalid/index.php?r=forgot&token=' . $token2;
    $correo = CorreoCampus::recuperacionHtml(['nombreUsuario' => 'Persona'], $url);
    comprobarAuth(str_contains($correo, 'Ver aquí') && str_contains($correo, 'href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"'), 'El correo contiene el CTA seguro de recuperación');
    comprobarAuth(!str_contains(strip_tags($correo), $url), 'El correo no muestra la URL como texto visible');
} finally {
    $admin->exec("DROP DATABASE IF EXISTS `$basePrueba`");
    ob_end_flush();
}
