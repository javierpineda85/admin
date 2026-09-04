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
                "creadoPor"         => (int) ($_SESSION['usuario']['id'] ?? 0),
                "responsable"       => (int) ($_SESSION['usuario']['id'] ?? 0)
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

    static public function crtProcesarAdministracionCurso()
    {
        $accion = trim((string) ($_POST['accion_curso'] ?? ''));
        if (!in_array($accion, ['baja_curso', 'reactivar_curso', 'eliminar_curso', 'reasignar_docente_curso', 'quitar_docente_curso'], true)) {
            return null;
        }

        if (!ControladorPermisos::esAdministrador()) {
            $_SESSION['error_message'] = 'Solo el administrador puede dar de baja o eliminar cursos.';
            return 'denied';
        }

        $idCurso = (int) ($_POST['idCurso'] ?? 0);
        $curso = self::crtBuscarCursoPorId($idCurso);
        if (!$curso) {
            $_SESSION['error_message'] = 'El curso no existe.';
            return 'error';
        }

        if (in_array($accion, ['reasignar_docente_curso', 'quitar_docente_curso'], true)) {
            $idResponsable = $accion === 'quitar_docente_curso' ? 0 : (int) ($_POST['idResponsable'] ?? 0);
            if ($accion === 'reasignar_docente_curso') {
                $usuario = ModeloUsuarios::mdlObtenerUsuarioPorId($idResponsable);
                $rol = strtoupper((string) ($usuario['rol'] ?? ''));
                if (!$usuario || (int) ($usuario['activo'] ?? 0) !== 1 || !in_array($rol, ['DOCENTE', 'ADMINISTRADOR'], true)) {
                    $_SESSION['error_message'] = 'Selecciona un docente o administrador activo.';
                    return 'error';
                }
            }

            $respuesta = ModeloCursos::mdlActualizarResponsableCurso($idCurso, $idResponsable);
            $_SESSION[$respuesta === 'ok' ? 'success_message' : 'error_message'] = $respuesta === 'ok'
                ? ($idResponsable > 0 ? 'Responsable del curso reasignado correctamente.' : 'El curso quedo sin docente responsable.')
                : 'No se pudo actualizar el responsable del curso.';
            return $respuesta;
        }

        if ($accion === 'baja_curso') {
            $motivo = trim((string) ($_POST['motivoBaja'] ?? ''));
            if ($motivo === '') {
                $_SESSION['error_message'] = 'Indica el motivo de la baja.';
                return 'error';
            }
            $respuesta = ModeloCursos::mdlCambiarEstadoActivoCurso(
                $idCurso,
                0,
                $motivo,
                (int) ($_SESSION['usuario']['id'] ?? 0)
            );
            $_SESSION[$respuesta === 'ok' ? 'success_message' : 'error_message'] = $respuesta === 'ok'
                ? 'Curso dado de baja correctamente.'
                : 'No se pudo dar de baja el curso.';
            return $respuesta;
        }

        if ($accion === 'reactivar_curso') {
            $respuesta = ModeloCursos::mdlCambiarEstadoActivoCurso($idCurso, 1, '', 0);
            $_SESSION[$respuesta === 'ok' ? 'success_message' : 'error_message'] = $respuesta === 'ok'
                ? 'Curso reactivado correctamente.'
                : 'No se pudo reactivar el curso.';
            return $respuesta;
        }

        $dependencias = ModeloCursos::mdlDependenciasCurso($idCurso);
        if (!empty($dependencias)) {
            $_SESSION['error_message'] = 'No se puede eliminar el curso porque tiene secciones, estudiantes o actividad asociada. Podes darlo de baja.';
            return 'blocked';
        }

        $respuesta = ModeloCursos::mdlEliminarCurso($idCurso);
        $_SESSION[$respuesta === 'ok' ? 'success_message' : 'error_message'] = $respuesta === 'ok'
            ? 'Curso eliminado definitivamente.'
            : 'No se pudo eliminar el curso.';
        return $respuesta;
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
