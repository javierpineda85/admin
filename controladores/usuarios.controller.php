<?php
require_once('modelos/usuarios.modelo.php');
require_once('modelos/perfiles.modelo.php');
class ControladorUsuarios
{

    /*GUARDAR USUARIOS */
    static public function crtGuardarUsuario()
    {
        if (isset($_POST["nombreUsuario"])) {

            try {
                $conexion = Conexion::conectar();

                // Verificar si ya hay una transacción activa
                if (!$conexion->inTransaction()) {
                    // Si no hay una transacción activa, iniciar una nueva
                    $conexion->beginTransaction();
                }

                // Guardar el usuario
                $tabla = "usuarios";

                $datosUsuario = array(
                    "nombreUsuario" => $_POST["nombreUsuario"],
                    "apellidoUsuario" => $_POST["apellidoUsuario"],
                    "email" => $_POST["email"],
                    "pass" => password_hash($_POST["pass"], PASSWORD_DEFAULT),
                    "resetPass" => 1,
                    "imgUsuario" => "",
                    "activo" => 1,
                    "rol" => $_POST["rol"]
                );

                ModeloUsuarios::mdlGuardarUsuario($tabla, $datosUsuario);

                // Obtener el ID del usuario dentro de la misma transacción
                
                $idUsuario = Conexion::conectar()->prepare("SELECT MAX(idUsuario) as maxId FROM usuarios");
                $idUsuario ->execute();
                $id=$idUsuario->fetch(PDO::FETCH_ASSOC);
                // Guardar el perfil
                $tabla = "perfiles";
                $datosPerfil = array(
                    "idUsuario" => $id['maxId'],
                    "dniPerfil" => $_POST['dniPerfil'],
                    "telefonoPerfil" => $_POST['telefonoPerfil'],
                    "fnacPerfil" => $_POST['fnacPerfil'],
                    "domicilioPerfil" => $_POST['domicilioPerfil'],
                    "provinciaPerfil" => $_POST['provinciaPerfil']
                );

                $respuesta1 = ModeloPerfiles::mdlGuardarPerfil($datosPerfil);

                // Confirmar la transacción si no hay errores
                Conexion::conectar()->commit();

                $_SESSION['success_message'] = 'Usuario y perfil creados exitosamente';

                return $respuesta1;
            } catch (Exception $e) {
                // Revertir la transacción en caso de error
                Conexion::conectar()->rollBack();

                // Manejar el error según sea necesario
                $_SESSION['success_message'] =  $e->getMessage();

                return false;
            }
        }
    }


}
