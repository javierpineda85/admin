<?php
require_once('conexion.php');

class ModeloMensajes
{
    private static function columnaPermitida($item)
    {
        return in_array($item, ['id_remitente', 'id_destinatario'], true) ? $item : null;
    }

    static public function mdlMostrarMensajes($item, $valor){
        $columna = self::columnaPermitida($item);
        if ($columna === null) {
            return [];
        }

        $stmt = Conexion::conectar()->prepare("
            SELECT m.idMensaje, m.id_remitente, m.id_destinatario, m.contenidoMensaje,
                   DATE_FORMAT(m.fechaMensaje, '%d/%m/%Y') AS fMensaje,
                   DATE_FORMAT(m.fechaMensaje, '%H:%i') AS horaMensaje,
                   u.nombreUsuario, u.apellidoUsuario
            FROM mensajes m
            JOIN usuarios u ON m.id_remitente = u.idUsuario
            WHERE m.$columna = :valor
            ORDER BY m.fechaMensaje DESC
        ");
        $stmt->bindParam(":valor", $valor, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlContarMensajesRecibidos($idUsuario)
    {
        $stmt = Conexion::conectar()->prepare('SELECT COUNT(*) AS total FROM mensajes WHERE id_destinatario = :idUsuario');
        $stmt->bindValue(':idUsuario', (int) $idUsuario, PDO::PARAM_INT);
        $stmt->execute();
        return (int) (($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0));
    }

    static public function mdlContarMensajesEnviados($idUsuario)
    {
        $stmt = Conexion::conectar()->prepare('SELECT COUNT(*) AS total FROM mensajes WHERE id_remitente = :idUsuario');
        $stmt->bindValue(':idUsuario', (int) $idUsuario, PDO::PARAM_INT);
        $stmt->execute();
        return (int) (($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0));
    }

    static public function mdlMensajesRecientesRecibidos($idUsuario, $limite = 5)
    {
        $limite = max(1, (int) $limite);
        $stmt = Conexion::conectar()->prepare("
            SELECT m.idMensaje, m.id_remitente, m.id_destinatario, m.contenidoMensaje, m.fechaMensaje,
                   DATE_FORMAT(m.fechaMensaje, '%d/%m/%Y') AS fMensaje,
                   DATE_FORMAT(m.fechaMensaje, '%H:%i') AS horaMensaje,
                   u.nombreUsuario, u.apellidoUsuario
            FROM mensajes m
            JOIN usuarios u ON m.id_remitente = u.idUsuario
            WHERE m.id_destinatario = :idUsuario
            ORDER BY m.fechaMensaje DESC
            LIMIT $limite
        ");
        $stmt->bindValue(':idUsuario', (int) $idUsuario, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlMensajesRecientesEnviados($idUsuario, $limite = 5)
    {
        $limite = max(1, (int) $limite);
        $stmt = Conexion::conectar()->prepare("
            SELECT m.idMensaje, m.id_remitente, m.id_destinatario, m.contenidoMensaje, m.fechaMensaje,
                   DATE_FORMAT(m.fechaMensaje, '%d/%m/%Y') AS fMensaje,
                   DATE_FORMAT(m.fechaMensaje, '%H:%i') AS horaMensaje,
                   u.nombreUsuario, u.apellidoUsuario
            FROM mensajes m
            JOIN usuarios u ON m.id_destinatario = u.idUsuario
            WHERE m.id_remitente = :idUsuario
            ORDER BY m.fechaMensaje DESC
            LIMIT $limite
        ");
        $stmt->bindValue(':idUsuario', (int) $idUsuario, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    static public function mdlMostrarMensajesEnviados($item, $valor){
        $columna = self::columnaPermitida($item);
        if ($columna === null) {
            return [];
        }

        $stmt = Conexion::conectar()->prepare("
            SELECT m.idMensaje, m.id_remitente, m.id_destinatario, m.contenidoMensaje,
                   DATE_FORMAT(m.fechaMensaje, '%d/%m/%Y') AS fMensaje,
                   DATE_FORMAT(m.fechaMensaje, '%H:%i') AS horaMensaje,
                   u.nombreUsuario, u.apellidoUsuario
            FROM mensajes m
            JOIN usuarios u ON m.id_destinatario = u.idUsuario
            WHERE m.$columna = :valor
            ORDER BY m.fechaMensaje DESC
        ");
        $stmt->bindParam(":valor", $valor, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    static public function mdlMostrarUnMensaje($id){

        $stmt = Conexion::conectar()->prepare("
            SELECT m.idMensaje, m.id_remitente, m.id_destinatario, m.contenidoMensaje,
                   DATE_FORMAT(m.fechaMensaje, '%d/%m/%Y') AS fMensaje,
                   DATE_FORMAT(m.fechaMensaje, '%H:%i') AS horaMensaje,
                   u.nombreUsuario, u.apellidoUsuario
            FROM mensajes m
            JOIN usuarios u ON m.id_remitente = u.idUsuario
            WHERE m.idMensaje = :idMensaje
            ORDER BY m.fechaMensaje DESC
            LIMIT 1
        ");
        $stmt->bindParam(":idMensaje", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    static public function mdlGuardarMensaje($datos){
                /* HOLA LEANDRO*/
                
        $registro = Conexion::conectar()->prepare("INSERT INTO mensajes (id_remitente, id_destinatario, contenidoMensaje, fechaMensaje) VALUES (:id_remitente, :id_destinatario, :contenidoMensaje, :fechaMensaje)");

        $registro->bindParam(":id_remitente", $datos["id_remitente"], PDO::PARAM_INT);
        $registro->bindParam(":id_destinatario", $datos["id_destinatario"], PDO::PARAM_INT);
        $registro->bindParam(":contenidoMensaje", $datos["contenidoMensaje"], PDO::PARAM_STR);
        $registro->bindParam(":fechaMensaje", $datos["fechaMensaje"], PDO::PARAM_STR);

        if ($registro->execute()) {
            return "ok";
        } else {
            print_r(Conexion::conectar()->errorInfo());
        }

        $registro->closeCursor();
        $registro = null;
    }
}
