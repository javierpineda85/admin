<?php
require_once('modelos/perfiles.modelo.php');

class ControladorPerfiles
{

    static public function crtGuardarPerfil()
    {
        if (isset($_POST["id_usuario"])) {

            $datos = array(
                "fnac" => $_POST["fnac"],
                "domicilioPerfil" => $_POST["domicilioPerfil"],
                "contenidoPerfil" => $_POST["contenidoPerfil"]
            );
var_dump($datos);
            $respuesta = ModeloPerfiles::mdlGuardarPerfil($datos);
            $_SESSION['success_message'] = 'Usuario creado exitosamente';
            return $respuesta;
        }
    }

    static public function crtSeleccionarPerfil($item, $valor)
    {

        $respuesta = ModeloPerfiles::mdlSeleccionarPerfil($item, $valor);
        return $respuesta;

        exit;
    }
}
