<?php

require_once('modelos/usuarios.modelo.php');
require_once('modelos/perfiles.modelo.php');

class ControladorUsuarios
{
    private static function procesarImagenUsuario($campo = 'imgUsuario', $nombreBase = 'usuario')
    {
        if (empty($_FILES[$campo]['name'])) {
            return '';
        }

        if ((int) ($_FILES[$campo]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $_SESSION['error_message'] = 'No se pudo subir la imagen del usuario.';
            return false;
        }

        $carpeta = __DIR__ . '/../img/usuarios/';
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0777, true);
        }

        $original = basename((string) $_FILES[$campo]['name']);
        $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $nombreArchivo = $nombreBase . '_' . uniqid('', true) . ($extension !== '' ? '.' . $extension : '');
        $rutaCompleta = $carpeta . $nombreArchivo;

        if (!move_uploaded_file($_FILES[$campo]['tmp_name'], $rutaCompleta)) {
            $_SESSION['error_message'] = 'No se pudo guardar la imagen del usuario.';
            return false;
        }

        return 'usuarios/' . $nombreArchivo;
    }

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

    public static function crtHistorialUsuario($idUsuario)
    {
        return ModeloUsuarios::mdlHistorialUsuario((int) $idUsuario);
    }

    public static function crtUsuariosConectadosRecientes($minutos = 60)
    {
        return ModeloUsuarios::mdlUsuariosConectadosRecientes((int) $minutos);
    }

    public static function crtUsuariosNoConectadosRecientes($minutos = 60)
    {
        return ModeloUsuarios::mdlUsuariosNoConectadosRecientes((int) $minutos);
    }

    public static function crtContarUsuariosConectadosRecientes($minutos = 60)
    {
        return ModeloUsuarios::mdlContarUsuariosConectadosRecientes((int) $minutos);
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
                "activo" => 1,
                "rol" => trim((string) $_POST["rol"]),
                "fechaAlta" => date('Y-m-d H:i:s'),
            ];

            $imagen = self::procesarImagenUsuario('imgUsuario', 'alta');
            if ($imagen === false) {
                $conexion->rollBack();
                return false;
            }
            $datosUsuario['imgUsuario'] = $imagen !== '' ? $imagen : '';

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

            ModeloUsuarios::mdlRegistrarHistorial([
                'id_usuario' => $idNuevoUsuario,
                'accion' => 'ALTA',
                'detalle' => 'Se creó el usuario y su perfil inicial.',
                'id_usuario_accion' => (int) ($_SESSION['usuario']['id'] ?? 0),
                'fechaEvento' => date('Y-m-d H:i:s'),
            ]);

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

        $imagen = self::procesarImagenUsuario('imgUsuario', 'perfil');
        if ($imagen === false) {
            return false;
        }
        if ($imagen !== '') {
            $datos['imgUsuario'] = $imagen;
        }

        if (!empty($_POST["passUsuario"])) {
            $datos["passUsuario"] = (string) $_POST["passUsuario"];
        }

        $respuesta = ModeloUsuarios::mdlModificarUsuario($tabla, $datos);
        if ($respuesta === 'ok') {
            ModeloUsuarios::mdlRegistrarHistorial([
                'id_usuario' => $datos['idUsuario'],
                'accion' => 'MODIFICACION',
                'detalle' => 'Se actualizaron datos de cuenta o perfil.',
                'id_usuario_accion' => (int) ($_SESSION['usuario']['id'] ?? 0),
                'fechaEvento' => date('Y-m-d H:i:s'),
            ]);
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
            ModeloUsuarios::mdlRegistrarHistorial([
                'id_usuario' => $datos['idUsuario'],
                'accion' => 'BAJA',
                'detalle' => 'Usuario dado de baja. Motivo: ' . $datos['motivoBaja'],
                'id_usuario_accion' => (int) ($_SESSION['usuario']['id'] ?? 0),
                'fechaEvento' => date('Y-m-d H:i:s'),
            ]);
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
            ModeloUsuarios::mdlRegistrarHistorial([
                'id_usuario' => (int) $idUsuario,
                'accion' => 'REACTIVACION',
                'detalle' => 'Usuario reactivado desde el listado de inactivos.',
                'id_usuario_accion' => (int) ($_SESSION['usuario']['id'] ?? 0),
                'fechaEvento' => date('Y-m-d H:i:s'),
            ]);
            $_SESSION['success_message'] = 'Usuario reactivado correctamente';
        } else {
            $_SESSION['error_message'] = 'No se pudo reactivar al usuario';
        }

        return $respuesta;
    }
}
