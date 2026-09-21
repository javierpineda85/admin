<?php

require_once __DIR__ . '/../modelos/instituciones.modelo.php';

class ControladorSuperAdmin
{
    public static function csrf()
    {
        if (empty($_SESSION['superadmin_csrf'])) {
            $_SESSION['superadmin_csrf'] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION['superadmin_csrf'];
    }

    private static function autorizado()
    {
        return ($_SESSION['logueado'] ?? false) === true
            && class_exists('ControladorInstitucion', false)
            && ControladorInstitucion::activo()
            && ControladorInstitucion::esSuperAdmin();
    }

    public static function crtDatosPanel()
    {
        if (!self::autorizado()) { throw new RuntimeException('Acceso global no autorizado.'); }
        return [
            'resumen' => ModeloInstituciones::mdlResumenPlataforma(),
            'instituciones' => ModeloInstituciones::mdlListarInstituciones(),
            'membresias' => ModeloInstituciones::mdlListarMembresias(),
            'roles' => ModeloInstituciones::mdlRolesDisponibles(),
            'usuarios' => ModeloInstituciones::mdlUsuariosParaMembresias(),
        ];
    }

    private static function rolesFormulario()
    {
        return isset($_POST['roles']) && is_array($_POST['roles']) ? $_POST['roles'] : [];
    }

    private static function procesarLogoInstitucion()
    {
        $archivo = $_FILES['logoArchivo'] ?? null;
        if (!$archivo || (int)($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) { return null; }
        if ((int)$archivo['error'] !== UPLOAD_ERR_OK) { throw new InvalidArgumentException('No se pudo recibir el logo. Intentá nuevamente.'); }
        if ((int)($archivo['size'] ?? 0) <= 0 || (int)$archivo['size'] > 2 * 1024 * 1024) {
            throw new InvalidArgumentException('El logo debe pesar como máximo 2 MB.');
        }
        $temporal = (string)($archivo['tmp_name'] ?? '');
        if ($temporal === '' || !is_uploaded_file($temporal)) { throw new InvalidArgumentException('El archivo recibido no es una carga válida.'); }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporal);
        $extensiones = ['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp','image/gif'=>'gif'];
        if (!isset($extensiones[$mime]) || @getimagesize($temporal) === false) {
            throw new InvalidArgumentException('El logo debe ser una imagen PNG, JPG, WEBP o GIF.');
        }
        $directorio = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'instituciones';
        if (!is_dir($directorio) && !mkdir($directorio, 0775, true) && !is_dir($directorio)) {
            throw new RuntimeException('No se pudo preparar la carpeta de logos.');
        }
        $nombre = 'institucion_' . bin2hex(random_bytes(12)) . '.' . $extensiones[$mime];
        if (!move_uploaded_file($temporal, $directorio . DIRECTORY_SEPARATOR . $nombre)) {
            throw new RuntimeException('No se pudo guardar el logo.');
        }
        return 'img/instituciones/' . $nombre;
    }

    private static function eliminarLogoSeguro($ruta)
    {
        if (!is_string($ruta) || !preg_match('~^img/instituciones/institucion_[a-f0-9]{24}\.(png|jpe?g|webp|gif)$~i', $ruta)) { return; }
        $archivo = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $ruta);
        if (is_file($archivo)) { @unlink($archivo); }
    }

    public static function crtProcesar()
    {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST'
            || !isset($_POST['accion_superadmin'])) {
            return null;
        }
        if (!self::autorizado()) {
            http_response_code(403);
            throw new RuntimeException('Acceso global no autorizado.');
        }
        $csrf = (string) ($_POST['superadmin_csrf'] ?? '');
        if ($csrf === '' || !hash_equals(self::csrf(), $csrf)) {
            $_SESSION['error_message'] = 'El formulario venció. Recargá la página e intentá nuevamente.';
            return 'forbidden';
        }

