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
        ];
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
        try {
            if ($accion === 'crear_institucion') {
                $id = ModeloInstituciones::mdlCrearInstitucion($_POST);
                $_SESSION['success_message'] = 'Institución creada correctamente.';
                return $id;
            }
            if ($accion === 'editar_institucion') {
                ModeloInstituciones::mdlActualizarInstitucion((int) ($_POST['idInstitucion'] ?? 0), $_POST);
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
            throw new InvalidArgumentException('Acción global inválida.');
        } catch (Throwable $e) {
            $_SESSION['error_message'] = $e->getMessage();
            return false;
        } finally {
            $_SESSION['superadmin_csrf'] = bin2hex(random_bytes(32));
        }
    }
}
