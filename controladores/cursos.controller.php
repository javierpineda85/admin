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

    /*GUARDAR CURSO */
    static public function crtGuardarCurso()
    {
        if (isset($_POST["nombreCurso"])) {
            $tabla = "cursos";

            $datos = array(
                "nombreCurso"       => $_POST["nombreCurso"],
                "contenidoCurso"    => $_POST["contenidoCurso"],
                "estado"            => $_POST["estado"],
                "fechaInicioCurso"  => $_POST["fechaInicioCurso"],
                "fechaFinCurso"     => $_POST["fechaFinCurso"],
                "horarioCurso"      => $_POST["horarioCurso"]
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

            $tabla = "cursos";

            $datos = array(
                "idCurso"           => $_POST["idCurso"],
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
    static public function crtAsignarCurso()
    {
        if (isset($_POST["idUsuarios"])) {
            $tabla = 'asignacioncursos';
            $idCurso = $_GET["idCurso"];
            $idUsuarios = $_POST['idUsuarios'];
            $respuestaFinal = 'ok';
            
            foreach ($idUsuarios as $idUsuario) {
                $datos = array(
                    "idCurso"    => $idCurso,
                    "idUsuario"  => $idUsuario
                );
                
                $respuesta = ModeloCursos::mdlAsignarCurso($tabla, $datos);
                if ($respuesta !== 'ok') {
                    $respuestaFinal = 'error';
                }
            }
            
            if ($respuestaFinal === 'ok') {
                $_SESSION['success_message'] = 'Se han registrado a los estudiantes';
            } else {
                $_SESSION['error_message'] = 'No se pudieron registrar todos los estudiantes';
            }
           

        }
    }
}
