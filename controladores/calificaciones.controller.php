<?php
require_once('modelos/calificaciones.modelo.php');

class ControladorCalificaciones
{
    public static function crtProcesarAcciones()
    {
        $accion = trim((string) ($_POST['accion'] ?? ''));
        if ($accion !== 'guardar_calificacion') {
            return null;
        }

        return self::crtGuardarCalificacion();
    }

    public static function crtGuardarCalificacion()
    {
        if (!isset($_POST['id_estudiante'], $_POST['id_seccion'], $_POST['id_modulo'], $_POST['id_curso'], $_POST['calificacion'])) {
            return null;
        }

        if (!(ControladorPermisos::esAdministrador() || ControladorPermisos::esDocente())) {
            $_SESSION['error_message'] = 'No tenes permisos para calificar.';
            return 'denied';
        }

        $calificacion = (int) $_POST['calificacion'];
        if ($calificacion < 0 || $calificacion > 100) {
            $_SESSION['error_message'] = 'La calificacion debe estar entre 0 y 100.';
            return 'error';
        }

        $respuesta = ModeloCalificaciones::mdlGuardarCalificacion([
            'id_estudiante' => (int) $_POST['id_estudiante'],
            'id_seccion' => (int) $_POST['id_seccion'],
            'id_modulo' => (int) $_POST['id_modulo'],
            'id_curso' => (int) $_POST['id_curso'],
            'calificacion' => $calificacion,
            'devolucion' => trim((string) ($_POST['devolucion'] ?? '')),
        ]);

        if ($respuesta === 'ok') {
            $_SESSION['success_message'] = 'Calificacion guardada correctamente.';
        } else {
            $_SESSION['error_message'] = 'No se pudo guardar la calificacion.';
        }

        return $respuesta;
    }

    public static function crtCalificacionesPorSeccion($idSeccion)
    {
        return ModeloCalificaciones::mdlCalificacionesPorSeccion($idSeccion);
    }

    public static function crtCalificacionesPorEstudiante($idSeccion, $idEstudiante)
    {
        return ModeloCalificaciones::mdlCalificacionesPorEstudiante($idSeccion, $idEstudiante);
    }

    public static function crtCalificacionPorLeccionYEstudiante($idSeccion, $idLeccion, $idEstudiante)
    {
        return ModeloCalificaciones::mdlCalificacionPorLeccionYEstudiante($idSeccion, $idLeccion, $idEstudiante);
    }

    public static function crtCalificacionesPorSeccionYEstudiante($idSeccion, $idEstudiante)
    {
        return ModeloCalificaciones::mdlCalificacionPorSeccionYEstudiante($idSeccion, $idEstudiante);
    }
}
