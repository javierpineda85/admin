<?php
require_once __DIR__ . '/../modelos/instituciones.modelo.php';

/** Única fuente del contexto: sesión autenticada + membresía revalidada en DB. */
class ControladorInstitucion
{
    private static $actual = null;
    private static $membresias = [];
    private static $identidad = null;

    public static function activo()
    {
        return defined('INSTITUCIONES_CONTEXTO_ACTIVO') && INSTITUCIONES_CONTEXTO_ACTIVO === true;
    }

    public static function limpiar()
    {
        self::$actual = null;
        self::$membresias = [];
        self::$identidad = null;
        unset($_SESSION['institucion_id'], $_SESSION['institucion_slug'], $_SESSION['institucion_usuario_id'],
            $_SESSION['institucion_membresia_id'], $_SESSION['institucion_roles'], $_SESSION['institucion_csrf'],
            $_SESSION['institucion_version'], $_SESSION['vista_estudiante'], $_SESSION['vista_estudiante_id']);
    }

    private static function establecer($membresia)
    {
        $roles = $membresia['roles'] ?? [];
        $id = (int) ($membresia['idInstitucion'] ?? 0);
        $cambio = (int) ($_SESSION['institucion_id'] ?? 0) !== $id
            || (int) ($_SESSION['institucion_usuario_id'] ?? 0) !== (int) (self::$identidad['idUsuario'] ?? 0)
            || (int) ($_SESSION['institucion_membresia_id'] ?? 0) !== (int) ($membresia['idUsuarioInstitucion'] ?? 0)
            || ($_SESSION['institucion_roles'] ?? []) !== $roles;
        if ($cambio) {
            unset($_SESSION['vista_estudiante'], $_SESSION['vista_estudiante_id'],
                $_SESSION['success_message'], $_SESSION['error_message']);
            session_regenerate_id(true);
            $_SESSION['institucion_version'] = bin2hex(random_bytes(24));
            $_SESSION['institucion_csrf'] = bin2hex(random_bytes(32));
        }
        self::$actual = $membresia;
        $_SESSION['institucion_usuario_id'] = (int) (self::$identidad['idUsuario'] ?? 0);
        $_SESSION['institucion_id'] = $id;
        $_SESSION['institucion_slug'] = (string) ($membresia['slug'] ?? '');
        $_SESSION['institucion_membresia_id'] = (int) ($membresia['idUsuarioInstitucion'] ?? 0);
        $_SESSION['institucion_roles'] = $roles;
        // No copiar el rol legacy a la autorización institucional.
        unset($_SESSION['usuario']['rol']);
    }

    public static function refrescar($seleccionAutomatica = true)
    {
        self::$actual = null;
        self::$membresias = [];
        self::$identidad = null;
        if (!self::activo() || ($_SESSION['logueado'] ?? false) !== true) {
            self::limpiar();
            return false;
        }
        self::$identidad = ModeloInstituciones::mdlIdentidadActiva((int) ($_SESSION['usuario']['id'] ?? 0));
        if (!self::$identidad) {
            self::limpiar();
            return false;
        }
        self::$membresias = ModeloInstituciones::mdlMembresiasActivas(self::$identidad['idUsuario']);
        $idSeleccionado = (int) ($_SESSION['institucion_id'] ?? 0);
        $mismoUsuario = (int) ($_SESSION['institucion_usuario_id'] ?? 0) === (int) self::$identidad['idUsuario'];
        $seleccion = null;
        foreach (self::$membresias as $membresia) {
            if ($mismoUsuario && $membresia['idInstitucion'] === $idSeleccionado) {
                $seleccion = $membresia;
                break;
            }
        }
        if (!$seleccion && $seleccionAutomatica && count(self::$membresias) === 1) {
            $seleccion = self::$membresias[0];
        }
        self::establecer($seleccion);
        return true;
    }

