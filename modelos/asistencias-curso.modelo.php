<?php
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/tenant.modelo.php';

class ModeloAsistenciasCurso
{
    public static function disponible()
    {
        $stmt = Conexion::conectar()->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE()
            AND table_name IN ('asistencia_curso_clases','asistencia_curso_registros')");
        return (int)$stmt->fetchColumn() === 2;
    }

    private static function asignado($alias, $idUsuario)
    {
        $idUsuario = (int)$idUsuario;
        return '(' . $alias . '.responsable=' . $idUsuario . ' OR EXISTS (SELECT 1 FROM secciones docente_s
            WHERE docente_s.id_curso=' . $alias . '.idCurso AND (docente_s.docente=' . $idUsuario . ' OR docente_s.tutor=' . $idUsuario . ')))';
    }

    public static function cursos($idDocente = 0)
    {
        $sql = 'SELECT c.idCurso,c.nombreCurso curso,COUNT(ac.idClase) clases FROM cursos c
            LEFT JOIN asistencia_curso_clases ac ON ac.id_curso=c.idCurso
            WHERE ' . ModeloTenant::cursos('c');
        if ((int)$idDocente > 0) { $sql .= ' AND ' . self::asignado('c', $idDocente); }
        $sql .= ' GROUP BY c.idCurso,c.nombreCurso ORDER BY c.nombreCurso';
        return Conexion::conectar()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function puedeGestionar($idCurso, $idUsuario, $esAdministrador)
    {
        ModeloTenant::exigirUsuario($idUsuario, ['ADMINISTRADOR', 'DOCENTE']);
        $sql = 'SELECT 1 FROM cursos c WHERE c.idCurso=? AND ' . ModeloTenant::cursos('c');
        if (!$esAdministrador) { $sql .= ' AND ' . self::asignado('c', $idUsuario); }
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute([(int)$idCurso]);
        return (bool)$stmt->fetchColumn();
    }

    private static function exigirGestion($idCurso, $idUsuario)
    {
        // La autorización se comprueba también en el modelo para las escrituras directas.
        if ((int)($_SESSION['usuario']['id'] ?? 0) !== (int)$idUsuario
            || !ControladorPermisos::puedeAccederRuta('asistencia-curso')
            || !self::puedeGestionar($idCurso, $idUsuario, ControladorPermisos::esAdministrador())) {
            throw new RuntimeException('No tenés permisos para gestionar esta asistencia.');
        }
    }

    public static function crearClase($idCurso, $fecha, $tema, $idUsuario)
    {
        self::exigirGestion($idCurso, $idUsuario);
        $fechaValida = DateTime::createFromFormat('!Y-m-d', $fecha);
        if (!$fechaValida || $fechaValida->format('Y-m-d') !== $fecha) { throw new InvalidArgumentException('Fecha inválida.'); }
        $pdo = Conexion::conectar();
        $pdo->beginTransaction();
        try {
            // Reabrir una fecha no cambia el tema ni reinicia estados ya guardados.
            $stmt = $pdo->prepare('INSERT INTO asistencia_curso_clases(id_curso,fechaClase,tema,creadaPor)
                SELECT c.idCurso,?,?,? FROM cursos c WHERE c.idCurso=? AND ' . ModeloTenant::cursos('c') . '
                ON DUPLICATE KEY UPDATE idClase=LAST_INSERT_ID(idClase)');
            $stmt->execute([$fecha, $tema, (int)$idUsuario, (int)$idCurso]);
            $buscar = $pdo->prepare('SELECT ac.idClase FROM asistencia_curso_clases ac INNER JOIN cursos c ON c.idCurso=ac.id_curso
                WHERE ac.id_curso=? AND ac.fechaClase=? AND ' . ModeloTenant::cursos('c'));
            $buscar->execute([(int)$idCurso, $fecha]);
            $idClase = (int)$buscar->fetchColumn();
            if (!$idClase) { throw new RuntimeException('Acceso institucional denegado.'); }
            // asignacioncursos.id_seccion es el ID del curso en el esquema existente.
            $alumnos = $pdo->prepare("INSERT INTO asistencia_curso_registros(id_clase,id_estudiante,estado,actualizadoPor)
                SELECT DISTINCT ?,a.id_estudiante,'PRESENTE',? FROM asignacioncursos a
                INNER JOIN usuarios u ON u.idUsuario=a.id_estudiante
                WHERE a.id_seccion=? AND u.activo=1 AND (a.fechaAlta IS NULL OR DATE(a.fechaAlta)<=?)
                AND (a.fechaBaja IS NULL OR DATE(a.fechaBaja)>?)
                AND (a.estadoInscripcion='ACTIVA' OR a.fechaBaja IS NOT NULL)
                AND " . ModeloTenant::cursoId($idCurso) . ' AND ' . ModeloTenant::usuarioConRol('u.idUsuario', ['ESTUDIANTE']) . '
                ON DUPLICATE KEY UPDATE idAsistencia=idAsistencia');
            $alumnos->execute([$idClase, (int)$idUsuario, (int)$idCurso, $fecha, $fecha]);
            $pdo->commit();
            return $idClase;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function clases($idCurso)
    {
        ModeloTenant::exigirCurso($idCurso);
        $stmt = Conexion::conectar()->prepare("SELECT ac.*,COUNT(r.idAsistencia) total,
                SUM(r.estado='PRESENTE') presentes,SUM(r.estado='AUSENTE') ausentes
            FROM asistencia_curso_clases ac INNER JOIN cursos c ON c.idCurso=ac.id_curso
            LEFT JOIN asistencia_curso_registros r ON r.id_clase=ac.idClase
            WHERE ac.id_curso=? AND " . ModeloTenant::cursos('c') . ' GROUP BY ac.idClase ORDER BY ac.fechaClase DESC');
        $stmt->execute([(int)$idCurso]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function clase($idClase, $idCurso)
    {
        $stmt = Conexion::conectar()->prepare('SELECT ac.* FROM asistencia_curso_clases ac INNER JOIN cursos c ON c.idCurso=ac.id_curso
            WHERE ac.idClase=? AND ac.id_curso=? AND ' . ModeloTenant::cursos('c'));
        $stmt->execute([(int)$idClase, (int)$idCurso]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function registros($idClase, $idCurso)
    {
        if (!self::clase($idClase, $idCurso)) { throw new RuntimeException('Acceso institucional denegado.'); }
        $stmt = Conexion::conectar()->prepare('SELECT r.*,u.nombreUsuario,u.apellidoUsuario FROM asistencia_curso_registros r
            INNER JOIN usuarios u ON u.idUsuario=r.id_estudiante
            INNER JOIN asistencia_curso_clases ac ON ac.idClase=r.id_clase INNER JOIN cursos c ON c.idCurso=ac.id_curso
            WHERE r.id_clase=? AND ' . ModeloTenant::cursos('c') . ' ORDER BY u.apellidoUsuario,u.nombreUsuario');
        $stmt->execute([(int)$idClase]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function guardar($idClase, $idCurso, array $estados, array $observaciones, $idUsuario)
    {
        self::exigirGestion($idCurso, $idUsuario);
        if (!self::clase($idClase, $idCurso)) { throw new RuntimeException('Acceso institucional denegado.'); }
        $pdo = Conexion::conectar();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('UPDATE asistencia_curso_registros r
                INNER JOIN asistencia_curso_clases ac ON ac.idClase=r.id_clase INNER JOIN cursos c ON c.idCurso=ac.id_curso
                SET r.estado=?,r.observacion=?,r.actualizadoPor=?,r.fechaActualizacion=NOW()
                WHERE r.id_clase=? AND ac.id_curso=? AND r.id_estudiante=? AND ' . ModeloTenant::cursos('c'));
            foreach ($estados as $idEstudiante => $estado) {
                if (!in_array($estado, ['PRESENTE','AUSENTE','TARDANZA','JUSTIFICADA'], true)) { throw new InvalidArgumentException('Estado inválido.'); }
                $stmt->execute([$estado, trim((string)($observaciones[$idEstudiante] ?? '')), (int)$idUsuario, (int)$idClase, (int)$idCurso, (int)$idEstudiante]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function resumenCurso($idCurso)
    {
        ModeloTenant::exigirCurso($idCurso);
        $stmt = Conexion::conectar()->prepare("SELECT r.id_estudiante,COUNT(*) clases,SUM(r.estado='AUSENTE') ausentes,
                SUM(r.estado='TARDANZA') tardanzas,SUM(r.estado='JUSTIFICADA') justificadas
            FROM asistencia_curso_registros r INNER JOIN asistencia_curso_clases ac ON ac.idClase=r.id_clase
            INNER JOIN cursos c ON c.idCurso=ac.id_curso
            WHERE ac.id_curso=? AND " . ModeloTenant::cursos('c') . ' GROUP BY r.id_estudiante');
        $stmt->execute([(int)$idCurso]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function resumenEstudiante($idEstudiante)
    {
        ModeloTenant::exigirUsuario($idEstudiante, ['ESTUDIANTE']);
        $stmt = Conexion::conectar()->prepare("SELECT c.nombreCurso curso,COUNT(*) clases,SUM(r.estado='PRESENTE') presentes,
                SUM(r.estado='AUSENTE') ausentes,SUM(r.estado='TARDANZA') tardanzas,SUM(r.estado='JUSTIFICADA') justificadas,
                ROUND(100*SUM(r.estado IN ('PRESENTE','TARDANZA','JUSTIFICADA'))/COUNT(*),1) porcentaje
            FROM asistencia_curso_registros r INNER JOIN asistencia_curso_clases ac ON ac.idClase=r.id_clase
            INNER JOIN cursos c ON c.idCurso=ac.id_curso WHERE r.id_estudiante=? AND " . ModeloTenant::cursos('c') . '
            GROUP BY c.idCurso,c.nombreCurso ORDER BY c.nombreCurso');
        $stmt->execute([(int)$idEstudiante]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
