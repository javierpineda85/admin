<?php

require_once 'modelos/usuarios.modelo.php';

class ControladorAuth
{
    private static function authDebugActivo()
    {
        $valor = defined('AUTH_DEBUG') ? AUTH_DEBUG : config_env('AUTH_DEBUG', '0');
        return strtoupper((string) $valor) === '1';
    }

    private static function registrarAuthDebug($mensaje, array $contexto = [])
    {
        if (!self::authDebugActivo()) {
            return;
        }

        $linea = '[' . date('Y-m-d H:i:s') . '] ' . $mensaje;
        if (!empty($contexto)) {
            $linea .= ' | ' . json_encode($contexto, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $ruta = __DIR__ . '/../logs/auth-debug.log';
        $carpeta = dirname($ruta);
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0777, true);
        }

        $resultado = @file_put_contents($ruta, $linea . PHP_EOL, FILE_APPEND);
        if ($resultado === false) {
            @file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'campus-auth-debug.log', $linea . PHP_EOL, FILE_APPEND);
        }
    }

    private static function generarClaveTemporal($longitud = 10)
    {
        $longitud = max(8, (int) $longitud);
        $bytes = bin2hex(random_bytes((int) ceil($longitud / 2)));
        return strtoupper(substr($bytes, 0, $longitud));
    }

    private static function obtenerModoAuth()
    {
        $modo = strtoupper((string) AUTH_MODE);
        return in_array($modo, ['LOCAL', 'WORDPRESS', 'HYBRID'], true) ? $modo : 'LOCAL';
    }

    private static function iniciarSesionUsuario($usuario)
    {
        ModeloUsuarios::mdlActualizarUltimaConexion($usuario['idUsuario']);

        session_regenerate_id(true);

        $_SESSION['logueado'] = true;
        $_SESSION['usuario'] = [
            'id' => (int) $usuario['idUsuario'],
            'nombre' => $usuario['nombreUsuario'],
            'apellido' => $usuario['apellidoUsuario'],
            'email' => $usuario['email'],
            'rol' => $usuario['rol'],
            'img' => $usuario['imgUsuario'],
        ];

        unset($_SESSION['login_error']);

        header('Location: index.php');
        exit;
    }

    private static function autenticarLocal($email, $password, &$error = null)
    {
        $usuario = ModeloUsuarios::mdlObtenerUsuarioPorEmail($email);

        if (!$usuario || (int) ($usuario['activo'] ?? 0) !== 1) {
            $error = 'Usuario no encontrado o inactivo.';
            return null;
        }

        $claveGuardada = (string) $usuario['pass'];
        $claveValida = password_verify($password, $claveGuardada) || hash_equals($claveGuardada, $password);

        if (!$claveValida) {
            $error = 'Credenciales incorrectas.';
            return null;
        }

        if (password_get_info($claveGuardada)['algo'] === 0) {
            ModeloUsuarios::mdlActualizarPassword($usuario['idUsuario'], password_hash($password, PASSWORD_DEFAULT));
            $usuario = ModeloUsuarios::mdlObtenerUsuarioPorId((int) $usuario['idUsuario']) ?: $usuario;
        }

        return $usuario;
    }

    private static function verificarPasswordWordPressLegacy($password, $hash)
    {
        if (WP_ROOT_PATH === '') {
            self::registrarAuthDebug('WP legacy hash sin WP_ROOT_PATH');
            return false;
        }

        $phpassFile = WP_ROOT_PATH . DIRECTORY_SEPARATOR . 'wp-includes' . DIRECTORY_SEPARATOR . 'class-phpass.php';
        if (!is_file($phpassFile)) {
            self::registrarAuthDebug('No existe class-phpass.php', ['ruta' => $phpassFile]);
            return false;
        }

        require_once $phpassFile;

        if (!class_exists('PasswordHash')) {
            self::registrarAuthDebug('No existe clase PasswordHash luego de require');
            return false;
        }

        $hasher = new PasswordHash(8, true);
        return $hasher->CheckPassword($password, $hash);
    }

    private static function verificarPasswordWordPressNativo($password, $hash, $userId = 0)
    {
        if (WP_ROOT_PATH === '') {
            self::registrarAuthDebug('WP nativo sin WP_ROOT_PATH');
            return null;
        }

        $wpLoad = WP_ROOT_PATH . DIRECTORY_SEPARATOR . 'wp-load.php';
        if (!is_file($wpLoad)) {
            self::registrarAuthDebug('No existe wp-load.php', ['ruta' => $wpLoad]);
            return null;
        }

        if (!function_exists('wp_check_password')) {
            require_once $wpLoad;
        }

        if (!function_exists('wp_check_password')) {
            self::registrarAuthDebug('No se pudo cargar wp_check_password');
            return null;
        }

        $resultado = wp_check_password($password, $hash, (int) $userId);
        self::registrarAuthDebug('Resultado wp_check_password', [
            'userId' => (int) $userId,
            'hashPrefix' => substr((string) $hash, 0, 12),
            'resultado' => (bool) $resultado,
        ]);
        return $resultado;
    }

    private static function verificarPasswordWordPress($password, $hash, $userId = 0)
    {
        $hash = (string) $hash;
        if ($hash === '') {
            self::registrarAuthDebug('Hash WordPress vacio');
            return false;
        }

        if (strlen($hash) <= 32) {
            $resultadoMd5 = hash_equals($hash, md5($password));
            self::registrarAuthDebug('Validacion md5 fallback', ['resultado' => $resultadoMd5]);
            return $resultadoMd5;
        }

        if (str_starts_with($hash, '$P$') || str_starts_with($hash, '$H$')) {
            $resultadoLegacy = self::verificarPasswordWordPressLegacy($password, $hash);
            self::registrarAuthDebug('Validacion legacy phpass', ['resultado' => $resultadoLegacy]);
            return $resultadoLegacy;
        }

        $validacionNativa = self::verificarPasswordWordPressNativo($password, $hash, $userId);
        if ($validacionNativa !== null) {
            return (bool) $validacionNativa;
        }

        if (str_starts_with($hash, '$wp')) {
            $passwordToVerify = base64_encode(hash_hmac('sha384', $password, 'wp-sha384', true));
            $resultadoWp = password_verify($passwordToVerify, substr($hash, 3));
            self::registrarAuthDebug('Validacion $wp fallback', ['resultado' => $resultadoWp]);
            return $resultadoWp;
        }

        if (password_verify($password, $hash)) {
            self::registrarAuthDebug('Validacion password_verify directa exitosa');
            return true;
        }

        self::registrarAuthDebug('Ninguna validacion de hash WordPress resulto exitosa', [
            'hashPrefix' => substr($hash, 0, 12),
        ]);
        return false;
    }

    private static function autenticarWordPress($email, $password, &$error = null)
    {
        if (WP_DB_NAME === '' || WP_DB_USER === '') {
            $error = 'La autenticacion con WordPress no esta configurada todavia.';
            self::registrarAuthDebug('Configuracion WP incompleta');
            return null;
        }

        $usuarioWp = ModeloUsuarios::mdlObtenerUsuarioWordPressPorEmail($email);
        if (!$usuarioWp) {
            $error = 'Credenciales incorrectas.';
            self::registrarAuthDebug('Usuario WP no encontrado', ['email' => $email]);
            return null;
        }

        self::registrarAuthDebug('Usuario WP encontrado', [
            'email' => $email,
            'userId' => (int) ($usuarioWp['ID'] ?? 0),
            'login' => $usuarioWp['user_login'] ?? '',
            'hashPrefix' => substr((string) ($usuarioWp['user_pass'] ?? ''), 0, 12),
            'rolCap' => $usuarioWp['capabilities'] ?? '',
            'isTutorInstructor' => $usuarioWp['is_tutor_instructor'] ?? null,
            'tutorStatus' => $usuarioWp['tutor_instructor_status'] ?? null,
            'isTutorStudent' => $usuarioWp['is_tutor_student'] ?? null,
        ]);

        if (!self::verificarPasswordWordPress($password, (string) ($usuarioWp['user_pass'] ?? ''), (int) ($usuarioWp['ID'] ?? 0))) {
            $error = 'Credenciales incorrectas.';
            self::registrarAuthDebug('Password WP invalida', [
                'email' => $email,
                'userId' => (int) ($usuarioWp['ID'] ?? 0),
            ]);
            return null;
        }

        $usuarioLocal = ModeloUsuarios::mdlSincronizarUsuarioWordPress($usuarioWp);
        if (!$usuarioLocal || (int) ($usuarioLocal['activo'] ?? 0) !== 1) {
            $error = 'Tu cuenta no tiene un rol habilitado para ingresar a Campus.';
            self::registrarAuthDebug('Usuario WP sin rol habilitado o sincronizacion fallida', [
                'email' => $email,
                'userId' => (int) ($usuarioWp['ID'] ?? 0),
            ]);
            return null;
        }

        self::registrarAuthDebug('Autenticacion WP exitosa', [
            'email' => $email,
            'campusUserId' => (int) ($usuarioLocal['idUsuario'] ?? 0),
            'rol' => $usuarioLocal['rol'] ?? '',
        ]);
        return $usuarioLocal;
    }

    public static function crtIniciarSesion()
    {
        if (!isset($_POST['login_email'], $_POST['login_pass'])) {
            return;
        }

        $email = trim((string) $_POST['login_email']);
        $password = (string) $_POST['login_pass'];

        if ($email === '' || $password === '') {
            $_SESSION['login_error'] = 'Completa correo y contrasena.';
            return;
        }

        $modo = self::obtenerModoAuth();
        $errorLocal = null;
        $errorWordPress = null;

        if (in_array($modo, ['LOCAL', 'HYBRID'], true)) {
            $usuarioLocal = self::autenticarLocal($email, $password, $errorLocal);
            if ($usuarioLocal) {
                self::iniciarSesionUsuario($usuarioLocal);
            }
        }

        if (in_array($modo, ['WORDPRESS', 'HYBRID'], true)) {
            $usuarioWordPress = self::autenticarWordPress($email, $password, $errorWordPress);
            if ($usuarioWordPress) {
                self::iniciarSesionUsuario($usuarioWordPress);
            }
        }

        $_SESSION['login_error'] = $errorWordPress ?: $errorLocal ?: 'No se pudo iniciar sesion.';
    }

    public static function crtRecuperarPassword()
    {
        if (!isset($_POST['forgot_email'])) {
            return null;
        }

        if (self::obtenerModoAuth() === 'WORDPRESS') {
            $_SESSION['forgot_error'] = 'La recuperacion de contrasena se gestiona desde mentemotion.com.';
            return false;
        }

        $email = trim((string) $_POST['forgot_email']);
        if ($email === '') {
            $_SESSION['forgot_error'] = 'Completa tu correo electronico.';
            return false;
        }

        $usuario = ModeloUsuarios::mdlObtenerUsuarioPorEmail($email);
        if (!$usuario || (int) ($usuario['activo'] ?? 0) !== 1) {
            $_SESSION['forgot_error'] = 'No encontramos una cuenta activa con ese correo.';
            return false;
        }

        $claveTemporal = self::generarClaveTemporal(10) . '!';
        $respuesta = ModeloUsuarios::mdlActualizarPassword(
            (int) $usuario['idUsuario'],
            password_hash($claveTemporal, PASSWORD_DEFAULT)
        );

        if ($respuesta !== 'ok') {
            $_SESSION['forgot_error'] = 'No se pudo generar la nueva contrasena.';
            return false;
        }

        $asunto = 'Classroom - Tu contrasena temporal';
        $mensaje = "Hola " . trim((string) $usuario['nombreUsuario']) . ",\n\n"
            . "Se genero una contrasena temporal para tu cuenta:\n\n"
            . $claveTemporal . "\n\n"
            . "Ingresa en http://localhost/admin/index.php?r=login y luego actualizala desde tu perfil.\n";
        $cabeceras = implode("\r\n", [
            'MIME-Version: 1.0',
            'Content-type: text/plain; charset=UTF-8',
            'From: Classroom <no-reply@classroom.local>',
        ]);
        @mail((string) $usuario['email'], $asunto, $mensaje, $cabeceras);

        $_SESSION['forgot_success'] = 'Generamos una contrasena temporal para tu cuenta.';
        $_SESSION['forgot_temp_password'] = $claveTemporal;
        return $claveTemporal;
    }
}
