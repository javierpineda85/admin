<?php
require_once('conexion.php');

class ModeloMensajes
{
    private static function pdo()
    {
        return Conexion::conectar();
    }

    private static function bindArray(PDOStatement $stmt, array $params)
    {
        foreach ($params as $clave => $valor) {
            $tipo = is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($clave, $valor, $tipo);
        }
    }

    private static function idsUnicos(array $ids)
    {
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, static function ($valor) {
            return $valor > 0;
        });
        return array_values(array_unique($ids));
    }

    private static function normalizarRol($rol)
    {
        return strtoupper(trim((string) $rol));
    }

    public static function mdlUsuariosPermitidosParaMensajes($idUsuarioActual, $rolActual)
    {
        return ModeloUsuarios::mdlDestinatariosPermitidos((int) $idUsuarioActual, (string) $rolActual);
    }

    public static function mdlSeccionesParaMensajes($idUsuarioActual, $rolActual)
    {
        $rol = self::normalizarRol($rolActual);
        $pdo = self::pdo();

        if ($rol === 'ADMINISTRADOR') {
            $stmt = $pdo->prepare('
                SELECT s.idSeccion, s.tituloSeccion, c.nombreCurso
                FROM secciones s
                INNER JOIN cursos c ON c.idCurso = s.id_curso
                ORDER BY c.nombreCurso ASC, s.tituloSeccion ASC
            ');
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($rol === 'DOCENTE') {
            $stmt = $pdo->prepare('
                SELECT s.idSeccion, s.tituloSeccion, c.nombreCurso
                FROM secciones s
                INNER JOIN cursos c ON c.idCurso = s.id_curso
                WHERE s.docente = :idUsuario OR s.tutor = :idUsuario
                ORDER BY c.nombreCurso ASC, s.tituloSeccion ASC
            ');
            $stmt->bindValue(':idUsuario', (int) $idUsuarioActual, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return [];
    }

    public static function mdlDestinatariosDeSeccion($idSeccion)
    {
        $stmt = self::pdo()->prepare('
            SELECT DISTINCT u.idUsuario
            FROM secciones s
            INNER JOIN asignacioncursos a ON a.id_seccion = s.id_curso
            INNER JOIN usuarios u ON u.idUsuario = a.id_estudiante
            WHERE s.idSeccion = :idSeccion
              AND u.activo = 1
              AND u.rol = "ESTUDIANTE"
        ');
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
        $stmt->execute();
        return array_map(static function ($fila) {
            return (int) ($fila['idUsuario'] ?? 0);
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public static function mdlGuardarMensaje(array $datos)
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();

        try {
            $destinatarios = self::idsUnicos($datos['destinatarios'] ?? []);
            $idRemitente = (int) ($datos['id_remitente'] ?? 0);
            $contenido = (string) ($datos['contenidoMensaje'] ?? '');
            $fecha = (string) ($datos['fechaMensaje'] ?? date('Y-m-d H:i:s'));
            $idPrimero = $destinatarios[0] ?? $idRemitente;

            $stmt = $pdo->prepare('
                INSERT INTO mensajes (id_remitente, id_destinatario, contenidoMensaje, fechaMensaje)
                VALUES (:id_remitente, :id_destinatario, :contenidoMensaje, :fechaMensaje)
            ');
            $stmt->bindValue(':id_remitente', $idRemitente, PDO::PARAM_INT);
            $stmt->bindValue(':id_destinatario', (int) $idPrimero, PDO::PARAM_INT);
            $stmt->bindValue(':contenidoMensaje', $contenido, PDO::PARAM_STR);
            $stmt->bindValue(':fechaMensaje', $fecha, PDO::PARAM_STR);
            if (!$stmt->execute()) {
                $pdo->rollBack();
                return 'error';
            }

            $idMensaje = (int) $pdo->lastInsertId();
            if ($idMensaje <= 0) {
                $pdo->rollBack();
                return 'error';
            }

            $stmtParticipante = $pdo->prepare('
                INSERT INTO mensajes_participantes
                    (id_mensaje, id_usuario, rolParticipante, leido, fechaLeido, enPapelera, fechaPapelera, eliminado)
                VALUES
                    (:id_mensaje, :id_usuario, :rolParticipante, :leido, :fechaLeido, :enPapelera, :fechaPapelera, 0)
            ');

            $stmtParticipante->execute([
                ':id_mensaje' => $idMensaje,
                ':id_usuario' => $idRemitente,
                ':rolParticipante' => 'REMITENTE',
                ':leido' => 1,
                ':fechaLeido' => $fecha,
                ':enPapelera' => 0,
                ':fechaPapelera' => null,
            ]);

            foreach ($destinatarios as $idDestinatario) {
                $stmtParticipante->execute([
                    ':id_mensaje' => $idMensaje,
                    ':id_usuario' => (int) $idDestinatario,
                    ':rolParticipante' => 'DESTINATARIO',
                    ':leido' => 0,
                    ':fechaLeido' => null,
                    ':enPapelera' => 0,
                    ':fechaPapelera' => null,
                ]);
            }

            foreach (($datos['adjuntos'] ?? []) as $adjunto) {
                $stmtAdjunto = $pdo->prepare('
                    INSERT INTO mensajes_adjuntos
                        (id_mensaje, nombreOriginal, nombreGuardado, rutaArchivo, mimeType, tamanoArchivo)
                    VALUES
                        (:id_mensaje, :nombreOriginal, :nombreGuardado, :rutaArchivo, :mimeType, :tamanoArchivo)
                ');
                $stmtAdjunto->execute([
                    ':id_mensaje' => $idMensaje,
                    ':nombreOriginal' => $adjunto['nombreOriginal'],
                    ':nombreGuardado' => $adjunto['nombreGuardado'],
                    ':rutaArchivo' => $adjunto['rutaArchivo'],
                    ':mimeType' => $adjunto['mimeType'],
                    ':tamanoArchivo' => (int) $adjunto['tamanoArchivo'],
                ]);
            }

            $pdo->commit();
            return $idMensaje;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return 'error';
        }
    }

    private static function consultaBaseMensajesRecibidos()
    {
        return '
            SELECT mp.idMensajeParticipante, mp.id_mensaje, mp.id_usuario, mp.rolParticipante, mp.leido, mp.fechaLeido,
                   mp.enPapelera, mp.fechaPapelera,
                   m.id_remitente, m.contenidoMensaje, m.fechaMensaje,
                   u.nombreUsuario, u.apellidoUsuario, u.email,
                   (SELECT COUNT(*) FROM mensajes_adjuntos ma WHERE ma.id_mensaje = m.idMensaje) AS totalAdjuntos,
                   (SELECT COUNT(*) FROM mensajes_participantes mp2
                      WHERE mp2.id_mensaje = m.idMensaje
                        AND mp2.rolParticipante = "DESTINATARIO"
                        AND mp2.eliminado = 0) AS totalDestinatarios
            FROM mensajes_participantes mp
            INNER JOIN mensajes m ON m.idMensaje = mp.id_mensaje
            INNER JOIN usuarios u ON u.idUsuario = m.id_remitente
            WHERE mp.id_usuario = :idUsuario
              AND mp.rolParticipante = "DESTINATARIO"
              AND mp.eliminado = 0
        ';
    }

    private static function consultaBaseMensajesEnviados()
    {
        return '
            SELECT mp.idMensajeParticipante, mp.id_mensaje, mp.id_usuario, mp.rolParticipante, mp.leido, mp.fechaLeido,
                   mp.enPapelera, mp.fechaPapelera,
                   m.id_remitente, m.contenidoMensaje, m.fechaMensaje,
                   (SELECT COUNT(*) FROM mensajes_adjuntos ma WHERE ma.id_mensaje = m.idMensaje) AS totalAdjuntos,
                   (SELECT COUNT(*) FROM mensajes_participantes mp2
                      WHERE mp2.id_mensaje = m.idMensaje
                        AND mp2.rolParticipante = "DESTINATARIO"
                        AND mp2.eliminado = 0) AS totalDestinatarios,
                   (SELECT GROUP_CONCAT(CONCAT(u.nombreUsuario, " ", u.apellidoUsuario) SEPARATOR ", ")
                      FROM mensajes_participantes mp3
                      INNER JOIN usuarios u ON u.idUsuario = mp3.id_usuario
                      WHERE mp3.id_mensaje = m.idMensaje
                        AND mp3.rolParticipante = "DESTINATARIO"
                        AND mp3.eliminado = 0) AS destinatariosResumen
            FROM mensajes_participantes mp
            INNER JOIN mensajes m ON m.idMensaje = mp.id_mensaje
            WHERE mp.id_usuario = :idUsuario
              AND mp.rolParticipante = "REMITENTE"
              AND mp.eliminado = 0
        ';
    }

    private static function consultaBasePapelera()
    {
        return '
            SELECT mp.idMensajeParticipante, mp.id_mensaje, mp.id_usuario, mp.rolParticipante, mp.leido, mp.fechaLeido,
                   mp.enPapelera, mp.fechaPapelera,
                   m.id_remitente, m.contenidoMensaje, m.fechaMensaje,
                   u.nombreUsuario, u.apellidoUsuario, u.email,
                   (SELECT COUNT(*) FROM mensajes_adjuntos ma WHERE ma.id_mensaje = m.idMensaje) AS totalAdjuntos
            FROM mensajes_participantes mp
            INNER JOIN mensajes m ON m.idMensaje = mp.id_mensaje
            LEFT JOIN usuarios u ON u.idUsuario = m.id_remitente
            WHERE mp.id_usuario = :idUsuario
              AND mp.enPapelera = 1
              AND mp.eliminado = 0
        ';
    }

    public static function mdlContarMensajesRecibidos($idUsuario)
    {
        $stmt = self::pdo()->prepare('
            SELECT COUNT(*) AS total
            FROM mensajes_participantes
            WHERE id_usuario = :idUsuario
              AND rolParticipante = "DESTINATARIO"
              AND enPapelera = 0
              AND eliminado = 0
        ');
        $stmt->bindValue(':idUsuario', (int) $idUsuario, PDO::PARAM_INT);
        $stmt->execute();
        return (int) (($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0));
    }

    public static function mdlContarMensajesEnviados($idUsuario)
    {
        $stmt = self::pdo()->prepare('
            SELECT COUNT(*) AS total
            FROM mensajes_participantes
            WHERE id_usuario = :idUsuario
              AND rolParticipante = "REMITENTE"
              AND enPapelera = 0
              AND eliminado = 0
        ');
        $stmt->bindValue(':idUsuario', (int) $idUsuario, PDO::PARAM_INT);
        $stmt->execute();
        return (int) (($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0));
    }

    public static function mdlContarMensajesPapelera($idUsuario)
    {
        $stmt = self::pdo()->prepare('
            SELECT COUNT(*) AS total
            FROM mensajes_participantes
            WHERE id_usuario = :idUsuario
              AND enPapelera = 1
              AND eliminado = 0
        ');
        $stmt->bindValue(':idUsuario', (int) $idUsuario, PDO::PARAM_INT);
        $stmt->execute();
        return (int) (($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0));
    }

    public static function mdlContarMensajesNoLeidos($idUsuario)
    {
        $stmt = self::pdo()->prepare('
            SELECT COUNT(*) AS total
            FROM mensajes_participantes
            WHERE id_usuario = :idUsuario
              AND rolParticipante = "DESTINATARIO"
              AND leido = 0
              AND enPapelera = 0
              AND eliminado = 0
        ');
        $stmt->bindValue(':idUsuario', (int) $idUsuario, PDO::PARAM_INT);
        $stmt->execute();
        return (int) (($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0));
    }

    public static function mdlMensajesRecibidos($idUsuario)
    {
        $stmt = self::pdo()->prepare(self::consultaBaseMensajesRecibidos() . ' ORDER BY mp.leido ASC, m.fechaMensaje DESC');
        $stmt->bindValue(':idUsuario', (int) $idUsuario, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlMensajesEnviados($idUsuario)
    {
        $stmt = self::pdo()->prepare(self::consultaBaseMensajesEnviados() . ' ORDER BY m.fechaMensaje DESC');
        $stmt->bindValue(':idUsuario', (int) $idUsuario, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlMensajesPapelera($idUsuario)
    {
        $stmt = self::pdo()->prepare(self::consultaBasePapelera() . ' ORDER BY mp.fechaPapelera DESC, m.fechaMensaje DESC');
        $stmt->bindValue(':idUsuario', (int) $idUsuario, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlMensajesRecientesRecibidos($idUsuario, $limite = 5)
    {
        $limite = max(1, (int) $limite);
        $sql = self::consultaBaseMensajesRecibidos() . ' ORDER BY mp.leido ASC, m.fechaMensaje DESC LIMIT ' . $limite;
        $stmt = self::pdo()->prepare($sql);
        $stmt->bindValue(':idUsuario', (int) $idUsuario, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlMensajesRecientesEnviados($idUsuario, $limite = 5)
    {
        $limite = max(1, (int) $limite);
        $sql = self::consultaBaseMensajesEnviados() . ' ORDER BY m.fechaMensaje DESC LIMIT ' . $limite;
        $stmt = self::pdo()->prepare($sql);
        $stmt->bindValue(':idUsuario', (int) $idUsuario, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlMensajeDetalle($idMensaje, $idUsuario)
    {
        $stmt = self::pdo()->prepare('
            SELECT mp.idMensajeParticipante, mp.id_mensaje, mp.id_usuario, mp.rolParticipante, mp.leido, mp.fechaLeido,
                   mp.enPapelera, mp.fechaPapelera,
                   m.idMensaje, m.id_remitente, m.contenidoMensaje, m.fechaMensaje,
                   u.nombreUsuario, u.apellidoUsuario, u.email, u.rol
            FROM mensajes_participantes mp
            INNER JOIN mensajes m ON m.idMensaje = mp.id_mensaje
            INNER JOIN usuarios u ON u.idUsuario = m.id_remitente
            WHERE mp.id_mensaje = :idMensaje
              AND mp.id_usuario = :idUsuario
              AND mp.eliminado = 0
            LIMIT 1
        ');
        $stmt->bindValue(':idMensaje', (int) $idMensaje, PDO::PARAM_INT);
        $stmt->bindValue(':idUsuario', (int) $idUsuario, PDO::PARAM_INT);
        $stmt->execute();
        $mensaje = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if (!$mensaje) {
            return null;
        }

        $mensaje['adjuntos'] = self::mdlAdjuntosPorMensaje((int) $idMensaje);
        $mensaje['destinatarios'] = self::mdlDestinatariosPorMensaje((int) $idMensaje);
        return $mensaje;
    }

    public static function mdlAdjuntosPorMensaje($idMensaje)
    {
        $stmt = self::pdo()->prepare('
            SELECT idAdjunto, id_mensaje, nombreOriginal, nombreGuardado, rutaArchivo, mimeType, tamanoArchivo, fechaAdjunto
            FROM mensajes_adjuntos
            WHERE id_mensaje = :idMensaje
            ORDER BY idAdjunto ASC
        ');
        $stmt->bindValue(':idMensaje', (int) $idMensaje, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlDestinatariosPorMensaje($idMensaje)
    {
        $stmt = self::pdo()->prepare('
            SELECT u.idUsuario, u.nombreUsuario, u.apellidoUsuario, u.rol, mp.leido, mp.enPapelera
            FROM mensajes_participantes mp
            INNER JOIN usuarios u ON u.idUsuario = mp.id_usuario
            WHERE mp.id_mensaje = :idMensaje
              AND mp.rolParticipante = "DESTINATARIO"
              AND mp.eliminado = 0
            ORDER BY u.apellidoUsuario ASC, u.nombreUsuario ASC
        ');
        $stmt->bindValue(':idMensaje', (int) $idMensaje, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlMarcarLeido($idMensaje, $idUsuario)
    {
        $stmt = self::pdo()->prepare('
            UPDATE mensajes_participantes
            SET leido = 1,
                fechaLeido = NOW()
            WHERE id_mensaje = :idMensaje
              AND id_usuario = :idUsuario
              AND rolParticipante = "DESTINATARIO"
              AND eliminado = 0
        ');
        $stmt->bindValue(':idMensaje', (int) $idMensaje, PDO::PARAM_INT);
        $stmt->bindValue(':idUsuario', (int) $idUsuario, PDO::PARAM_INT);
        return $stmt->execute() ? 'ok' : 'error';
    }

    public static function mdlMarcarNoLeido($idMensaje, $idUsuario)
    {
        $stmt = self::pdo()->prepare('
            UPDATE mensajes_participantes
            SET leido = 0,
                fechaLeido = NULL
            WHERE id_mensaje = :idMensaje
              AND id_usuario = :idUsuario
              AND rolParticipante = "DESTINATARIO"
              AND eliminado = 0
        ');
        $stmt->bindValue(':idMensaje', (int) $idMensaje, PDO::PARAM_INT);
        $stmt->bindValue(':idUsuario', (int) $idUsuario, PDO::PARAM_INT);
        return $stmt->execute() ? 'ok' : 'error';
    }

    public static function mdlMoverAPapelera($idMensaje, $idUsuario)
    {
        $stmt = self::pdo()->prepare('
            UPDATE mensajes_participantes
            SET enPapelera = 1,
                fechaPapelera = NOW()
            WHERE id_mensaje = :idMensaje
              AND id_usuario = :idUsuario
              AND eliminado = 0
        ');
        $stmt->bindValue(':idMensaje', (int) $idMensaje, PDO::PARAM_INT);
        $stmt->bindValue(':idUsuario', (int) $idUsuario, PDO::PARAM_INT);
        return $stmt->execute() ? 'ok' : 'error';
    }

    public static function mdlRestaurarDePapelera($idMensaje, $idUsuario)
    {
        $stmt = self::pdo()->prepare('
            UPDATE mensajes_participantes
            SET enPapelera = 0,
                fechaPapelera = NULL
            WHERE id_mensaje = :idMensaje
              AND id_usuario = :idUsuario
              AND eliminado = 0
        ');
        $stmt->bindValue(':idMensaje', (int) $idMensaje, PDO::PARAM_INT);
        $stmt->bindValue(':idUsuario', (int) $idUsuario, PDO::PARAM_INT);
        return $stmt->execute() ? 'ok' : 'error';
    }

    public static function mdlEliminarPermanente($idMensaje, $idUsuario)
    {
        $pdo = self::pdo();
        $stmt = $pdo->prepare('
            UPDATE mensajes_participantes
            SET eliminado = 1
            WHERE id_mensaje = :idMensaje
              AND id_usuario = :idUsuario
        ');
        $stmt->bindValue(':idMensaje', (int) $idMensaje, PDO::PARAM_INT);
        $stmt->bindValue(':idUsuario', (int) $idUsuario, PDO::PARAM_INT);
        if (!$stmt->execute()) {
            return 'error';
        }

        $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM mensajes_participantes WHERE id_mensaje = :idMensaje AND eliminado = 0');
        $stmt->bindValue(':idMensaje', (int) $idMensaje, PDO::PARAM_INT);
        $stmt->execute();
        $restantes = (int) (($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0));

        if ($restantes === 0) {
            $pdo->prepare('DELETE FROM mensajes_adjuntos WHERE id_mensaje = :idMensaje')->execute([':idMensaje' => (int) $idMensaje]);
            $pdo->prepare('DELETE FROM mensajes_participantes WHERE id_mensaje = :idMensaje')->execute([':idMensaje' => (int) $idMensaje]);
            $pdo->prepare('DELETE FROM mensajes WHERE idMensaje = :idMensaje')->execute([':idMensaje' => (int) $idMensaje]);
        }

        return 'ok';
    }

    public static function mdlMostrarMensajes($item, $valor)
    {
        if ($item === 'id_destinatario') {
            return self::mdlMensajesRecibidos((int) $valor);
        }

        return [];
    }

    public static function mdlMostrarMensajesEnviados($item, $valor)
    {
        if ($item === 'id_remitente') {
            return self::mdlMensajesEnviados((int) $valor);
        }

        return [];
    }

    public static function mdlMostrarUnMensaje($id)
    {
        return self::mdlMensajeDetalle((int) $id, (int) ($_SESSION['usuario']['id'] ?? 0));
    }
}
