<?php
require_once('modelos/usuarios.modelo.php');
require_once('modelos/mensajes.modelo.php');

class ControladorMensajes
{
    static public function crtMostrarMensajes($item, $valor){
        $respuesta = ModeloMensajes::mdlMostrarMensajes($item, $valor);
        return $respuesta;
    }

    static public function crtMostrarMensajesEnviados($item, $valor){
        $respuesta = ModeloMensajes::mdlMostrarMensajesEnviados($item, $valor);
        return $respuesta;
    }
    static public function crtMostrarUnMensaje($id){
        $respuesta = ModeloMensajes::mdlMostrarUnMensaje($id);
        return $respuesta;
    }

    static public function crtDestinatariosPermitidos()
    {
        return ControladorUsuarios::crtDestinatariosPermitidos();
    }

    static public function crtGuardarMensaje(){
        if (isset($_POST["id_destinatario"], $_POST["contenidoMensaje"])) {
            $idRemitente = (int) ($_SESSION['usuario']['id'] ?? 0);
            $idDestinatario = (int) $_POST["id_destinatario"];
            $contenido = trim((string) $_POST["contenidoMensaje"]);

            if ($idRemitente <= 0 || $idDestinatario <= 0 || $contenido === '') {
                $_SESSION['success_message'] = 'Completa el destinatario y el mensaje.';
                return false;
            }

            $usuarioRemitente = ModeloUsuarios::mdlObtenerUsuarioPorId($idRemitente);
            $usuarioDestinatario = ModeloUsuarios::mdlObtenerUsuarioPorId($idDestinatario);

            if (!$usuarioRemitente || !$usuarioDestinatario) {
                $_SESSION['success_message'] = 'No se pudo validar el destinatario.';
                return false;
            }

            $rolRemitente = strtoupper(trim((string) ($usuarioRemitente['rol'] ?? '')));
            $rolDestinatario = strtoupper(trim((string) ($usuarioDestinatario['rol'] ?? '')));

            $esAdminODocente = in_array($rolRemitente, ['ADMINISTRADOR', 'DOCENTE'], true);
            $esEstudiante = $rolRemitente === 'ESTUDIANTE' && $rolDestinatario === 'ESTUDIANTE';

            if ($rolRemitente === 'ESTUDIANTE' && !$esEstudiante) {
                $_SESSION['success_message'] = 'Los estudiantes solo pueden escribir a otros estudiantes.';
                return false;
            }

            if ($esEstudiante && !ModeloUsuarios::mdlCompartenCurso($idRemitente, $idDestinatario)) {
                $_SESSION['success_message'] = 'Solo podés escribir a estudiantes de tu mismo curso.';
                return false;
            }

            $datos = array(
                "id_remitente"        => $idRemitente,
                "id_destinatario"     => $idDestinatario,
                "contenidoMensaje"    => $contenido,
                "fechaMensaje" => date('Y-m-d H:i:s')
            );

            $respuesta = ModeloMensajes::mdlGuardarMensaje($datos);
            $_SESSION['success_message'] = $respuesta === "ok" ? 'Mensaje enviado exitosamente' : 'No se pudo enviar el mensaje';
           return $respuesta;
            
        }
    }
}
