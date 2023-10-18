<?php
require_once('modelos/usuarios.modelo.php');

class ControladorUsuarios
{

    /*LISTADO USUARIO */
    static public function crtSeleccionarUsuario($item, $valor)
    {

        $tabla = "usuarios";
        $respuesta = ModeloUsuarios::mdlSeleccionarUsuario($tabla, $item, $valor);
        return $respuesta;

        exit;
    }



    /*GUARDAR USUARIOS */
    static public function crtGuardarUsuario()
    {
        if (isset($_POST["nombreUsuario"])) {
            $tabla = "usuarios";

            $datos = array(
                "nombreUsuario" => $_POST["nombreUsuario"],
                "apellidoUsuario" => $_POST["apellidoUsuario"],
                "email" => $_POST["email"],
                "pass" => password_hash($_POST["pass"],PASSWORD_DEFAULT),
                "resetPass" => 1,
                "imgUsuario" => "",
                "activo" => 1,
                "rol" => $_POST["rol"]
            );

            $respuesta = ModeloUsuarios::mdlGuardarUsuario($tabla,$datos);
            $_SESSION['success_message'] = 'Usuario creado exitosamente';
            return $respuesta;
        }
        
    }

}
