<?php

require_once('modelos/usuarios.modelo.php');
require_once('modelos/perfiles.modelo.php');

class ControladorUsuarios
{
    public static function crtSeleccionarUsuario($item, $valor)
    {
        return ModeloUsuarios::mdlSeleccionarUsuarios($item, $valor);
    }

    public static function crtDestinatariosPermitidos()
    {
        $idUsuarioActual = (int) ($_SESSION['usuario']['id'] ?? 0);
        $rolActual = $_SESSION['usuario']['rol'] ?? '';

        return ModeloUsuarios::mdlDestinatariosPermitidos($idUsuarioActual, $rolActual);
    }

    public static function crtUsuarioActual()
    {
        $idUsuarioActual = (int) ($_SESSION['usuario']['id'] ?? 0);
        if ($idUsuarioActual <= 0) {
            return null;
        }

        return ModeloUsuarios::mdlObtenerUsuarioCompleto($idUsuarioActual);
    }

    public static function crtUsuarioCompleto($idUsuario)
    {
        return ModeloUsuarios::mdlObtenerUsuarioCompleto((int) $idUsuario);
    }

    public static function crtRelacionesAcademicas($idUsuario)
    {
        return ModeloUsuarios::mdlRelacionesAcademicas((int) $idUsuario);
    }

    public static function crtGuardarUsuario()
    {
        if (!isset($_POST["nombreUsuario"], $_POST["apellidoUsuario"], $_POST["email"], $_POST["pass"], $_POST["rol"])) {
            return null;
        }

        try {
            $conexion = Conexion::conectar();
            if (!$conexion->inTransaction()) {
                $conexion->beginTransaction();
            }

            $tabla = "usuarios";
            $datosUsuario = [
                "nombreUsuario" => trim((string) $_POST["nombreUsuario"]),
                "apellidoUsuario" => trim((string) $_POST["apellidoUsuario"]),
                "email" => trim((string) $_POST["email"]),
                "pass" => password_hash((string) $_POST["pass"], PASSWORD_DEFAULT),
                "resetPass" => 1,
                "imgUsuario" => "",
                "activo" => 1,
                "rol" => trim((string) $_POST["rol"]),
                "fechaAlta" => date('Y-m-d H:i:s'),
            ];

            $respuestaUsuario = ModeloUsuarios::mdlGuardarUsuario($tabla, $datosUsuario);
            if ($respuestaUsuario !== 'ok') {
                $conexion->rollBack();
                $_SESSION['error_message'] = 'No se pudo crear el usuario';
                return false;
            }

            $idNuevoUsuario = (int) $conexion->lastInsertId();

            $datosPerfil = [
                "idUsuario" => $idNuevoUsuario,
                "dniPerfil" => $_POST['dniPerfil'] ?? null,
                "telefonoPerfil" => $_POST['telefonoPerfil'] ?? null,
                "fnacPerfil" => $_POST['fnacPerfil'] ?? null,
                "domicilioPerfil" => $_POST['domicilioPerfil'] ?? null,
                "provinciaPerfil" => $_POST['provinciaPerfil'] ?? null,
                "contenidoPerfil" => $_POST['contenidoPerfil'] ?? '',
            ];

            $respuestaPerfil = ModeloPerfiles::mdlGuardarPerfil($datosPerfil);
            if ($respuestaPerfil !== 'ok') {
                $conexion->rollBack();
                $_SESSION['error_message'] = 'No se pudo crear el perfil del usuario';
                return false;
            }

            $conexion->commit();
            $_SESSION['success_message'] = 'Usuario y perfil creados exitosamente';
            return 'ok';
        } catch (Exception $e) {
            if ($conexion instanceof PDO && $conexion->inTransaction()) {
                $conexion->rollBack();
            }

            $_SESSION['error_message'] = $e->getMessage();
            return false;
        }
    }

    public static function crtModificarUsuario()
    {
        if (!isset($_POST["nombreUsuario"], $_POST["apellidoUsuario"], $_POST["emailUsuario"], $_POST["rol"])) {
            return null;
        }

        $tabla = "usuarios";
        $datos = [
            "idUsuario" => (int) ($_POST["idUsuario"] ?? 0),
            "nombreUsuario" => trim((string) $_POST["nombreUsuario"]),
            "apellidoUsuario" => trim((string) $_POST["apellidoUsuario"]),
            "emailUsuario" => trim((string) $_POST["emailUsuario"]),
            "rol" => trim((string) $_POST["rol"]),
        ];

        if (!empty($_POST["passUsuario"])) {
            $datos["passUsuario"] = (string) $_POST["passUsuario"];
        }

        $respuesta = ModeloUsuarios::mdlModificarUsuario($tabla, $datos);
        if ($respuesta === 'ok') {
            $_SESSION['success_message'] = 'Usuario modificado exitosamente';
        } else {
            $_SESSION['error_message'] = 'No se pudo modificar el usuario';
        }

        return $respuesta;
    }

    public static function crtDarBajaUsuario()
    {
        if (!isset($_POST['accion_usuario']) || $_POST['accion_usuario'] !== 'baja_usuario') {
            return null;
        }

        $datos = [
            'idUsuario' => (int) ($_POST['idUsuario'] ?? 0),
            'fechaBaja' => !empty($_POST['fechaBaja']) ? $_POST['fechaBaja'] . ' 00:00:00' : date('Y-m-d H:i:s'),
            'motivoBaja' => trim((string) ($_POST['motivoBaja'] ?? '')),
            'usuarioBaja' => (int) ($_SESSION['usuario']['id'] ?? 0),
        ];

        if ($datos['idUsuario'] <= 0 || $datos['motivoBaja'] === '') {
            $_SESSION['error_message'] = 'Completa la fecha y el motivo de baja.';
            return false;
        }

        $respuesta = ModeloUsuarios::mdlDarBajaUsuario($datos);
        if ($respuesta === 'ok') {
            $_SESSION['success_message'] = 'Usuario dado de baja correctamente';
        } else {
            $_SESSION['error_message'] = 'No se pudo dar de baja al usuario';
        }

        return $respuesta;
    }

    public static function crtReactivarUsuario($idUsuario)
    {
        $respuesta = ModeloUsuarios::mdlReactivarUsuario((int) $idUsuario);
        if ($respuesta === 'ok') {
            $_SESSION['success_message'] = 'Usuario reactivado correctamente';
        } else {
            $_SESSION['error_message'] = 'No se pudo reactivar al usuario';
        }

        return $respuesta;
    }
}
