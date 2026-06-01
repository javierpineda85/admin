<?php
require_once('modelos/perfiles.modelo.php');
require_once('modelos/usuarios.modelo.php');

class ControladorPerfiles
{

    static public function crtEditarPerfil()
    {
      
        if (isset($_POST["id_usuario"])) {
            $idUsuario = (int) $_POST["id_usuario"];
            $imagenPerfil = self::procesarImagenPerfil($idUsuario);
            if ($imagenPerfil === false) {
                return false;
            }

            $datos = array(
                "idUsuario" => $idUsuario,
                "fnac" => $_POST["fnacPerfil"],
                "domicilioPerfil" => $_POST["domicilioPerfil"],
                "contenidoPerfil" => $_POST["contenidoPerfil"]
            );

            $respuesta = ModeloPerfiles::mdlEditarPerfil($datos);
            if ($respuesta === 'ok') {
                if ($imagenPerfil !== '') {
                    ModeloUsuarios::mdlActualizarImagenUsuario($idUsuario, $imagenPerfil);
                }
                ModeloUsuarios::mdlRegistrarHistorial([
                    'id_usuario' => $idUsuario,
                    'accion' => 'PERFIL',
                    'detalle' => 'El usuario actualizó sus datos personales.',
                    'id_usuario_accion' => $idUsuario,
                    'fechaEvento' => date('Y-m-d H:i:s'),
                ]);
                $_SESSION['success_message'] = 'Perfil actualizado exitosamente';
            } else {
                $_SESSION['error_message'] = 'No se pudo actualizar el perfil';
            }
            return $respuesta;
        }
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
