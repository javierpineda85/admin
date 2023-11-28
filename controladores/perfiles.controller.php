<?php
require_once('modelos/perfiles.modelo.php');

class ControladorPerfiles
{

    static public function crtEditarPerfil()
    {
      
        if (isset($_POST["id_usuario"])) {

            $datos = array(
                "idUsuario" => $_POST["id_usuario"],
                "fnac" => $_POST["fnacPerfil"],
                "domicilioPerfil" => $_POST["domicilioPerfil"],
                "contenidoPerfil" => $_POST["contenidoPerfil"]
            );

            $respuesta = ModeloPerfiles::mdlEditarPerfil($datos);
            $_SESSION['success_message'] = 'Perfil actualizado exitosamente';
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
