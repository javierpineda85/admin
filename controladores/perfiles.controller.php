<?php
require_once('modelos/perfiles.modelo.php');
require_once('modelos/usuarios.modelo.php');

class ControladorPerfiles
{
    static public function crtEditarPerfil()
    {
        if (!isset($_POST["id_usuario"])) {
            return null;
        }

        $idUsuario = (int) $_POST["id_usuario"];
        $idUsuarioSesion = (int) ($_SESSION['usuario']['id'] ?? 0);
        $puedeCambiarClave = $idUsuarioSesion > 0 && $idUsuarioSesion === $idUsuario;

        $cambiarClave = $puedeCambiarClave
            && trim((string) ($_POST['passActual'] ?? '')) !== ''
            && trim((string) ($_POST['passNueva'] ?? '')) !== ''
            && trim((string) ($_POST['passNuevaConfirmar'] ?? '')) !== '';

        if ($cambiarClave) {
            $usuarioActual = ModeloUsuarios::mdlObtenerUsuarioPorId($idUsuario);
            $passActual = (string) $_POST['passActual'];
            $passNueva = (string) $_POST['passNueva'];
            $passNuevaConfirmar = (string) $_POST['passNuevaConfirmar'];

            if (!$usuarioActual || !password_verify($passActual, (string) ($usuarioActual['pass'] ?? ''))) {
                $_SESSION['error_message'] = 'La contrasena actual no es correcta.';
                return false;
            }

            if (strlen($passNueva) < 8) {
                $_SESSION['error_message'] = 'La nueva contrasena debe tener al menos 8 caracteres.';
                return false;
            }

            if ($passNueva !== $passNuevaConfirmar) {
                $_SESSION['error_message'] = 'La nueva contrasena y su confirmacion no coinciden.';
                return false;
            }
        }

        $imagenPerfil = self::procesarImagenPerfil($idUsuario);
        if ($imagenPerfil === false) {
            return false;
        }

        $datos = array(
            "idUsuario" => $idUsuario,
            "dniPerfil" => $_POST["dniPerfil"] ?? null,
            "telefonoPerfil" => $_POST["telefonoPerfil"] ?? null,
            "fnacPerfil" => $_POST["fnacPerfil"] ?? null,
            "domicilioPerfil" => $_POST["domicilioPerfil"] ?? null,
            "provinciaPerfil" => $_POST["provinciaPerfil"] ?? null,
            "contenidoPerfil" => $_POST["contenidoPerfil"] ?? ''
        );

        $conexion = Conexion::conectar();
        if ($conexion instanceof PDO && !$conexion->inTransaction()) {
            $conexion->beginTransaction();
        }

        $respuesta = ModeloPerfiles::mdlEditarPerfil($datos);
        if ($respuesta !== 'ok') {
            if ($conexion instanceof PDO && $conexion->inTransaction()) {
                $conexion->rollBack();
            }
            $_SESSION['error_message'] = 'No se pudo actualizar el perfil';
            return $respuesta;
        }

        if ($imagenPerfil !== '') {
            $respuestaImagen = ModeloUsuarios::mdlActualizarImagenUsuario($idUsuario, $imagenPerfil);
            if ($respuestaImagen !== 'ok') {
                if ($conexion instanceof PDO && $conexion->inTransaction()) {
                    $conexion->rollBack();
                }
                $_SESSION['error_message'] = 'No se pudo guardar la foto de perfil.';
                return false;
            }
        }

        if ($cambiarClave) {
            $respuestaClave = ModeloUsuarios::mdlActualizarPassword($idUsuario, password_hash((string) $_POST['passNueva'], PASSWORD_DEFAULT));
            if ($respuestaClave !== 'ok') {
                if ($conexion instanceof PDO && $conexion->inTransaction()) {
                    $conexion->rollBack();
                }
                $_SESSION['error_message'] = 'No se pudo actualizar la contrasena.';
                return false;
            }
        }

        ModeloUsuarios::mdlRegistrarHistorial([
            'id_usuario' => $idUsuario,
            'accion' => 'PERFIL',
            'detalle' => 'El usuario actualizo sus datos personales.' . ($cambiarClave ? ' Tambien cambio su contrasena.' : ''),
            'id_usuario_accion' => $idUsuarioSesion > 0 ? $idUsuarioSesion : $idUsuario,
            'fechaEvento' => date('Y-m-d H:i:s'),
        ]);

        if ($conexion instanceof PDO && $conexion->inTransaction()) {
            $conexion->commit();
        }

        $_SESSION['success_message'] = $cambiarClave
            ? 'Perfil y contrasena actualizados exitosamente'
            : 'Perfil actualizado exitosamente';

        return $respuesta;
    }

    private static function procesarImagenPerfil($idUsuario)
    {
        if (empty($_FILES['imgUsuario']['name'])) {
            return '';
        }

        if ((int) ($_FILES['imgUsuario']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $_SESSION['error_message'] = 'No se pudo subir la foto de perfil.';
            return false;
        }

        $carpeta = __DIR__ . '/../img/usuarios/';
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0777, true);
        }

        $original = basename((string) $_FILES['imgUsuario']['name']);
        $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $nombreArchivo = 'perfil_' . $idUsuario . '_' . uniqid('', true) . ($extension !== '' ? '.' . $extension : '');
        $rutaCompleta = $carpeta . $nombreArchivo;

        if (!move_uploaded_file($_FILES['imgUsuario']['tmp_name'], $rutaCompleta)) {
            $_SESSION['error_message'] = 'No se pudo guardar la foto de perfil.';
            return false;
        }

        return 'usuarios/' . $nombreArchivo;
    }
}
