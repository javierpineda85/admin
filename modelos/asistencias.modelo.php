<?php
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/tenant.modelo.php';

class ModeloAsistencias
{
    private static $preparada = false;

    public static function prepararTablas()
    {
        if (self::$preparada) { return; }
        if (ModeloTenant::activo()) {
            self::$preparada = true;
            return;
        }
        $pdo = Conexion::conectar();
        $pdo->exec("CREATE TABLE IF NOT EXISTS asistencia_clases(idClase INT NOT NULL AUTO_INCREMENT,id_seccion INT NOT NULL,id_curso INT NOT NULL,fechaClase DATE NOT NULL,tema VARCHAR(180) NULL,creadaPor INT NOT NULL,fechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY(idClase),UNIQUE KEY uq_asistencia_fecha(id_seccion,fechaClase),KEY idx_asistencia_curso(id_curso)) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE TABLE IF NOT EXISTS asistencia_registros(idAsistencia INT NOT NULL AUTO_INCREMENT,id_clase INT NOT NULL,id_estudiante INT NOT NULL,estado VARCHAR(15) NOT NULL DEFAULT 'PRESENTE',observacion VARCHAR(255) NULL,actualizadoPor INT NULL,fechaActualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY(idAsistencia),UNIQUE KEY uq_asistencia_estudiante(id_clase,id_estudiante),KEY idx_asistencia_alumno(id_estudiante)) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        self::$preparada = true;
    }

    public static function mdlSecciones($idDocente = 0)
    {
        self::prepararTablas();
        $condiciones = [ModeloTenant::secciones('s')];
        if ((int)$idDocente > 0) { $condiciones[] = '(s.docente=:docente OR s.tutor=:docente OR c.responsable=:docente)'; }
        $sql = "SELECT s.idSeccion,s.tituloSeccion materia,c.idCurso,c.nombreCurso curso,
                CONCAT(u.nombreUsuario,' ',u.apellidoUsuario) docente,COUNT(DISTINCT ac.idClase) clases
            FROM secciones s INNER JOIN cursos c ON c.idCurso=s.id_curso
            LEFT JOIN usuarios u ON u.idUsuario=s.docente
            LEFT JOIN asistencia_clases ac ON ac.id_seccion=s.idSeccion AND " . ModeloTenant::asistenciaClases('ac') . '
            WHERE ' . implode(' AND ', $condiciones) . '
            GROUP BY s.idSeccion ORDER BY c.nombreCurso,s.tituloSeccion';
        $stmt = Conexion::conectar()->prepare($sql);
        if ((int)$idDocente > 0) { $stmt->bindValue(':docente', (int)$idDocente, PDO::PARAM_INT); }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlCrearClase($idSeccion, $idCurso, $fecha, $tema, $idUsuario)
    {
        self::prepararTablas();
        ModeloTenant::exigirSeccion($idSeccion);
        ModeloTenant::exigirUsuario($idUsuario, ['ADMINISTRADOR', 'DOCENTE']);
        $pdo = Conexion::conectar();
        if (ModeloTenant::activo()) {
            $stmt = $pdo->prepare('INSERT INTO asistencia_clases(id_seccion,id_curso,fechaClase,tema,creadaPor)
                SELECT s.idSeccion,c.idCurso,?,?,? FROM secciones s INNER JOIN cursos c ON c.idCurso=s.id_curso
                WHERE s.idSeccion=? AND c.idCurso=? AND ' . ModeloTenant::secciones('s') . '
                AND ('. ModeloTenant::usuarioIdConRol($idUsuario, ['ADMINISTRADOR']) . '
                    OR ((s.docente=' . (int)$idUsuario . ' OR s.tutor=' . (int)$idUsuario . ' OR c.responsable=' . (int)$idUsuario . ')
                        AND ' . ModeloTenant::usuarioIdConRol($idUsuario, ['DOCENTE']) . '))
                ON DUPLICATE KEY UPDATE tema=VALUES(tema),idClase=LAST_INSERT_ID(idClase)');
            $stmt->execute([$fecha, $tema, (int)$idUsuario, (int)$idSeccion, (int)$idCurso]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO asistencia_clases(id_seccion,id_curso,fechaClase,tema,creadaPor) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE tema=VALUES(tema),idClase=LAST_INSERT_ID(idClase)');
            $stmt->execute([(int)$idSeccion, (int)$idCurso, $fecha, $tema, (int)$idUsuario]);
        }
        $buscar = $pdo->prepare('SELECT ac.idClase FROM asistencia_clases ac WHERE ac.id_seccion=? AND ac.fechaClase=? AND ' . ModeloTenant::asistenciaClases('ac'));
        $buscar->execute([(int)$idSeccion, $fecha]);
        $idClase = (int)$buscar->fetchColumn();
        if ($idClase <= 0) { throw new RuntimeException('Acceso institucional denegado.'); }
        $sql = "INSERT IGNORE INTO asistencia_registros(id_clase,id_estudiante,estado,actualizadoPor)
            SELECT ?,a.id_estudiante,'PRESENTE',? FROM asignacioncursos a INNER JOIN usuarios u ON u.idUsuario=a.id_estudiante
            WHERE a.id_seccion=? AND u.activo=1 AND (a.fechaAlta IS NULL OR DATE(a.fechaAlta)<=?)
            AND (a.fechaBaja IS NULL OR DATE(a.fechaBaja)>?)";
        if (ModeloTenant::activo()) {
            $sql .= " AND a.estadoInscripcion='ACTIVA' AND " . ModeloTenant::cursoId($idCurso)
                . ' AND ' . ModeloTenant::usuarioConRol('u.idUsuario', ['ESTUDIANTE']);
        }
        $alumnos = $pdo->prepare($sql);
        $alumnos->execute([$idClase, (int)$idUsuario, (int)$idCurso, $fecha, $fecha]);
        return $idClase;
    }

    public static function mdlClasesSeccion($idSeccion)
    {
        self::prepararTablas(); ModeloTenant::exigirSeccion($idSeccion);
        $stmt = Conexion::conectar()->prepare("SELECT ac.*,COUNT(r.idAsistencia) total,SUM(r.estado='PRESENTE') presentes,
                SUM(r.estado='AUSENTE') ausentes,SUM(r.estado='TARDANZA') tardanzas,SUM(r.estado='JUSTIFICADA') justificadas
            FROM asistencia_clases ac LEFT JOIN asistencia_registros r ON r.id_clase=ac.idClase AND " . ModeloTenant::asistenciaRegistros('r') . '
            WHERE ac.id_seccion=? AND ' . ModeloTenant::asistenciaClases('ac') . '
            GROUP BY ac.idClase ORDER BY ac.fechaClase DESC');
        $stmt->execute([(int)$idSeccion]); return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlClase($idClase)
    {
        self::prepararTablas(); ModeloTenant::exigirClaseAsistencia($idClase);
        $stmt = Conexion::conectar()->prepare('SELECT ac.*,s.tituloSeccion materia,cu.nombreCurso curso
            FROM asistencia_clases ac INNER JOIN secciones s ON s.idSeccion=ac.id_seccion INNER JOIN cursos cu ON cu.idCurso=ac.id_curso
            WHERE ac.idClase=? AND ' . ModeloTenant::asistenciaClases('ac'));
        $stmt->execute([(int)$idClase]); return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function mdlRegistrosClase($idClase)
    {
        self::prepararTablas(); ModeloTenant::exigirClaseAsistencia($idClase);
        $stmt = Conexion::conectar()->prepare('SELECT r.*,u.nombreUsuario,u.apellidoUsuario FROM asistencia_registros r
            INNER JOIN usuarios u ON u.idUsuario=r.id_estudiante WHERE r.id_clase=? AND ' . ModeloTenant::asistenciaRegistros('r') . '
            ORDER BY u.apellidoUsuario,u.nombreUsuario');
        $stmt->execute([(int)$idClase]); return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlResumenEstudiantesSeccion($idSeccion)
    {
        self::prepararTablas(); ModeloTenant::exigirSeccion($idSeccion);
        $stmt = Conexion::conectar()->prepare("SELECT r.id_estudiante,COUNT(*) clases,SUM(r.estado='AUSENTE') ausentes,
                SUM(r.estado='TARDANZA') tardanzas,SUM(r.estado='JUSTIFICADA') justificadas,
                ROUND(100*SUM(r.estado IN ('PRESENTE','TARDANZA','JUSTIFICADA'))/COUNT(*),1) porcentaje
            FROM asistencia_registros r INNER JOIN asistencia_clases ac ON ac.idClase=r.id_clase
            WHERE ac.id_seccion=? AND " . ModeloTenant::asistenciaClases('ac') . ' AND ' . ModeloTenant::asistenciaRegistros('r') . '
            GROUP BY r.id_estudiante');
        $stmt->execute([(int)$idSeccion]); return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlGuardar($idClase, array $estados, array $observaciones, $idUsuario)
    {
        self::prepararTablas();
        ModeloTenant::exigirClaseAsistencia($idClase);
        ModeloTenant::exigirUsuario($idUsuario, ['ADMINISTRADOR', 'DOCENTE']);
        $permitidos = array_fill_keys(array_map('intval', array_column(self::mdlRegistrosClase($idClase), 'id_estudiante')), true);
        $stmt = Conexion::conectar()->prepare('UPDATE asistencia_registros r SET estado=?,observacion=?,actualizadoPor=?,fechaActualizacion=NOW()
            WHERE r.id_clase=? AND r.id_estudiante=? AND ' . ModeloTenant::asistenciaRegistros('r') . '
            AND ' . ModeloTenant::usuarioIdConRol($idUsuario, ['ADMINISTRADOR', 'DOCENTE']));
        foreach ($estados as $idEstudiante => $estado) {
            $idEstudiante = (int)$idEstudiante; $estado = strtoupper((string)$estado);
            if (!isset($permitidos[$idEstudiante]) || !in_array($estado, ['PRESENTE','AUSENTE','TARDANZA','JUSTIFICADA'], true)) { continue; }
            if (!$stmt->execute([$estado, trim((string)($observaciones[$idEstudiante] ?? '')), (int)$idUsuario, (int)$idClase, $idEstudiante])) { return 'error'; }
        }
        return 'ok';
    }

    public static function mdlResumenEstudiante($idEstudiante)
    {
        self::prepararTablas(); ModeloTenant::exigirUsuario($idEstudiante, ['ESTUDIANTE']);
        $stmt = Conexion::conectar()->prepare("SELECT s.tituloSeccion materia,c.nombreCurso curso,COUNT(*) clases,
                SUM(r.estado='PRESENTE') presentes,SUM(r.estado='AUSENTE') ausentes,SUM(r.estado='TARDANZA') tardanzas,
                SUM(r.estado='JUSTIFICADA') justificadas,ROUND(100*SUM(r.estado IN ('PRESENTE','TARDANZA','JUSTIFICADA'))/COUNT(*),1) porcentaje
            FROM asistencia_registros r INNER JOIN asistencia_clases ac ON ac.idClase=r.id_clase
            INNER JOIN secciones s ON s.idSeccion=ac.id_seccion INNER JOIN cursos c ON c.idCurso=ac.id_curso
            WHERE r.id_estudiante=? AND " . ModeloTenant::asistenciaRegistros('r') . '
            GROUP BY ac.id_seccion ORDER BY c.nombreCurso,s.tituloSeccion');
        $stmt->execute([(int)$idEstudiante]); return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