        $accion = trim((string) $_POST['accion_superadmin']);
        $logoNuevo = null;
        try {
            if ($accion === 'crear_institucion') {
                $logoNuevo = self::procesarLogoInstitucion();
                $_POST['logo'] = $logoNuevo ?? '';
                $id = ModeloInstituciones::mdlCrearInstitucion($_POST);
                $logoNuevo = null;
                $_SESSION['success_message'] = 'Institución creada correctamente.';
                return $id;
            }
            if ($accion === 'editar_institucion') {
                $idInstitucion = (int)($_POST['idInstitucion'] ?? 0);
                $actual = ModeloInstituciones::mdlObtenerInstitucion($idInstitucion);
                if (!$actual) { throw new RuntimeException('La institución no existe.'); }
                $logoNuevo = self::procesarLogoInstitucion();
                $logoAnterior = (string)($actual['logo'] ?? '');
                $_POST['logo'] = $logoNuevo ?? (!empty($_POST['quitarLogo']) ? '' : $logoAnterior);
                ModeloInstituciones::mdlActualizarInstitucion($idInstitucion, $_POST);
                if ($_POST['logo'] !== $logoAnterior && $logoAnterior !== '' && !ModeloInstituciones::mdlLogoUsadoPorOtraInstitucion($logoAnterior, $idInstitucion)) {
                    self::eliminarLogoSeguro($logoAnterior);
                }
                $logoNuevo = null;
                $_SESSION['success_message'] = 'Institución actualizada correctamente.';
                return true;
            }
            if ($accion === 'suspender_institucion') {
                if (!ModeloInstituciones::mdlCambiarEstadoInstitucion((int) ($_POST['idInstitucion'] ?? 0), false, $_POST['motivoBaja'] ?? '')) {
                    throw new RuntimeException('No se pudo suspender la institución.');
                }
                $_SESSION['success_message'] = 'Institución suspendida. Las membresías ya no habilitan acceso.';
                return true;
            }
            if ($accion === 'activar_institucion') {
                if (!ModeloInstituciones::mdlCambiarEstadoInstitucion((int) ($_POST['idInstitucion'] ?? 0), true)) {
                    throw new RuntimeException('No se pudo activar la institución.');
                }
                $_SESSION['success_message'] = 'Institución activada correctamente.';
                return true;
            }
            if ($accion === 'asignar_administrador') {
                ModeloInstituciones::mdlAsignarAdministrador((int) ($_POST['idInstitucion'] ?? 0), $_POST['email'] ?? '');
                $_SESSION['success_message'] = 'Administrador institucional asignado correctamente.';
                return true;
            }
            if ($accion === 'agregar_membresias') {
                $resultado = ModeloInstituciones::mdlAgregarMembresias(
                    (int)($_POST['idInstitucion'] ?? 0),
                    isset($_POST['usuarios']) && is_array($_POST['usuarios']) ? $_POST['usuarios'] : [],
                    self::rolesFormulario()
                );
                $_SESSION['success_message'] = 'Membresías agregadas: ' . $resultado['agregadas']
                    . '. Membresías que ya existían y se conservaron: ' . $resultado['existentes'] . '.';
                return true;
            }
            if ($accion === 'guardar_membresia') {
                ModeloInstituciones::mdlGuardarMembresia((int)($_POST['idInstitucion'] ?? 0), $_POST['email'] ?? '', self::rolesFormulario());
                $_SESSION['success_message'] = 'Membresía institucional guardada correctamente.';
                return true;
            }
            if ($accion === 'actualizar_roles_membresia') {
                ModeloInstituciones::mdlActualizarRolesMembresia((int)($_POST['idUsuarioInstitucion'] ?? 0), self::rolesFormulario());
                $_SESSION['success_message'] = 'Roles de la membresía actualizados correctamente.';
                return true;
            }
            if ($accion === 'suspender_membresia') {
                ModeloInstituciones::mdlCambiarEstadoMembresia((int)($_POST['idUsuarioInstitucion'] ?? 0), false, $_POST['motivoBaja'] ?? '');
                $_SESSION['success_message'] = 'Membresía suspendida correctamente.';
                return true;
            }
            if ($accion === 'activar_membresia') {
                ModeloInstituciones::mdlCambiarEstadoMembresia((int)($_POST['idUsuarioInstitucion'] ?? 0), true);
                $_SESSION['success_message'] = 'Membresía reactivada correctamente.';
                return true;
            }
            throw new InvalidArgumentException('Acción global inválida.');
        } catch (InvalidArgumentException | RuntimeException $e) {
            if ($logoNuevo) { self::eliminarLogoSeguro($logoNuevo); }
            $_SESSION['error_message'] = $e->getMessage();
            return false;
        } catch (Throwable $e) {
            if ($logoNuevo) { self::eliminarLogoSeguro($logoNuevo); }
            error_log('No se pudo procesar una acción SuperAdmin: ' . get_class($e));
            $_SESSION['error_message'] = 'No se pudo completar la operación global. Revisá el estado e intentá nuevamente.';
            return false;
        } finally {
            $_SESSION['superadmin_csrf'] = bin2hex(random_bytes(32));
        }
    }
}
