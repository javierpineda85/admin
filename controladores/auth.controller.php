<?php

require_once 'modelos/usuarios.modelo.php';

class ControladorAuth
{
    private static function generarClaveTemporal($longitud = 10)
    {
        $longitud = max(8, (int) $longitud);
        $bytes = bin2hex(random_bytes((int) ceil($longitud / 2)));
        return strtoupper(substr($bytes, 0, $longitud));
    }

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

    public static function crtRecuperarPassword()
    {
        if (!isset($_POST['forgot_email'])) {
            return null;
        }

        $email = trim((string) $_POST['forgot_email']);
        if ($email === '') {
            $_SESSION['forgot_error'] = 'Completá tu correo electrónico.';
            return false;
        }

        $usuario = ModeloUsuarios::mdlObtenerUsuarioPorEmail($email);
        if (!$usuario || (int) ($usuario['activo'] ?? 0) !== 1) {
            $_SESSION['forgot_error'] = 'No encontramos una cuenta activa con ese correo.';
            return false;
        }

        $claveTemporal = self::generarClaveTemporal(10) . '!';
        $respuesta = ModeloUsuarios::mdlActualizarPassword(
            (int) $usuario['idUsuario'],
            password_hash($claveTemporal, PASSWORD_DEFAULT)
        );

        if ($respuesta !== 'ok') {
            $_SESSION['forgot_error'] = 'No se pudo generar la nueva contraseña.';
            return false;
        }

        $asunto = 'Classroom - Tu contraseña temporal';
        $mensaje = "Hola " . trim((string) $usuario['nombreUsuario']) . ",\n\n"
            . "Se generó una contraseña temporal para tu cuenta:\n\n"
            . $claveTemporal . "\n\n"
            . "Ingresá en http://localhost/admin/index.php?r=login y luego actualizala desde tu perfil.\n";
        $cabeceras = implode("\r\n", [
            'MIME-Version: 1.0',
            'Content-type: text/plain; charset=UTF-8',
            'From: Classroom <no-reply@classroom.local>',
        ]);
        @mail((string) $usuario['email'], $asunto, $mensaje, $cabeceras);

        $_SESSION['forgot_success'] = 'Generamos una contraseña temporal para tu cuenta.';
        $_SESSION['forgot_temp_password'] = $claveTemporal;
        return $claveTemporal;
    }
}
