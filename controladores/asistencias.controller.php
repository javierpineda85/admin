<?php
require_once __DIR__ . '/../modelos/asistencias.modelo.php';

class ControladorAsistencias
{
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
