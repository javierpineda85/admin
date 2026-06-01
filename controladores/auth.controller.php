<?php

require_once 'modelos/usuarios.modelo.php';

class ControladorAuth
{
    static public function crtIniciarSesion()
    {
        if (!isset($_POST['login_email'], $_POST['login_pass'])) {
            return;
        }

        $email = trim($_POST['login_email']);
        $password = (string) $_POST['login_pass'];

        if ($email === '' || $password === '') {
            $_SESSION['login_error'] = 'Completa correo y contraseña.';
            return;
        }

        $usuario = ModeloUsuarios::mdlObtenerUsuarioPorEmail($email);

        if (!$usuario || (int) $usuario['activo'] !== 1) {
            $_SESSION['login_error'] = 'Usuario no encontrado o inactivo.';
            return;
        }

        $claveGuardada = (string) $usuario['pass'];
        $claveValida = password_verify($password, $claveGuardada) || hash_equals($claveGuardada, $password);

        if (!$claveValida) {
            $_SESSION['login_error'] = 'Credenciales incorrectas.';
            return;
        }

        if (password_get_info($claveGuardada)['algo'] === 0) {
            ModeloUsuarios::mdlActualizarPassword($usuario['idUsuario'], password_hash($password, PASSWORD_DEFAULT));
        }

        ModeloUsuarios::mdlActualizarUltimaConexion($usuario['idUsuario']);

        session_regenerate_id(true);

        $_SESSION['logueado'] = true;
        $_SESSION['usuario'] = [
            'id' => (int) $usuario['idUsuario'],
            'nombre' => $usuario['nombreUsuario'],
            'apellido' => $usuario['apellidoUsuario'],
            'email' => $usuario['email'],
            'rol' => $usuario['rol'],
            'img' => $usuario['imgUsuario'],
        ];

        unset($_SESSION['login_error']);

        header('Location: index.php');
        exit;
    }
}
