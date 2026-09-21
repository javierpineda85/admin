<?php

class SeguridadArchivos
{
    private const TIPOS_IMAGEN = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    private const TIPOS_ADJUNTO = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'odt' => ['application/vnd.oasis.opendocument.text', 'application/zip'],
        'xls' => ['application/vnd.ms-excel', 'application/octet-stream'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'ods' => ['application/vnd.oasis.opendocument.spreadsheet', 'application/zip'],
        'csv' => ['text/csv', 'text/plain', 'application/vnd.ms-excel'],
        'ppt' => ['application/vnd.ms-powerpoint', 'application/octet-stream'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
        'odp' => ['application/vnd.oasis.opendocument.presentation', 'application/zip'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'webp' => ['image/webp'],
        'txt' => ['text/plain'],
        'zip' => ['application/zip', 'application/x-zip-compressed'],
        'rar' => ['application/vnd.rar', 'application/x-rar', 'application/x-rar-compressed'],
        '7z' => ['application/x-7z-compressed'],
        'mp3' => ['audio/mpeg', 'audio/mp3'],
        'mp4' => ['video/mp4', 'application/mp4'],
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

    public static function validarAdjuntoTemporal($rutaTemporal, $tamano, $nombreOriginal, array $extensionesPermitidas, $maximoBytes = 10485760)
    {
        $rutaTemporal = (string) $rutaTemporal;
        $tamano = (int) $tamano;
        $nombreOriginal = basename(str_replace('\\', '/', trim((string) $nombreOriginal)));
        $extension = strtolower((string) pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        $permitidas = array_values(array_unique(array_map('strtolower', $extensionesPermitidas)));

        if ($rutaTemporal === '' || !is_file($rutaTemporal) || $tamano <= 0 || $tamano > (int) $maximoBytes) {
            throw new InvalidArgumentException('El archivo no es válido o supera los 10 MB permitidos.');
        }
        if ($nombreOriginal === '' || strlen($nombreOriginal) > 180 || preg_match('/[\x00-\x1F\x7F]/', $nombreOriginal)) {
            throw new InvalidArgumentException('El nombre del archivo no es válido.');
        }
        if ($extension === '' || !in_array($extension, $permitidas, true) || !isset(self::TIPOS_ADJUNTO[$extension])) {
            throw new InvalidArgumentException('El tipo de archivo no está permitido.');
        }

        $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($rutaTemporal);
        if (!in_array($mime, self::TIPOS_ADJUNTO[$extension], true)) {
            throw new InvalidArgumentException('El contenido del archivo no coincide con su extensión.');
        }
        if (isset(self::TIPOS_IMAGEN[$mime]) && @getimagesize($rutaTemporal) === false) {
            throw new InvalidArgumentException('La imagen adjunta no es válida.');
        }

        return [
            'nombreOriginal' => $nombreOriginal,
            'extension' => $extension,
            'mimeType' => $mime,
            'tamanoArchivo' => $tamano,
        ];
    }

    public static function guardarAdjuntoSubido(array $archivo, $directorio, $prefijo, array $extensionesPermitidas, $maximoBytes = 10485760)
    {
        if ((int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('No se pudo recibir el archivo.');
        }
        $temporal = (string) ($archivo['tmp_name'] ?? '');
        if ($temporal === '' || !is_uploaded_file($temporal)) {
            throw new InvalidArgumentException('El archivo recibido no es una carga válida.');
        }

        $datos = self::validarAdjuntoTemporal(
            $temporal,
            (int) filesize($temporal),
            (string) ($archivo['name'] ?? ''),
            $extensionesPermitidas,
            (int) $maximoBytes
        );
        $directorio = rtrim((string) $directorio, '/\\');
        if (!is_dir($directorio) && !mkdir($directorio, 0775, true) && !is_dir($directorio)) {
            throw new RuntimeException('No se pudo preparar la carpeta de archivos.');
        }
        $prefijo = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $prefijo);
        $nombre = $prefijo . bin2hex(random_bytes(12)) . '.' . $datos['extension'];
        if (!move_uploaded_file($temporal, $directorio . DIRECTORY_SEPARATOR . $nombre)) {
            throw new RuntimeException('No se pudo guardar el archivo.');
        }
        $datos['nombreGuardado'] = $nombre;
        return $datos;
    }
}
