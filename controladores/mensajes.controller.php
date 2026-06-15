<?php
require_once('modelos/usuarios.modelo.php');
require_once('modelos/mensajes.modelo.php');

class ControladorMensajes
{
    private static function idUsuarioActual()
    {
        return (int) ($_SESSION['usuario']['id'] ?? 0);
    }

    private static function rolActual()
    {
        return ControladorPermisos::rolActual();
    }

    private static function limpiarMensaje($mensaje)
    {
        $mensaje = trim((string) $mensaje);
        return $mensaje;
    }

    public static function crtDestinatariosPermitidos()
    {
        return ModeloMensajes::mdlUsuariosPermitidosParaMensajes(self::idUsuarioActual(), self::rolActual());
    }

    public static function crtSeccionesDisponibles()
    {
        return ModeloMensajes::mdlSeccionesParaMensajes(self::idUsuarioActual(), self::rolActual());
    }

    public static function crtMostrarMensajes($item, $valor)
    {
        return ModeloMensajes::mdlMostrarMensajes($item, $valor);
    }

    public static function crtMostrarMensajesEnviados($item, $valor)
    {
        return ModeloMensajes::mdlMostrarMensajesEnviados($item, $valor);
    }

    public static function crtMostrarPapelera()
    {
        return ModeloMensajes::mdlMensajesPapelera(self::idUsuarioActual());
    }

    public static function crtMostrarUnMensaje($id)
    {
        return ModeloMensajes::mdlMensajeDetalle((int) $id, self::idUsuarioActual());
    }

    public static function crtContarMensajesRecibidos($idUsuario)
    {
        return ModeloMensajes::mdlContarMensajesRecibidos($idUsuario);
    }

    public static function crtContarMensajesEnviados($idUsuario)
    {
        return ModeloMensajes::mdlContarMensajesEnviados($idUsuario);
    }

    public static function crtContarMensajesPapelera($idUsuario)
    {
        return ModeloMensajes::mdlContarMensajesPapelera($idUsuario);
    }

    public static function crtContarMensajesNoLeidos($idUsuario)
    {
        return ModeloMensajes::mdlContarMensajesNoLeidos($idUsuario);
    }

    public static function crtMensajesRecientesRecibidos($idUsuario, $limite = 5)
    {
        return ModeloMensajes::mdlMensajesRecientesRecibidos($idUsuario, $limite);
    }

    public static function crtMensajesRecientesEnviados($idUsuario, $limite = 5)
    {
        return ModeloMensajes::mdlMensajesRecientesEnviados($idUsuario, $limite);
    }

    public static function crtVerMensaje($idMensaje)
    {
        $idUsuario = self::idUsuarioActual();
        $mensaje = ModeloMensajes::mdlMensajeDetalle((int) $idMensaje, $idUsuario);

        if ($mensaje && ($mensaje['rolParticipante'] ?? '') === 'DESTINATARIO' && (int) ($mensaje['leido'] ?? 0) === 0) {
            ModeloMensajes::mdlMarcarLeido((int) $idMensaje, $idUsuario);
            $mensaje = ModeloMensajes::mdlMensajeDetalle((int) $idMensaje, $idUsuario);
        }

        return $mensaje;
    }

    public static function crtProcesarAccion()
    {
        $accion = trim((string) ($_POST['accion'] ?? $_GET['accion'] ?? ''));
        if ($accion === '') {
            return null;
        }

        switch ($accion) {
            case 'enviar_mensaje':
                return self::crtGuardarMensaje();
            case 'marcar_leido':
                return self::crtMarcarLeido();
            case 'marcar_no_leido':
                return self::crtMarcarNoLeido();
            case 'mover_papelera':
                return self::crtMoverPapelera();
            case 'restaurar_mensaje':
                return self::crtRestaurarMensaje();
            case 'eliminar_permanente':
                return self::crtEliminarPermanente();
            default:
                return null;
        }
    }

