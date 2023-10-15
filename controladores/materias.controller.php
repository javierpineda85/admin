<?php
require('modelos/materias.modelo.php');


class ControladorMaterias
{
    static public function crtGuardarMateria()
    {
        if (isset($_POST["tituloSeccion"])) {
            $tabla = "secciones";

            $datos = array(
                "tituloSeccion" => $_POST["tituloSeccion"],
                "contenidoSeccion" => $_POST["contenidoSeccion"],
                "id_curso" => $_POST["id_curso"],
                "docente" => $_POST["docente"],
                "tutor" => $_POST["tutor"]
            );
            
            $respuesta = ModeloMaterias::mdlGuardarMateria($tabla, $datos);
            $_SESSION['success_message'] = 'Materia creada exitosamente';
            return $respuesta;
        }
    }

    static public function crtSeleccionarMateria($item,$valor){
        $tabla = "secciones";
        //$item = 'join';
        $valor = NULL;
        $respuesta = ModeloMaterias::mdlSeleccionarmateria($tabla, $item, $valor);
        return $respuesta;

        exit;
    }

    
}
