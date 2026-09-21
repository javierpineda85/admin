<?php

class SeguridadArchivos
{
    private const TIPOS_IMAGEN = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    public static function validarImagenTemporal($rutaTemporal, $tamano, $maximoBytes = 5242880)
    {
        $rutaTemporal = (string) $rutaTemporal;
        $tamano = (int) $tamano;
        $maximoBytes = (int) $maximoBytes;

        if ($rutaTemporal === '' || !is_file($rutaTemporal) || $tamano <= 0 || $tamano > $maximoBytes) {
            throw new InvalidArgumentException('La imagen no es válida o supera el tamaño permitido.');
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($rutaTemporal);
        if (!isset(self::TIPOS_IMAGEN[$mime]) || @getimagesize($rutaTemporal) === false) {
            throw new InvalidArgumentException('El archivo debe ser una imagen JPG, PNG, GIF o WEBP válida.');
        }

        return self::TIPOS_IMAGEN[$mime];
    }

    public static function guardarImagenSubida(array $archivo, $directorio, $prefijo, $maximoBytes = 5242880)
    {
        if ((int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('No se pudo recibir la imagen.');
        }

        $temporal = (string) ($archivo['tmp_name'] ?? '');
        if ($temporal === '' || !is_uploaded_file($temporal)) {
            throw new InvalidArgumentException('El archivo recibido no es una carga válida.');
        }

        $extension = self::validarImagenTemporal(
            $temporal,
            (int) filesize($temporal),
            (int) $maximoBytes
        );

        $directorio = rtrim((string) $directorio, '/\\');
        if (!is_dir($directorio) && !mkdir($directorio, 0775, true) && !is_dir($directorio)) {
            throw new RuntimeException('No se pudo preparar la carpeta de imágenes.');
        }

        $prefijo = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $prefijo);
        $nombre = $prefijo . bin2hex(random_bytes(12)) . '.' . $extension;
        if (!move_uploaded_file($temporal, $directorio . DIRECTORY_SEPARATOR . $nombre)) {
            throw new RuntimeException('No se pudo guardar la imagen.');
        }

        return $nombre;
    }
}
