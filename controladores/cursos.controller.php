<?php
require_once('modelos/cursos.modelo.php');

class ControladorCursos
{
    static public function crtBuscarCursoPorId($idCurso)
    {
        return ModeloCursos::mdlBuscarCursoPorId((int) $idCurso);
    }

    static public function crtListarCursos()
    {
        return ModeloCursos::mdlListarCursos();
    }

    static public function crtCursosPorEstudiante($idEstudiante)
    {
        return ModeloCursos::mdlCursosPorEstudiante((int) $idEstudiante);
    }

    static public function crtCursosPorDocente($idDocente)
    {
        return ModeloCursos::mdlCursosPorDocente((int) $idDocente);
    }

    static public function crtEstudianteInscriptoCurso($idEstudiante, $idCurso)
    {
        return ModeloCursos::mdlEstudianteInscriptoCurso((int) $idEstudiante, (int) $idCurso);
    }

    static public function crtSeccionesPorCurso($idCurso)
    {
        return ModeloCursos::mdlSeccionesPorCurso((int) $idCurso);
    }

    static public function crtSeccionesPorCursoParaDocente($idCurso, $idDocente)
    {
        return ModeloCursos::mdlSeccionesPorCursoParaDocente((int) $idCurso, (int) $idDocente);
    }

    static public function crtPuedeGestionarCurso($idCurso)
    {
        if (ControladorPermisos::esAdministrador()) {
            return true;
        }

        return ControladorPermisos::esDocente()
            && ModeloCursos::mdlDocentePuedeGestionarCurso((int) $idCurso, (int) ($_SESSION['usuario']['id'] ?? 0));
    }

    /*GUARDAR CURSO */
    static public function crtGuardarCurso()
    {
        if (isset($_POST["nombreCurso"])) {
            if (!ControladorPermisos::esAdministrador() && !ControladorPermisos::esDocente()) {
                $_SESSION['error_message'] = 'No tenes permisos para crear cursos.';
                return 'denied';
            }

            $tabla = "cursos";

            $datos = array(
                "nombreCurso"       => $_POST["nombreCurso"],
                "contenidoCurso"    => $_POST["contenidoCurso"],
                "estado"            => $_POST["estado"],
                "fechaInicioCurso"  => $_POST["fechaInicioCurso"],
                "fechaFinCurso"     => $_POST["fechaFinCurso"],
                "horarioCurso"      => $_POST["horarioCurso"],
                "creadoPor"         => (int) ($_SESSION['usuario']['id'] ?? 0)
            );

            $respuesta = ModeloCursos::mdlGuardarCurso($tabla, $datos);
            if ($respuesta === 'ok') {
                $_SESSION['success_message'] = 'Curso creado exitosamente';
            } else {
                $_SESSION['error_message'] = 'No se pudo crear el curso';
            }
            return $respuesta;
        }
    }

    /*MODIFICAR CURSO */
    static public function crtModificarCurso()
    {
        if (isset($_POST["nombreCurso"])) {

            $idCurso = (int) ($_POST['idCurso'] ?? 0);
            if (!self::crtPuedeGestionarCurso($idCurso)) {
                $_SESSION['error_message'] = 'No podes editar un curso que no esta a tu cargo.';
                return 'denied';
            }

            $tabla = "cursos";

            $datos = array(
                "idCurso"           => $idCurso,
                "nombreCurso"       => $_POST["nombreCurso"],
                "contenidoCurso"    => $_POST["contenidoCurso"],
                "estado"            => $_POST["estado"],
                "fechaInicioCurso"  => $_POST["fechaInicioCurso"],
                "fechaFinCurso"     => $_POST["fechaFinCurso"],
                "horarioCurso"      => $_POST["horarioCurso"]
            );

            $respuesta = ModeloCursos::mdlModificarCurso($tabla, $datos);
            if ($respuesta === 'ok') {
                $_SESSION['success_message'] = 'Curso modificado exitosamente';
            } else {
                $_SESSION['error_message'] = 'No se pudo modificar el curso';
            }
            return $respuesta;
        }
    }

    /*Asignar curso */
    static public function crtEstudiantesDisponiblesCurso($idCurso)
    {
        return ModeloCursos::mdlEstudiantesDisponiblesCurso((int) $idCurso);
    }

    static public function crtAsignarCurso()
    {
        if (isset($_POST["idUsuarios"])) {
            if (!ControladorPermisos::esAdministrador()) {
                $_SESSION['error_message'] = 'No tenes permisos para inscribir estudiantes.';
                return 'denied';
            }

            $tabla = 'asignacioncursos';
            $idCurso = (int) ($_POST['idCurso'] ?? ($_GET['idCurso'] ?? 0));
            $idUsuarios = array_values(array_unique(array_filter(array_map('intval', (array) $_POST['idUsuarios']))));
            $respuestaFinal = 'ok';
            $totalRegistrados = 0;

            if ($idCurso <= 0 || empty($idUsuarios)) {
                $_SESSION['error_message'] = 'Selecciona al menos un estudiante valido.';
                return 'error';
            }
            
            foreach ($idUsuarios as $idUsuario) {
                $datos = array(
                    "idCurso"    => $idCurso,
                    "idUsuario"  => $idUsuario
                );
                
                $respuesta = ModeloCursos::mdlAsignarCurso($tabla, $datos);
                if ($respuesta === 'ok') {
                    $totalRegistrados++;
                } elseif ($respuesta !== 'exists') {
                    $respuestaFinal = 'error';
                }
            }
            
            if ($respuestaFinal === 'ok' && $totalRegistrados > 0) {
                $_SESSION['success_message'] = 'Se han registrado los estudiantes seleccionados.';
            } elseif ($respuestaFinal === 'ok') {
                $_SESSION['error_message'] = 'Los estudiantes seleccionados ya estaban inscriptos o no estan activos.';
            } else {
                $_SESSION['error_message'] = 'No se pudieron registrar todos los estudiantes';
            }

            return $respuestaFinal;
        }
    }

    static public function crtQuitarEstudianteCurso()
    {
        if (!isset($_POST['accion_curso']) || $_POST['accion_curso'] !== 'quitar_estudiante') {
            return null;
        }

        if (!ControladorPermisos::esAdministrador()) {
            $_SESSION['error_message'] = 'No tenes permisos para quitar estudiantes del curso.';
            return false;
        }

        $idCurso = (int) ($_POST['idCurso'] ?? ($_GET['idCurso'] ?? 0));
        $idUsuario = (int) ($_POST['idUsuario'] ?? 0);

        if ($idCurso <= 0 || $idUsuario <= 0) {
            $_SESSION['error_message'] = 'No se pudo identificar el curso o el estudiante.';
            return false;
        }

        $respuesta = ModeloCursos::mdlQuitarEstudianteCurso($idCurso, $idUsuario);
        if ($respuesta === 'ok') {
            $_SESSION['success_message'] = 'Estudiante quitado del curso correctamente';
        } else {
            $_SESSION['error_message'] = 'No se pudo quitar al estudiante del curso';
        }

        return $respuesta;
    }
}
