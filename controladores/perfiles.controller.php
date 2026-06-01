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
            if ($respuesta === 'ok') {
                $_SESSION['success_message'] = 'Perfil actualizado exitosamente';
            } else {
                $_SESSION['error_message'] = 'No se pudo actualizar el perfil';
            }
            return $respuesta;
        }
    }


}
