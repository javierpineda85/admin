<?php

/** Presentación y cabeceras compartidas de los correos del Campus. */
class CorreoCampus
{
    public static function remitente()
    {
        $email = trim((string) MAIL_FROM_EMAIL);
        $nombre = trim(str_replace(["\r", "\n"], '', (string) MAIL_FROM_NAME));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $email)) {
            throw new InvalidArgumentException('MAIL_FROM_EMAIL debe ser una dirección de correo válida.');
        }
        return $nombre === '' ? $email : '=?UTF-8?B?' . base64_encode($nombre) . '?= <' . $email . '>';
    }

    public static function publicacionHtml(array $estudiante, array $datos)
    {
        $e = static function ($valor) { return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8'); };
        $nombre = trim((string) ($estudiante['nombreUsuario'] ?? '')) ?: 'estudiante';
        $url = (string) ($datos['urlEmail'] ?? APP_BASE_URL);
        if (!filter_var($url, FILTER_VALIDATE_URL) || !in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            throw new InvalidArgumentException('La URL del correo debe usar HTTP o HTTPS.');
        }
        ob_start();
        require __DIR__ . '/../vistas/correos/publicacion.php';
        return ob_get_clean();
    }

    public static function recuperacionHtml(array $usuario, $url)
    {
        $url = (string) $url;
        if (!filter_var($url, FILTER_VALIDATE_URL) || strtolower((string) parse_url($url, PHP_URL_SCHEME)) !== 'https') {
            throw new InvalidArgumentException('La URL de recuperación debe usar HTTPS.');
        }
        $nombre = trim((string) ($usuario['nombreUsuario'] ?? '')) ?: 'usuario';
        ob_start();
        require __DIR__ . '/../vistas/correos/recuperacion-password.php';
        return ob_get_clean();
    }
}
