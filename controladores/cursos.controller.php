<?php
require_once('modelos/cursos.modelo.php');

class ControladorCursos
{

    /*LISTADO USUARIO */
    static public function crtSeleccionarCurso($item, $valor)
    {

        $tabla = "cursos";
        $respuesta = ModeloCursos::mdlSeleccionarCursos($tabla, $item, $valor);
        return $respuesta;

        exit;
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
            $_SESSION['success_message'] = 'Curso creado exitosamente';
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
            $_SESSION['success_message'] = 'Curso modificado exitosamente';
           return $respuesta;
            
        }
    }
}
