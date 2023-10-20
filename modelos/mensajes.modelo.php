<?php
require_once('conexion.php');

class ModeloMensajes
{
    static public function mdlMostrarMensajes($item, $valor){


        $stmt = Conexion::conectar()->prepare("SELECT idMensaje, id_remitente, id_destinatario,contenidoMensaje, DATE_FORMAT(fechaMensaje, '%d/%m/%Y') AS fMensaje, DATE_FORMAT(fechaMensaje, '%H:%i') AS horaMensaje, nombreUsuario, apellidoUsuario FROM mensajes JOIN usuarios ON id_remitente = usuarios.idUsuario WHERE $item = $valor ORDER BY fechaMensaje DESC; ");
        $stmt->execute();
        return $stmt->fetchAll();
        $stmt->closeCursor();

        $stmt = null;
    }
    static public function mdlMostrarUnMensaje($id){


        $stmt = Conexion::conectar()->prepare("SELECT idMensaje, id_remitente, id_destinatario,contenidoMensaje, DATE_FORMAT(fechaMensaje, '%d/%m/%Y') AS fMensaje, DATE_FORMAT(fechaMensaje, '%H:%i') AS horaMensaje, nombreUsuario, apellidoUsuario FROM mensajes JOIN usuarios ON id_remitente = usuarios.idUsuario WHERE idMensaje = $id ORDER BY fechaMensaje DESC; ");
        $stmt->execute();
        return $stmt->fetchAll();
        $stmt->closeCursor();

        $stmt = null;
    }

    static public function mdlGuardarMensaje($datos){
                
        $registro = Conexion::conectar()->prepare("INSERT INTO mensajes (id_remitente, id_destinatario, contenidoMensaje, fechaMensaje) VALUES (:id_remitente, :id_destinatario, :contenidoMensaje, :fechaMensaje)");

        $registro->bindParam(":id_remitente", $datos["id_remitente"], PDO::PARAM_INT);
        $registro->bindParam(":id_destinatario", $datos["id_destinatario"], PDO::PARAM_INT);
        $registro->bindParam(":contenidoMensaje", $datos["contenidoMensaje"], PDO::PARAM_STR);
        $registro->bindParam("fechaMensaje", $datos["fechaMensaje"], PDO::PARAM_STR);

        if ($registro->execute()) {
            return "ok";
        } else {
            print_r(Conexion::conectar()->errorInfo());
        }

        $registro->closeCursor();
        $registro = null;
    }
}