    public static function crtGuardarMensaje()
    {
        $idRemitente = self::idUsuarioActual();
        $rolRemitente = self::rolActual();
        $contenido = self::limpiarMensaje($_POST['contenidoMensaje'] ?? '');
        $destinatarios = $_POST['id_destinatarios'] ?? [];
        $seccionDestino = (int) ($_POST['id_seccion_destino'] ?? 0);

        if ($idRemitente <= 0 || $contenido === '') {
            $_SESSION['error_message'] = 'Completa el mensaje antes de enviarlo.';
            return false;
        }

        $destinatarios = array_values(array_unique(array_filter(array_map('intval', (array) $destinatarios))));
        $destinatarios = array_values(array_filter($destinatarios, static function ($id) use ($idRemitente) {
            return $id > 0 && $id !== $idRemitente;
        }));

        if ($seccionDestino > 0 && in_array($rolRemitente, ['ADMINISTRADOR', 'DOCENTE'], true)) {
            $destinatariosSeccion = ModeloMensajes::mdlDestinatariosDeSeccion($seccionDestino);
            $destinatarios = array_values(array_unique(array_merge($destinatarios, $destinatariosSeccion)));
        }

        if (empty($destinatarios)) {
            $_SESSION['error_message'] = 'Selecciona al menos un destinatario o una seccion.';
            return false;
        }

        $usuarioRemitente = ModeloUsuarios::mdlObtenerUsuarioPorId($idRemitente);
        if (!$usuarioRemitente) {
            $_SESSION['error_message'] = 'No se pudo validar el remitente.';
            return false;
        }

        $usuariosPermitidos = ModeloMensajes::mdlUsuariosPermitidosParaMensajes($idRemitente, $rolRemitente);
        $mapaPermitidos = [];
        foreach ($usuariosPermitidos as $usuario) {
            $mapaPermitidos[(int) $usuario['idUsuario']] = strtoupper(trim((string) ($usuario['rol'] ?? '')));
        }

        foreach ($destinatarios as $idDestinatario) {
            $rolDestinatario = $mapaPermitidos[$idDestinatario] ?? null;
            $usuarioDestinatario = ModeloUsuarios::mdlObtenerUsuarioPorId($idDestinatario);

            if (!$usuarioDestinatario) {
                $_SESSION['error_message'] = 'Hay destinatarios que no existen o no están activos.';
                return false;
            }

            if ($rolRemitente === 'ESTUDIANTE') {
                if (in_array($rolDestinatario, ['DOCENTE', 'ADMINISTRADOR'], true) && ModeloUsuarios::mdlDocenteTutorDeCursosEstudiante($idRemitente, $idDestinatario)) {
                    continue;
                }

                if ($rolDestinatario !== 'ESTUDIANTE') {
                    $_SESSION['error_message'] = 'Los estudiantes solo pueden escribir a estudiantes de su curso o a sus docentes/tutores.';
                    return false;
                }

                if (!ModeloUsuarios::mdlCompartenCurso($idRemitente, $idDestinatario)) {
                    $_SESSION['error_message'] = 'Solo podés escribir a estudiantes de tu mismo curso.';
                    return false;
                }
            }
        }

        $adjuntos = self::crtProcesarAdjuntos();
        if ($adjuntos === false) {
            return false;
        }

        $respuesta = ModeloMensajes::mdlGuardarMensaje([
            'id_remitente' => $idRemitente,
            'contenidoMensaje' => $contenido,
            'fechaMensaje' => date('Y-m-d H:i:s'),
            'destinatarios' => $destinatarios,
            'adjuntos' => $adjuntos,
        ]);

        if (is_int($respuesta) && $respuesta > 0) {
            $_SESSION['success_message'] = 'Mensaje enviado exitosamente';
            return $respuesta;
        }

        foreach ($adjuntos as $adjunto) {
            if (!empty($adjunto['rutaCompleta']) && is_file($adjunto['rutaCompleta'])) {
                @unlink($adjunto['rutaCompleta']);
            }
        }

        $_SESSION['error_message'] = 'No se pudo enviar el mensaje';
        return false;
    }

