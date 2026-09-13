<?php
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/tenant.modelo.php';

class ModeloNotificaciones
{
    private static $tablaPreparada = false;

    private static function prepararTabla()
    {
        if (self::$tablaPreparada) {
            return;
        }

        if(ModeloTenant::activo()){
            self::$tablaPreparada=true;
            return;
        }

        try {
            Conexion::conectar()->exec('
                CREATE TABLE IF NOT EXISTS notificaciones (
                    idNotificacion int NOT NULL AUTO_INCREMENT,
                    id_usuario int NOT NULL,
                    tipoNotificacion varchar(40) NOT NULL,
                    referenciaTipo varchar(30) NOT NULL,
                    referenciaId int NOT NULL,
                    tituloNotificacion varchar(160) NOT NULL,
                    detalleNotificacion text NULL,
                    urlNotificacion varchar(255) NULL,
                    fechaNotificacion datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (idNotificacion),
                    UNIQUE KEY uq_notificacion_usuario_ref (id_usuario, tipoNotificacion, referenciaTipo, referenciaId),
                    KEY idx_notificaciones_usuario_fecha (id_usuario, fechaNotificacion)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ');
        } catch (Exception $e) {
            // Si la base no permite DDL, el SQL versionado deja documentado el cambio requerido.
        }

        self::$tablaPreparada = true;
    }

    public static function mdlBuscarEstudiantesPorCurso($idCurso)
    {
        $stmt = Conexion::conectar()->prepare(
            'SELECT DISTINCT u.idUsuario, u.nombreUsuario, u.apellidoUsuario, u.email
             FROM asignacioncursos a
             INNER JOIN usuarios u ON u.idUsuario = a.id_estudiante
             WHERE a.id_seccion = :idCurso
               AND a.estadoInscripcion = "ACTIVA"
               AND u.activo = 1
               AND EXISTS (SELECT 1 FROM cursos c WHERE c.idCurso=a.id_seccion AND ' . ModeloTenant::cursos('c') . ')
               AND ' . ModeloTenant::usuarioConRol('u.idUsuario',['ESTUDIANTE']) . '
             ORDER BY u.apellidoUsuario ASC, u.nombreUsuario ASC'
        );
        $stmt->bindValue(':idCurso', (int) $idCurso, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlRegistrarNotificacion(array $datos)
    {
        self::prepararTabla();

        if(ModeloTenant::activo()){
            ModeloTenant::exigirUsuario((int)$datos['id_usuario'],['ESTUDIANTE']);
            $tipo=strtoupper((string)($datos['referenciaTipo']??''));
            if($tipo==='LECCION'){ModeloTenant::exigirLeccion((int)$datos['referenciaId']);}
            elseif($tipo==='ACTIVIDAD'){ModeloTenant::exigirActividad((int)$datos['referenciaId']);}
            else{throw new RuntimeException('Referencia institucional no válida.');}
        }

        try {
            $institucional=ModeloTenant::activo();
            $stmt = Conexion::conectar()->prepare(
                'INSERT IGNORE INTO notificaciones
                    (id_usuario, tipoNotificacion, referenciaTipo, referenciaId, tituloNotificacion, detalleNotificacion, urlNotificacion'.($institucional?', id_institucion':'').')
                 '.($institucional?'SELECT':'VALUES (').'
                    :id_usuario, :tipoNotificacion, :referenciaTipo, :referenciaId, :tituloNotificacion, :detalleNotificacion, :urlNotificacion'.($institucional?', :id_institucion':'').'
                 '.($institucional?'WHERE '.ModeloTenant::sesionActiva():')')
            );
            $stmt->bindValue(':id_usuario', (int) $datos['id_usuario'], PDO::PARAM_INT);
            $stmt->bindValue(':tipoNotificacion', (string) $datos['tipoNotificacion'], PDO::PARAM_STR);
            $stmt->bindValue(':referenciaTipo', (string) $datos['referenciaTipo'], PDO::PARAM_STR);
            $stmt->bindValue(':referenciaId', (int) $datos['referenciaId'], PDO::PARAM_INT);
            $stmt->bindValue(':tituloNotificacion', (string) $datos['tituloNotificacion'], PDO::PARAM_STR);
            $stmt->bindValue(':detalleNotificacion', (string) ($datos['detalleNotificacion'] ?? ''), PDO::PARAM_STR);
            $stmt->bindValue(':urlNotificacion', (string) ($datos['urlNotificacion'] ?? ''), PDO::PARAM_STR);
            if($institucional){$stmt->bindValue(':id_institucion',ModeloTenant::id(),PDO::PARAM_INT);}
            if (!$stmt->execute()) {
                return 'error';
            }

            return $stmt->rowCount() > 0 ? 'ok' : 'duplicada';
        } catch (Exception $e) {
            return 'error';
        }
    }

    public static function mdlListarNotificacionesUsuario($idUsuario, $limite = 10)
    {
        self::prepararTabla();

        try {
            $stmt = Conexion::conectar()->prepare(
                'SELECT idNotificacion, id_usuario, tipoNotificacion, referenciaTipo, referenciaId,
                        tituloNotificacion, detalleNotificacion, urlNotificacion, fechaNotificacion
                 FROM notificaciones
                 WHERE id_usuario = :idUsuario
                   AND ' . ModeloTenant::notificaciones('notificaciones') . '
                 ORDER BY fechaNotificacion DESC, idNotificacion DESC
                 LIMIT ' . (int) $limite
            );
            $stmt->bindValue(':idUsuario', (int) $idUsuario, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}
