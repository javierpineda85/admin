<?php
require_once __DIR__ . '/../modelos/asistencias.modelo.php';
require_once __DIR__ . '/../modelos/asistencias-curso.modelo.php';

class ControladorAsistencias
{
    public static function crtPuedeGestionarCurso($idCurso)
    {
        return (ControladorPermisos::esAdministrador() || ControladorPermisos::esDocente())
            && ModeloAsistenciasCurso::puedeGestionar((int)$idCurso, (int)($_SESSION['usuario']['id'] ?? 0), ControladorPermisos::esAdministrador());
    }

    public static function crtProcesarCurso()
    {
        if (!isset($_POST['accion_asistencia'])) { return null; }
        $idCurso = (int)($_GET['idCurso'] ?? 0);
        if ($idCurso !== (int)($_POST['id_curso'] ?? 0) || !self::crtPuedeGestionarCurso($idCurso)) {
            http_response_code(403);
            throw new RuntimeException('No tenés permisos para gestionar esta asistencia.');
        }
        if (!ModeloAsistenciasCurso::disponible()) {
            $_SESSION['error_message'] = 'La asistencia por curso todavía no está habilitada.';
            return 0;
        }
        if (!hash_equals(self::csrfCurso(), (string)($_POST['csrf_asistencia'] ?? ''))) {
            $_SESSION['error_message'] = 'El formulario venció. Volvé a cargar la página.';
            return 0;
        }
        $idUsuario = (int)($_SESSION['usuario']['id'] ?? 0);
        if ($_POST['accion_asistencia'] === 'crear_clase') {
            $fecha = (string)($_POST['fechaClase'] ?? '');
            $fechaValida = DateTime::createFromFormat('!Y-m-d', $fecha);
            if (!$fechaValida || $fechaValida->format('Y-m-d') !== $fecha) {
                $_SESSION['error_message'] = 'Seleccioná una fecha válida.';
                return 0;
            }
            $idClase = ModeloAsistenciasCurso::crearClase($idCurso, $fecha, trim((string)($_POST['tema'] ?? '')), $idUsuario);
            $_SESSION['success_message'] = 'Planilla del curso preparada. Se conservaron los estados ya guardados para esta fecha.';
            return $idClase;
        }
        if ($_POST['accion_asistencia'] === 'guardar_asistencia') {
            $idClase = (int)($_POST['id_clase'] ?? 0);
            ModeloAsistenciasCurso::guardar($idClase, $idCurso, (array)($_POST['estados'] ?? []), (array)($_POST['observaciones'] ?? []), $idUsuario);
            $_SESSION['success_message'] = 'Asistencia del curso guardada correctamente.';
            return $idClase;
        }
        return null;
    }

    public static function csrfCurso()
    {
        if (empty($_SESSION['csrf_asistencia'])) { $_SESSION['csrf_asistencia'] = bin2hex(random_bytes(32)); }
        return $_SESSION['csrf_asistencia'];
    }

    private static function puede($idSeccion)
    {
        ModeloTenant::exigirSeccion((int)$idSeccion);
        return ControladorPermisos::esAdministrador()
            || (ControladorPermisos::esDocente()
                && ControladorLecciones::crtSeccionAsignadaDocente((int)$idSeccion, (int)($_SESSION['usuario']['id'] ?? 0)));
    }

    public static function crtPuedeGestionar($idSeccion) { return self::puede((int)$idSeccion); }
    public static function crtSecciones() { return ModeloAsistencias::mdlSecciones(ControladorPermisos::esDocente() ? (int)($_SESSION['usuario']['id'] ?? 0) : 0); }
    public static function crtClases($id) { return ModeloAsistencias::mdlClasesSeccion((int)$id); }
    public static function crtClase($id) { return ModeloAsistencias::mdlClase((int)$id); }
    public static function crtRegistros($id) { return ModeloAsistencias::mdlRegistrosClase((int)$id); }
    public static function crtResumenEstudiantesSeccion($id) { return ModeloAsistencias::mdlResumenEstudiantesSeccion((int)$id); }
    public static function crtResumenEstudiante($id) { return ModeloAsistencias::mdlResumenEstudiante((int)$id); }

    public static function crtProcesar()
    {
        if (!isset($_POST['accion_asistencia'])) { return null; }
        $accion = $_POST['accion_asistencia'];
        $idSeccion = (int)($_POST['id_seccion'] ?? 0);
        if (!self::puede($idSeccion)) {
            $_SESSION['error_message'] = 'No tenés permisos para gestionar esta asistencia.';
            return 0;
        }
        if ($accion === 'crear_clase') {
            $fecha = (string)($_POST['fechaClase'] ?? '');
            $fechaValida = DateTime::createFromFormat('Y-m-d', $fecha);
            $seccion = ControladorLecciones::crtBuscarSeccionPorId($idSeccion);
            if (!$seccion || !$fechaValida || $fechaValida->format('Y-m-d') !== $fecha) {
                $_SESSION['error_message'] = 'Seleccioná una fecha válida.'; return 0;
            }
            $idClase = ModeloAsistencias::mdlCrearClase($idSeccion, (int)$seccion['id_curso'], $fecha,
                trim((string)($_POST['tema'] ?? '')), (int)($_SESSION['usuario']['id'] ?? 0));
            $_SESSION['success_message'] = 'Clase preparada con todos los estudiantes presentes.';
            return $idClase;
        }
        if ($accion === 'guardar_asistencia') {
            $idClase = (int)($_POST['id_clase'] ?? 0);
            ModeloTenant::exigirClaseAsistencia($idClase, $idSeccion);
            $respuesta = ModeloAsistencias::mdlGuardar($idClase, (array)($_POST['estados'] ?? []),
                (array)($_POST['observaciones'] ?? []), (int)($_SESSION['usuario']['id'] ?? 0));
            $_SESSION[$respuesta === 'ok' ? 'success_message' : 'error_message'] = $respuesta === 'ok'
                ? 'Asistencia guardada correctamente.' : 'No se pudo guardar la asistencia.';
            return $idClase;
        }
        return null;
    }
}
