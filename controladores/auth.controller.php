<?php

require_once 'modelos/usuarios.modelo.php';
require_once __DIR__ . '/../modelos/seguridad-auth.modelo.php';
require_once __DIR__ . '/../modelos/correo.php';
require_once __DIR__ . '/institucion.controller.php';

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

    private static function obtenerModoAuth()
    {
        $modo = strtoupper((string) AUTH_MODE);
        return in_array($modo, ['LOCAL', 'WORDPRESS', 'HYBRID'], true) ? $modo : 'LOCAL';
    }

    public static function registroDisponible()
    {
        return ControladorInstitucion::activo() && self::obtenerModoAuth() !== 'WORDPRESS';
    }

    public static function recuperacionLocalDisponible()
    {
        return self::obtenerModoAuth() !== 'WORDPRESS';
    }

    public static function csrfRegistro()
    {
        if (empty($_SESSION['registro_csrf'])) {
            $_SESSION['registro_csrf'] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION['registro_csrf'];
    }

    public static function csrfRecuperacion()
    {
        if (empty($_SESSION['recuperacion_csrf'])) {
            $_SESSION['recuperacion_csrf'] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION['recuperacion_csrf'];
    }

    private static function ipCliente()
    {
        $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? 'desconocida'));
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'desconocida';
    }

    private static function claveLimite($email)
    {
        return strtolower(trim((string) $email)) . '|' . self::ipCliente();
    }

    private static function limiteExcedido($tipo, $clave, $maximo, $ventana)
    {
        try {
            return ModeloSeguridadAuth::limitado($tipo, $clave, $maximo, $ventana);
        } catch (Throwable $e) {
            error_log('No se pudo consultar el límite de autenticación: ' . get_class($e));
            return false;
        }
    }

    private static function registrarFalloSeguro($tipo, $clave)
    {
        try {
            ModeloSeguridadAuth::registrarFallo($tipo, $clave);
        } catch (Throwable $e) {
            error_log('No se pudo registrar un intento de autenticación: ' . get_class($e));
        }
    }

    private static function limpiarIntentosSeguro($tipo, $clave)
    {
        try {
            ModeloSeguridadAuth::limpiarIntentos($tipo, $clave);
        } catch (Throwable $e) {
            error_log('No se pudieron limpiar intentos de autenticación: ' . get_class($e));
        }
    }

    public static function crtRegistrarCuenta()
    {
        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST'
            || !isset($_POST['accion_registro'])) {
            return null;
        }
        if (!self::registroDisponible()) {
            http_response_code(403);
            $_SESSION['registro_error'] = 'El registro directo no está disponible en este momento.';
            return false;
        }

        $ahora = time();
        $intentos = array_values(array_filter(
            is_array($_SESSION['registro_intentos'] ?? null) ? $_SESSION['registro_intentos'] : [],
            static fn($marca) => is_int($marca) && $marca >= $ahora - 900
        ));
        if (count($intentos) >= 5) {
            http_response_code(429);
            $_SESSION['registro_error'] = 'Se realizaron demasiados intentos. Esperá unos minutos antes de volver a probar.';
            return false;
        }
        $intentos[] = $ahora;
        $_SESSION['registro_intentos'] = $intentos;

        $csrf = (string)($_POST['registro_csrf'] ?? '');
        if ($csrf === '' || !hash_equals(self::csrfRegistro(), $csrf)) {
            http_response_code(403);
            $_SESSION['registro_error'] = 'El formulario venció. Recargá la página e intentá nuevamente.';
            $_SESSION['registro_csrf'] = bin2hex(random_bytes(32));
            return false;
        }
        $_SESSION['registro_csrf'] = bin2hex(random_bytes(32));

        // Campo trampa: una persona no lo completa, los envíos automáticos suelen hacerlo.
        if (trim((string)($_POST['website'] ?? '')) !== '') {
            $_SESSION['registro_error'] = 'No se pudo completar el registro.';
            return false;
        }

        $nombre = trim((string)($_POST['registro_nombre'] ?? ''));
        $apellido = trim((string)($_POST['registro_apellido'] ?? ''));
        $email = strtolower(trim((string)($_POST['registro_email'] ?? '')));
        $password = (string)($_POST['registro_password'] ?? '');
        $confirmacion = (string)($_POST['registro_password_confirmacion'] ?? '');
        $largo = static fn($valor) => function_exists('mb_strlen') ? mb_strlen($valor, 'UTF-8') : strlen($valor);
        $nombreValido = $nombre !== '' && $largo($nombre) <= 20 && preg_match("/^[\\p{L}\\p{M}' -]+$/u", $nombre);
        $apellidoValido = $apellido !== '' && $largo($apellido) <= 20 && preg_match("/^[\\p{L}\\p{M}' -]+$/u", $apellido);

        if (!$nombreValido || !$apellidoValido) {
            $_SESSION['registro_error'] = 'Ingresá un nombre y apellido válidos, de hasta 20 caracteres cada uno.';
            return false;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 50) {
            $_SESSION['registro_error'] = 'Ingresá un correo electrónico válido.';
            return false;
        }
        if (strlen($password) < 8 || strlen($password) > 72
            || !preg_match('/[[:alpha:]]/', $password) || !preg_match('/[[:digit:]]/', $password)) {
            $_SESSION['registro_error'] = 'La contraseña debe tener entre 8 y 72 caracteres e incluir letras y números.';
            return false;
        }
        if (!hash_equals($password, $confirmacion)) {
            $_SESSION['registro_error'] = 'Las contraseñas no coinciden.';
            return false;
        }

        try {
            $usuario = ModeloUsuarios::mdlRegistrarIdentidadPublica([
                'nombreUsuario' => $nombre,
                'apellidoUsuario' => $apellido,
                'email' => $email,
                'pass' => $password,
            ]);
            unset($_SESSION['registro_error'], $_SESSION['registro_intentos'], $_SESSION['registro_csrf']);
            $_SESSION['registro_reciente'] = true;
            self::iniciarSesionUsuario($usuario);
        } catch (InvalidArgumentException | RuntimeException $e) {
            $_SESSION['registro_error'] = $e->getMessage();
            return false;
        } catch (Throwable $e) {
            error_log('No se pudo registrar una identidad desde Campus: ' . get_class($e));
            $_SESSION['registro_error'] = 'No se pudo crear la cuenta. Intentá nuevamente más tarde.';
            return false;
        }
        return true;
    }

    private static function iniciarSesionUsuario($usuario)
    {
        ModeloUsuarios::mdlActualizarUltimaConexion($usuario['idUsuario']);

        ControladorInstitucion::limpiar();
        session_regenerate_id(true);

        $_SESSION['logueado'] = true;
        $_SESSION['ultima_actividad'] = time();
        $_SESSION['usuario'] = [
            'id' => (int) $usuario['idUsuario'],
            'nombre' => $usuario['nombreUsuario'],
            'apellido' => $usuario['apellidoUsuario'],
            'email' => $usuario['email'],
            'rol' => $usuario['rol'],
            'img' => $usuario['imgUsuario'],
        ];
        $_SESSION['auth_fingerprint'] = hash('sha256', (string) ($usuario['pass'] ?? ''));

        unset($_SESSION['login_error']);

        if (ControladorInstitucion::activo()) {
            unset($_SESSION['usuario']['rol']);
            if (!ControladorInstitucion::refrescar()) {
                $_SESSION = [];
                header('Location: index.php?r=login', true, 303);
                exit;
            }
            $rutaInstitucional = ControladorInstitucion::rutaDestino();
            $destinoInstitucional = $rutaInstitucional === ''
                ? 'index.php'
                : ($rutaInstitucional === 'superadmin' ? 'superadmin' : 'index.php?r=' . rawurlencode($rutaInstitucional));
            header('Location: ' . $destinoInstitucional, true, 303);
            exit;
        }

        header('Location: index.php');
        exit;
    }

    public static function validarSesionActual()
    {
        if (($_SESSION['logueado'] ?? false) !== true) { return true; }
        $usuario = ModeloUsuarios::mdlObtenerUsuarioPorId((int) ($_SESSION['usuario']['id'] ?? 0));
        $fingerprint = hash('sha256', (string) ($usuario['pass'] ?? ''));
        $fingerprintSesion = (string) ($_SESSION['auth_fingerprint'] ?? '');
        if (!$usuario || (int) ($usuario['activo'] ?? 0) !== 1
            || $fingerprintSesion === '' || !hash_equals($fingerprint, $fingerprintSesion)) {
            $_SESSION = [];
            ControladorInstitucion::limpiar();
            session_regenerate_id(true);
            $_SESSION['login_error'] = 'La sesión finalizó porque cambiaron las credenciales de la cuenta. Ingresá nuevamente.';
            return false;
        }
        return true;
    }

    private static function autenticarLocal($email, $password, &$error = null)
    {
        $usuario = ModeloUsuarios::mdlObtenerUsuarioPorEmail($email);

        if (!$usuario || (int) ($usuario['activo'] ?? 0) !== 1) {
            $error = 'Usuario no encontrado o inactivo.';
            return null;
        }

        $claveGuardada = (string) $usuario['pass'];
        $claveValida = password_verify($password, $claveGuardada);

        if (!$claveValida) {
            $error = 'Credenciales incorrectas.';
            return null;
        }

        if (password_needs_rehash($claveGuardada, PASSWORD_DEFAULT)) {
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
            'userStatus' => (int) ($usuarioWp['user_status'] ?? 0),
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
            $error = 'Tu cuenta no está disponible para ingresar a Campus.';
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

    private static function debeIntentarWordPress($email)
    {
        $usuario = ModeloUsuarios::mdlObtenerUsuarioPorEmail((string) $email);
        if (!$usuario) {
            return true;
        }

        return strtoupper(trim((string) ($usuario['origenAuth'] ?? 'LOCAL'))) === 'WORDPRESS';
    }

    public static function crtIniciarSesion()
    {
        if (!isset($_POST['login_email'], $_POST['login_pass'])) {
            return;
        }

        $email = trim((string) $_POST['login_email']);
        $password = (string) $_POST['login_pass'];

        if ($email === '' || $password === '') {
            $_SESSION['login_error'] = 'No se pudo iniciar sesión con las credenciales ingresadas.';
            return;
        }

        $claveLimite = self::claveLimite($email);
        $claveIp = 'ip|' . self::ipCliente();
        if (self::limiteExcedido('login', $claveLimite, 5, 900)
            || self::limiteExcedido('login_ip', $claveIp, 20, 900)) {
            http_response_code(429);
            $_SESSION['login_error'] = 'Se realizaron demasiados intentos. Esperá unos minutos antes de volver a probar.';
            return;
        }

        $modo = self::obtenerModoAuth();
        $errorLocal = null;
        $errorWordPress = null;

        try {
            if (in_array($modo, ['LOCAL', 'HYBRID'], true)) {
                $usuarioLocal = self::autenticarLocal($email, $password, $errorLocal);
                if ($usuarioLocal) {
                    self::limpiarIntentosSeguro('login', $claveLimite);
                    self::limpiarIntentosSeguro('login_ip', $claveIp);
                    self::iniciarSesionUsuario($usuarioLocal);
                }
            }

            if ($modo === 'WORDPRESS' || ($modo === 'HYBRID' && self::debeIntentarWordPress($email))) {
                $usuarioWordPress = self::autenticarWordPress($email, $password, $errorWordPress);
                if ($usuarioWordPress) {
                    self::limpiarIntentosSeguro('login', $claveLimite);
                    self::limpiarIntentosSeguro('login_ip', $claveIp);
                    self::iniciarSesionUsuario($usuarioWordPress);
                }
            }
        } catch (Throwable $e) {
            if (!ControladorInstitucion::activo()) { throw $e; }
            ControladorInstitucion::limpiar();
            unset($_SESSION['logueado'], $_SESSION['usuario'], $_SESSION['ultima_actividad']);
            error_log('No se pudo autenticar la identidad institucional: ' . get_class($e));
            $_SESSION['login_error'] = 'No se pudo validar tu cuenta. Intentá nuevamente más tarde.';
            return;
        }

        self::registrarFalloSeguro('login', $claveLimite);
        self::registrarFalloSeguro('login_ip', $claveIp);
        $_SESSION['login_error'] = 'No se pudo iniciar sesión con las credenciales ingresadas.';
    }

    public static function crtRecuperarPassword()
    {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            return null;
        }
        $csrf = (string) ($_POST['recuperacion_csrf'] ?? '');
        if ($csrf === '' || !hash_equals(self::csrfRecuperacion(), $csrf)) {
            http_response_code(403);
            $_SESSION['forgot_error'] = 'El formulario venció. Recargá la página e intentá nuevamente.';
            $_SESSION['recuperacion_csrf'] = bin2hex(random_bytes(32));
            return false;
        }
        $_SESSION['recuperacion_csrf'] = bin2hex(random_bytes(32));

        if (isset($_POST['accion_restablecer_password'])) {
            return self::crtRestablecerPassword();
        }
        if (!isset($_POST['accion_solicitar_recuperacion'])) { return null; }

        $email = strtolower(trim((string) ($_POST['forgot_email'] ?? '')));
        $mensajeUniforme = 'Si existe una cuenta local activa con ese correo, recibirás un enlace para restablecer la contraseña.';
        $claveLimite = self::claveLimite($email);
        $claveIp = 'ip|' . self::ipCliente();
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254
            || self::limiteExcedido('recuperacion', $claveLimite, 3, 3600)
            || self::limiteExcedido('recuperacion_ip', $claveIp, 20, 3600)) {
            self::registrarFalloSeguro('recuperacion', $claveLimite);
            self::registrarFalloSeguro('recuperacion_ip', $claveIp);
            self::registrarAuthDebug('Recuperación rechazada por validación o límite');
            $_SESSION['forgot_success'] = $mensajeUniforme;
            return true;
        }

        self::registrarFalloSeguro('recuperacion', $claveLimite);
        self::registrarFalloSeguro('recuperacion_ip', $claveIp);
        try {
            $usuario = self::obtenerModoAuth() === 'WORDPRESS' ? null : ModeloUsuarios::mdlObtenerUsuarioPorEmail($email);
            $origen = strtoupper((string) ($usuario['origenAuth'] ?? 'LOCAL'));
            if ($usuario && (int) ($usuario['activo'] ?? 0) === 1 && $origen !== 'WORDPRESS') {
                self::registrarAuthDebug('Recuperación: cuenta local elegible');
                $token = ModeloSeguridadAuth::crearRecuperacion((int) $usuario['idUsuario'], self::ipCliente(), 3600);
                $url = rtrim((string) APP_BASE_URL, '/') . '/index.php?r=forgot&token=' . rawurlencode($token);
                $asunto = '=?UTF-8?B?' . base64_encode(MAIL_FROM_NAME . ' - Restablecer contraseña') . '?=';
                $mensaje = CorreoCampus::recuperacionHtml($usuario, $url);
                $cabeceras = implode("\r\n", [
                    'MIME-Version: 1.0',
                    'Content-type: text/html; charset=UTF-8',
                    'From: ' . CorreoCampus::remitente(),
                ]);
                $enviado = @mail((string) $usuario['email'], $asunto, $mensaje, $cabeceras);
                self::registrarAuthDebug('Recuperación: resultado de mail()', ['aceptado' => (bool) $enviado]);
                if (!$enviado) {
                    ModeloSeguridadAuth::invalidarRecuperacion($token);
                    error_log('No se pudo enviar un correo de recuperación de Campus.');
                }
            } else {
                self::registrarAuthDebug('Recuperación: no hay cuenta local elegible');
            }
        } catch (Throwable $e) {
            self::registrarAuthDebug('Recuperación: excepción durante el envío', ['tipo' => get_class($e)]);
            error_log('No se pudo procesar una recuperación de contraseña: ' . get_class($e));
        }
        $_SESSION['forgot_success'] = $mensajeUniforme;
        return true;
    }

    private static function crtRestablecerPassword()
    {
        $token = strtolower(trim((string) ($_POST['token'] ?? '')));
        $password = (string) ($_POST['password_nueva'] ?? '');
        $confirmacion = (string) ($_POST['password_confirmacion'] ?? '');
        if (strlen($password) < 8 || strlen($password) > 72
            || !preg_match('/[[:alpha:]]/', $password) || !preg_match('/[[:digit:]]/', $password)) {
            $_SESSION['forgot_error'] = 'La contraseña debe tener entre 8 y 72 caracteres e incluir letras y números.';
            return false;
        }
        if (!hash_equals($password, $confirmacion)) {
            $_SESSION['forgot_error'] = 'Las contraseñas no coinciden.';
            return false;
        }
        try {
            $idUsuario = ModeloSeguridadAuth::consumirRecuperacion($token, password_hash($password, PASSWORD_DEFAULT));
            if ($idUsuario <= 0) {
                $_SESSION['forgot_error'] = 'El enlace no es válido o ya venció.';
                return false;
            }
            $_SESSION = [];
            session_regenerate_id(true);
            $_SESSION['forgot_success'] = 'La contraseña fue actualizada. Ya podés iniciar sesión.';
            return true;
        } catch (Throwable $e) {
            error_log('No se pudo completar una recuperación de contraseña: ' . get_class($e));
            $_SESSION['forgot_error'] = 'No se pudo actualizar la contraseña. Intentá nuevamente más tarde.';
            return false;
        }
    }

    public static function tokenRecuperacionValido($token)
    {
        try {
            return ModeloSeguridadAuth::buscarRecuperacion((string) $token) !== null;
        } catch (Throwable $e) {
            error_log('No se pudo validar un enlace de recuperación: ' . get_class($e));
            return false;
        }
    }
}
