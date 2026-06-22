<?php
require_once('modelos/calificaciones.modelo.php');

class ControladorCalificaciones
{
    public static function crtProcesarAcciones()
    {
        $accion = trim((string) ($_POST['accion'] ?? ''));
        if ($accion === 'guardar_calificacion') {
            return self::crtGuardarCalificacion();
        }

        if ($accion === 'crear_evaluacion') {
            return self::crtCrearEvaluacion();
        }

        if ($accion === 'guardar_calificaciones_evaluacion') {
            return self::crtGuardarCalificacionesEvaluacion();
        }

        return null;
    }

    private static function puedeGestionarSeccion($idSeccion)
    {
        if (ControladorPermisos::esAdministrador()) {
            return true;
        }

        return ControladorPermisos::esDocente()
            && ControladorLecciones::crtSeccionAsignadaDocente((int) $idSeccion, (int) ($_SESSION['usuario']['id'] ?? 0));
    }

    public static function crtCrearEvaluacion()
    {
        $idSeccion = (int) ($_POST['id_seccion'] ?? 0);
        $tema = trim((string) ($_POST['temaEvaluacion'] ?? ''));
        $fecha = trim((string) ($_POST['fechaEvaluacion'] ?? ''));
        $seccion = $idSeccion > 0 ? ControladorLecciones::crtBuscarSeccionPorId($idSeccion) : null;

        if (!$seccion || !self::puedeGestionarSeccion($idSeccion)) {
            $_SESSION['error_message'] = 'No tenes permisos para crear evaluaciones en esta materia.';
            return 'denied';
        }

        $fechaObjeto = DateTime::createFromFormat('Y-m-d', $fecha);
        $fechaValida = $fechaObjeto instanceof DateTime
            && $fechaObjeto->format('Y-m-d') === $fecha;

        if ($tema === '' || !$fechaValida) {
            $_SESSION['error_message'] = 'Completa el tema y una fecha valida.';
            return 'error';
        }

        $respuesta = ModeloCalificaciones::mdlCrearEvaluacion([
            'id_seccion' => $idSeccion,
            'id_curso' => (int) ($seccion['id_curso'] ?? 0),
            'id_autor' => (int) ($_SESSION['usuario']['id'] ?? 0),
            'temaEvaluacion' => $tema,
            'fechaEvaluacion' => $fecha,
        ]);

        if (is_int($respuesta) && $respuesta > 0) {
            $_SESSION['success_message'] = 'Evaluacion creada. Ya podes cargar las calificaciones.';
            return $respuesta;
        }

        $_SESSION['error_message'] = 'No se pudo crear la evaluacion.';
        return 'error';
    }

    public static function crtGuardarCalificacionesEvaluacion()
    {
        $idEvaluacion = (int) ($_POST['id_evaluacion'] ?? 0);
        $evaluacion = $idEvaluacion > 0 ? ModeloCalificaciones::mdlEvaluacionPorId($idEvaluacion) : null;

        if (!$evaluacion || !self::puedeGestionarSeccion((int) $evaluacion['id_seccion'])) {
            $_SESSION['error_message'] = 'No tenes permisos para calificar esta evaluacion.';
            return 'denied';
        }

        $notas = (array) ($_POST['calificaciones'] ?? []);
        $devoluciones = (array) ($_POST['devoluciones'] ?? []);
        $estudiantes = ModeloCalificaciones::mdlEstudiantesPorCurso((int) $evaluacion['id_curso']);
        $permitidos = array_fill_keys(array_map('intval', array_column($estudiantes, 'idUsuario')), true);
        $calificaciones = [];

        foreach ($notas as $idEstudiante => $nota) {
            $idEstudiante = (int) $idEstudiante;
            $nota = trim((string) $nota);

            if ($nota === '' || !isset($permitidos[$idEstudiante])) {
                continue;
            }

            $notaNumerica = (float) str_replace(',', '.', $nota);
            if ($notaNumerica < 0 || $notaNumerica > 100) {
                $_SESSION['error_message'] = 'Todas las calificaciones deben estar entre 0 y 100.';
                return 'error';
            }

            $calificaciones[] = [
                'id_estudiante' => $idEstudiante,
                'calificacion' => $notaNumerica,
                'devolucion' => trim((string) ($devoluciones[$idEstudiante] ?? '')),
            ];
        }

        if (empty($calificaciones)) {
            $_SESSION['error_message'] = 'Carga al menos una calificacion.';
            return 'error';
        }

        $respuesta = ModeloCalificaciones::mdlGuardarCalificacionesEvaluacion($idEvaluacion, $calificaciones);
        if ($respuesta === 'ok') {
            $_SESSION['success_message'] = 'Calificaciones guardadas correctamente.';
        } else {
            $_SESSION['error_message'] = 'No se pudieron guardar las calificaciones.';
        }

        return $respuesta;
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

    public static function crtEvaluacionesPorSeccion($idSeccion)
    {
        return ModeloCalificaciones::mdlEvaluacionesPorSeccion((int) $idSeccion);
    }

    public static function crtEvaluacionesPorEstudiante($idSeccion, $idEstudiante)
    {
        return ModeloCalificaciones::mdlEvaluacionesPorEstudiante((int) $idSeccion, (int) $idEstudiante);
    }

    public static function crtEstudiantesPorCurso($idCurso)
    {
        return ModeloCalificaciones::mdlEstudiantesPorCurso((int) $idCurso);
    }

    public static function crtCalificacionesEvaluacion($idEvaluacion)
    {
        return ModeloCalificaciones::mdlCalificacionesEvaluacion((int) $idEvaluacion);
    }
}
