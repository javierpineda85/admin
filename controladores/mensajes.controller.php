<?php
require_once('modelos/mensajes.modelo.php');

class ControladorMensajes
{
    static public function crtMostrarMensajes($item, $valor){
        $respuesta = ModeloMensajes::mdlMostrarMensajes($item, $valor);
        return $respuesta;

        exit;
    }

    static public function crtMostrarMensajesEnviados($item, $valor){
        $respuesta = ModeloMensajes::mdlMostrarMensajesEnviados($item, $valor);
        return $respuesta;

        exit;
    }
    static public function crtMostrarUnMensaje($id){
        $respuesta = ModeloMensajes::mdlMostrarUnMensaje($id);
        return $respuesta;

        exit;
    }

    static public function crtGuardarMensaje(){
        if (isset($_POST["id_destinatario"])) {
            $datos = array(
                "id_remitente"        => $_POST["id_remitente"],
                "id_destinatario"     => $_POST["id_destinatario"],
                "contenidoMensaje"    => $_POST["contenidoMensaje"],
                "fechaMensaje" => date('Y-m-d H:i:s')
            );

            $respuesta = ModeloMensajes::mdlGuardarMensaje($datos);
            $_SESSION['success_message'] = 'Mensaje enviado exitosamente';
           return $respuesta;
            
        }
    }
}