    public static function seleccionar($idInstitucion, $csrf, $version)
    {
        // Primero revalidar: suspensión, baja o cambio de rol invalidan tokens viejos.
        if (!self::refrescar(false)) {
            return false;
        }
        if (!is_string($csrf) || !is_string($version) || $csrf === '' || $version === ''
            || !hash_equals(self::csrf(), $csrf) || !hash_equals(self::version(), $version)) {
            return false;
        }
        if (filter_var($idInstitucion, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            return false;
        }
        foreach (self::$membresias as $membresia) {
            if ($membresia['idInstitucion'] === (int) $idInstitucion) {
                self::establecer($membresia);
                // También consume el token al seleccionar de nuevo la misma institución.
                $_SESSION['institucion_csrf'] = bin2hex(random_bytes(32));
                $_SESSION['institucion_version'] = bin2hex(random_bytes(24));
                return true;
            }
        }
        return false;
    }

    public static function actual() { return self::$actual; }
    public static function id() { return (int) (self::$actual['idInstitucion'] ?? 0); }
    public static function membresia() { return self::$actual; }
    public static function membresias() { return self::$membresias; }
    public static function roles() { return self::$actual['roles'] ?? []; }
    public static function esSuperAdmin() { return (int) (self::$identidad['esSuperAdmin'] ?? 0) === 1; }
    public static function csrf()
    {
        if (empty($_SESSION['institucion_csrf'])) {
            $_SESSION['institucion_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['institucion_csrf'];
    }
    public static function version()
    {
        if (empty($_SESSION['institucion_version'])) {
            $_SESSION['institucion_version'] = bin2hex(random_bytes(24));
        }
        return $_SESSION['institucion_version'];
    }

    public static function rutaDestino()
    {
        if (!self::$membresias) { return 'sin-acceso-institucional'; }
        return self::$actual ? '' : 'seleccionar-institucion';
    }

    private static function redirigir($ruta)
    {
        $destino = $ruta === '' ? 'index.php' : 'index.php?r=' . rawurlencode($ruta);
        header('Location: ' . $destino, true, 303);
        exit;
    }

    public static function procesarAntesDeRenderizar()
    {
        if (!self::activo()) {
            // Nunca reutilizar la sesión de ensayo al volver al modo legacy.
            if (isset($_SESSION['institucion_usuario_id'])) {
                $_SESSION = [];
                self::limpiar();
                session_regenerate_id(true);
                self::redirigir('login');
            }
            return;
        }
        header('Cache-Control: no-store, private');
        header('Pragma: no-cache');
        $ruta = is_string($_GET['r'] ?? '') ? ($_GET['r'] ?? '') : '';
        if ($ruta === 'logout') { return; }

        try {
            ModeloInstituciones::mdlVerificarEsquema();
            if (($_SESSION['logueado'] ?? false) !== true) {
                if (in_array($ruta, ['login', 'forgot', 'actividad-publica'], true)) { return; }
                self::redirigir('login');
            }
            $ultima = (int) ($_SESSION['ultima_actividad'] ?? 0);
            if (!$ultima || time() - $ultima >= SESSION_INACTIVITY_TIMEOUT || !self::refrescar(($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')) {
                $_SESSION = [];
                self::limpiar();
                session_regenerate_id(true);
                $_SESSION['login_error'] = 'La sesión venció o la cuenta ya no está disponible. Ingresá nuevamente.';
                self::redirigir('login');
            }
            $_SESSION['ultima_actividad'] = time();
            if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $ruta === 'seleccionar-institucion') {
                if (!self::seleccionar($_POST['id_institucion'] ?? null, $_POST['institucion_csrf'] ?? null, $_POST['institucion_version'] ?? null)) {
                    self::mostrar('solicitud-invalida', 403);
                }
                self::redirigir(self::rutaDestino());
            }

            if (self::$actual) {
                if ($ruta === 'seleccionar-institucion') {
                    self::mostrar('seleccionar-institucion');
                }
                if ($ruta === 'institucion-preparada') {
                    self::redirigir('');
                }
                return;
            }

            if ($ruta === 'seleccionar-institucion' && self::$membresias) {
                self::mostrar('seleccionar-institucion');
            }
            $destino = self::rutaDestino();
            if ($ruta !== $destino || ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') { self::redirigir($destino); }
            self::mostrar($destino);
        } catch (Throwable $e) {
            self::limpiar();
            error_log('No se pudo validar el contexto institucional: ' . get_class($e));
            self::mostrar('institucion-no-disponible', 503);
        }
    }

    private static function mostrar($estado, $codigo = 200)
    {
        http_response_code($codigo);
        $instituciones = self::membresias();
        $institucion = self::actual();
        require __DIR__ . '/../vistas/paginas/instituciones/seleccionar-institucion.php';
        exit;
    }
}