    private static function crtProcesarAdjuntos()
    {
        if (empty($_FILES['adjuntos']) || !is_array($_FILES['adjuntos']['name'] ?? null)) {
            return [];
        }

        $carpeta = __DIR__ . '/../uploads/mensajes/';
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0777, true);
        }

        $permitidos = [];
        $nombres = $_FILES['adjuntos']['name'];
        $tipos = $_FILES['adjuntos']['type'];
        $tmpNames = $_FILES['adjuntos']['tmp_name'];
        $errores = $_FILES['adjuntos']['error'];
        $tamanos = $_FILES['adjuntos']['size'];
        $archivosGuardados = [];

        for ($i = 0, $total = count($nombres); $i < $total; $i++) {
            if (($errores[$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if (($errores[$i] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || !is_uploaded_file($tmpNames[$i])) {
                $_SESSION['error_message'] = 'Uno de los adjuntos no se pudo subir correctamente.';
                foreach ($archivosGuardados as $rutaCompleta) {
                    if (is_file($rutaCompleta)) {
                        @unlink($rutaCompleta);
                    }
                }
                return false;
            }

            $original = basename((string) $nombres[$i]);
            $extension = pathinfo($original, PATHINFO_EXTENSION);
            $nombreGuardado = uniqid('msg_', true) . ($extension !== '' ? '.' . strtolower($extension) : '');
            $rutaRelativa = 'uploads/mensajes/' . $nombreGuardado;
            $rutaCompleta = $carpeta . $nombreGuardado;

            if (!move_uploaded_file($tmpNames[$i], $rutaCompleta)) {
                $_SESSION['error_message'] = 'No se pudo guardar uno de los adjuntos.';
                foreach ($archivosGuardados as $rutaAnterior) {
                    if (is_file($rutaAnterior)) {
                        @unlink($rutaAnterior);
                    }
                }
                return false;
            }

            $archivosGuardados[] = $rutaCompleta;

            $permitidos[] = [
                'nombreOriginal' => $original,
                'nombreGuardado' => $nombreGuardado,
                'rutaArchivo' => $rutaRelativa,
                'rutaCompleta' => $rutaCompleta,
                'mimeType' => (string) ($tipos[$i] ?? ''),
                'tamanoArchivo' => (int) ($tamanos[$i] ?? 0),
            ];
        }

        return $permitidos;
    }

    public static function crtMarcarLeido()
    {
        $idMensaje = (int) ($_POST['id_mensaje'] ?? $_GET['id_mensaje'] ?? 0);
        if ($idMensaje <= 0) {
            return false;
        }

        $respuesta = ModeloMensajes::mdlMarcarLeido($idMensaje, self::idUsuarioActual());
        if ($respuesta === 'ok') {
            $_SESSION['success_message'] = 'Marcado como leido.';
        } else {
            $_SESSION['error_message'] = 'No se pudo marcar como leido.';
        }

        return $respuesta;
    }

    public static function crtMarcarNoLeido()
    {
        $idMensaje = (int) ($_POST['id_mensaje'] ?? $_GET['id_mensaje'] ?? 0);
        if ($idMensaje <= 0) {
            return false;
        }

        $respuesta = ModeloMensajes::mdlMarcarNoLeido($idMensaje, self::idUsuarioActual());
        if ($respuesta === 'ok') {
            $_SESSION['success_message'] = 'Marcado como no leido.';
        } else {
            $_SESSION['error_message'] = 'No se pudo cambiar el estado.';
        }

        return $respuesta;
    }

    public static function crtMoverPapelera()
    {
        $idMensaje = (int) ($_POST['id_mensaje'] ?? $_GET['id_mensaje'] ?? 0);
        if ($idMensaje <= 0) {
            return false;
        }

        $respuesta = ModeloMensajes::mdlMoverAPapelera($idMensaje, self::idUsuarioActual());
        if ($respuesta === 'ok') {
            $_SESSION['success_message'] = 'Mensaje enviado a la papelera.';
        } else {
            $_SESSION['error_message'] = 'No se pudo mover a la papelera.';
        }

        return $respuesta;
    }

    public static function crtRestaurarMensaje()
    {
        $idMensaje = (int) ($_POST['id_mensaje'] ?? $_GET['id_mensaje'] ?? 0);
        if ($idMensaje <= 0) {
            return false;
        }

        $respuesta = ModeloMensajes::mdlRestaurarDePapelera($idMensaje, self::idUsuarioActual());
        if ($respuesta === 'ok') {
            $_SESSION['success_message'] = 'Mensaje restaurado.';
        } else {
            $_SESSION['error_message'] = 'No se pudo restaurar el mensaje.';
        }

        return $respuesta;
    }

    public static function crtEliminarPermanente()
    {
        $idMensaje = (int) ($_POST['id_mensaje'] ?? $_GET['id_mensaje'] ?? 0);
        if ($idMensaje <= 0) {
            return false;
        }

        $respuesta = ModeloMensajes::mdlEliminarPermanente($idMensaje, self::idUsuarioActual());
        if ($respuesta === 'ok') {
            $_SESSION['success_message'] = 'Mensaje eliminado permanentemente.';
        } else {
            $_SESSION['error_message'] = 'No se pudo eliminar permanentemente.';
        }

        return $respuesta;
    }
}
