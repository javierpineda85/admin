<?php
require_once('modelos/notificaciones.modelo.php');

class ControladorNotificaciones
{
    public static function crtNotificarLeccionPublicada(array $leccion, array $seccion)
    {
        $idCurso = (int) ($seccion['id_curso'] ?? 0);
        $idLeccion = (int) ($leccion['idLeccion'] ?? 0);

        if ($idCurso <= 0 || $idLeccion <= 0) {
            return 0;
        }

        return self::notificarPublicacion([
            'tipoNotificacion' => 'LECCION_PUBLICADA',
            'referenciaTipo' => 'LECCION',
            'referenciaId' => $idLeccion,
            'tituloNotificacion' => 'Nueva leccion publicada',
            'detalleNotificacion' => (string) ($leccion['nombreLeccion'] ?? 'Nueva leccion') . ' en ' . (string) ($seccion['tituloSeccion'] ?? 'tu curso'),
            'urlNotificacion' => 'index.php?r=detalle-seccion&idSeccion=' . (int) ($seccion['idSeccion'] ?? 0)
                . '&abrirLeccion=' . $idLeccion . '#leccion-estudiante-' . $idLeccion,
            'asuntoEmail' => 'Nueva leccion publicada',
            'tituloEmail' => (string) ($leccion['nombreLeccion'] ?? 'Nueva leccion'),
            'contextoEmail' => (string) ($seccion['nombreCurso'] ?? 'Curso') . ' - ' . (string) ($seccion['tituloSeccion'] ?? 'Materia'),
            'urlEmail' => APP_BASE_URL . '/index.php?r=detalle-seccion&idSeccion=' . (int) ($seccion['idSeccion'] ?? 0)
                . '&abrirLeccion=' . $idLeccion . '#leccion-estudiante-' . $idLeccion,
        ], $idCurso);
    }

    public static function crtNotificarActividadPublicada(array $actividad)
    {
        $idCurso = (int) ($actividad['id_curso'] ?? 0);
        $idActividad = (int) ($actividad['idActividad'] ?? 0);

        if ($idCurso <= 0 || $idActividad <= 0) {
            return 0;
        }

        return self::notificarPublicacion([
            'tipoNotificacion' => 'ACTIVIDAD_PUBLICADA',
            'referenciaTipo' => 'ACTIVIDAD',
            'referenciaId' => $idActividad,
            'tituloNotificacion' => 'Nueva actividad publicada',
            'detalleNotificacion' => (string) ($actividad['tituloActividad'] ?? 'Nueva actividad') . ' en ' . (string) ($actividad['tituloSeccion'] ?? 'tu curso'),
            'urlNotificacion' => 'index.php?r=ver-actividad&idActividad=' . $idActividad,
            'asuntoEmail' => 'Nueva actividad publicada',
            'tituloEmail' => (string) ($actividad['tituloActividad'] ?? 'Nueva actividad'),
            'contextoEmail' => (string) ($actividad['nombreCurso'] ?? 'Curso') . ' - ' . (string) ($actividad['tituloSeccion'] ?? 'Materia'),
            'urlEmail' => APP_BASE_URL . '/index.php?r=ver-actividad&idActividad=' . $idActividad,
        ], $idCurso);
    }

    private static function notificarPublicacion(array $datos, $idCurso)
    {
        $estudiantes = ModeloNotificaciones::mdlBuscarEstudiantesPorCurso((int) $idCurso);
        $notificados = 0;

        foreach ($estudiantes as $estudiante) {
            $idUsuario = (int) ($estudiante['idUsuario'] ?? 0);
            if ($idUsuario <= 0) {
                continue;
            }

            $respuesta = ModeloNotificaciones::mdlRegistrarNotificacion([
                'id_usuario' => $idUsuario,
                'tipoNotificacion' => $datos['tipoNotificacion'],
                'referenciaTipo' => $datos['referenciaTipo'],
                'referenciaId' => (int) $datos['referenciaId'],
                'tituloNotificacion' => $datos['tituloNotificacion'],
                'detalleNotificacion' => $datos['detalleNotificacion'],
                'urlNotificacion' => $datos['urlNotificacion'],
            ]);

            if ($respuesta === 'ok') {
                $notificados++;
                self::enviarEmailPublicacion($estudiante, $datos);
            }
        }

        return $notificados;
    }

    private static function enviarEmailPublicacion(array $estudiante, array $datos)
    {
        $email = trim((string) ($estudiante['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $nombre = trim((string) ($estudiante['nombreUsuario'] ?? ''));
        $asunto = (string) ($datos['asuntoEmail'] ?? 'Nueva publicacion');
        $mensaje = "Hola " . ($nombre !== '' ? $nombre : 'estudiante') . ",\n\n"
            . "Se publico nuevo contenido en tu curso:\n\n"
            . (string) ($datos['tituloEmail'] ?? 'Nueva publicacion') . "\n"
            . (string) ($datos['contextoEmail'] ?? '') . "\n\n"
            . "Podes verlo desde este enlace:\n"
            . (string) ($datos['urlEmail'] ?? APP_BASE_URL) . "\n";

        $fromName = trim((string) MAIL_FROM_NAME);
        $fromEmail = trim((string) MAIL_FROM_EMAIL);
        $from = $fromName !== '' ? $fromName . ' <' . $fromEmail . '>' : $fromEmail;
        $cabeceras = implode("\r\n", [
            'MIME-Version: 1.0',
            'Content-type: text/plain; charset=UTF-8',
            'From: ' . $from,
        ]);

        return @mail($email, $asunto, $mensaje, $cabeceras);
    }
}
