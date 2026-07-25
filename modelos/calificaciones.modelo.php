<?php
require_once('conexion.php');

class ModeloCalificaciones
{
    private static $tablasEvaluacionesPreparadas = false;

    private static function prepararTablasEvaluaciones()
    {
        if (self::$tablasEvaluacionesPreparadas) {
            return;
        }

        $pdo = Conexion::conectar();
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS evaluaciones (
                idEvaluacion INT NOT NULL AUTO_INCREMENT,
                id_seccion INT NOT NULL,
                id_curso INT NOT NULL,
                id_autor INT NOT NULL,
                temaEvaluacion VARCHAR(180) NOT NULL,
                fechaEvaluacion DATE NOT NULL,
                fechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (idEvaluacion),
                KEY idx_evaluaciones_seccion (id_seccion, fechaEvaluacion),
                KEY idx_evaluaciones_autor (id_autor)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS evaluaciones_calificaciones (
                idEvaluacionCalificacion INT NOT NULL AUTO_INCREMENT,
                id_evaluacion INT NOT NULL,
                id_estudiante INT NOT NULL,
                calificacion DECIMAL(5,2) NOT NULL,
                devolucion TEXT NULL,
                fechaActualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (idEvaluacionCalificacion),
                UNIQUE KEY uq_evaluacion_estudiante (id_evaluacion, id_estudiante),
                KEY idx_evaluaciones_calificaciones_estudiante (id_estudiante)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        self::$tablasEvaluacionesPreparadas = true;
    }

    public static function mdlGuardarCalificacion($datos)
    {
        $stmt = Conexion::conectar()->prepare(
            'INSERT INTO calificaciones (id_estudiante, id_seccion, id_modulo, id_curso, calificacion, devolucion)
             VALUES (:id_estudiante, :id_seccion, :id_modulo, :id_curso, :calificacion, :devolucion)
             ON DUPLICATE KEY UPDATE
                id_curso = VALUES(id_curso),
                calificacion = VALUES(calificacion),
                devolucion = VALUES(devolucion)'
        );
        $stmt->bindValue(':id_estudiante', (int) $datos['id_estudiante'], PDO::PARAM_INT);
        $stmt->bindValue(':id_seccion', (int) $datos['id_seccion'], PDO::PARAM_INT);
        $stmt->bindValue(':id_modulo', (int) $datos['id_modulo'], PDO::PARAM_INT);
        $stmt->bindValue(':id_curso', (int) $datos['id_curso'], PDO::PARAM_INT);
        $stmt->bindValue(':calificacion', (int) $datos['calificacion'], PDO::PARAM_INT);
        $stmt->bindValue(':devolucion', (string) ($datos['devolucion'] ?? ''), PDO::PARAM_STR);
        return $stmt->execute() ? 'ok' : 'error';
    }

    public static function mdlGuardarCalificaciones(array $calificaciones)
    {
        if (empty($calificaciones)) {
            return 'error';
        }

        $valores = [];
        $params = [];

        foreach (array_values($calificaciones) as $indice => $calificacion) {
            $valores[] = "(:id_estudiante_{$indice}, :id_seccion_{$indice}, :id_modulo_{$indice}, :id_curso_{$indice}, :calificacion_{$indice}, :devolucion_{$indice})";
            $params[":id_estudiante_{$indice}"] = (int) $calificacion['id_estudiante'];
            $params[":id_seccion_{$indice}"] = (int) $calificacion['id_seccion'];
            $params[":id_modulo_{$indice}"] = (int) $calificacion['id_modulo'];
            $params[":id_curso_{$indice}"] = (int) $calificacion['id_curso'];
            $params[":calificacion_{$indice}"] = (int) $calificacion['calificacion'];
            $params[":devolucion_{$indice}"] = (string) ($calificacion['devolucion'] ?? '');
        }

        $stmt = Conexion::conectar()->prepare(
            'INSERT INTO calificaciones
                (id_estudiante, id_seccion, id_modulo, id_curso, calificacion, devolucion)
             VALUES ' . implode(', ', $valores) . '
             ON DUPLICATE KEY UPDATE
                id_curso = VALUES(id_curso),
                calificacion = VALUES(calificacion),
                devolucion = VALUES(devolucion)'
        );

        return $stmt->execute($params) ? 'ok' : 'error';
    }

    public static function mdlCalificacionesPorSeccion($idSeccion)
    {
        $stmt = Conexion::conectar()->prepare(
            'SELECT c.idCalificacion, c.id_estudiante, c.id_seccion, c.id_modulo, c.id_curso, c.calificacion,
                    c.devolucion,
                    l.nombreLeccion, l.tipoLeccion,
                    u.nombreUsuario, u.apellidoUsuario, u.email
             FROM calificaciones c
             INNER JOIN usuarios u ON u.idUsuario = c.id_estudiante
             LEFT JOIN lecciones l ON l.idLeccion = c.id_modulo
             WHERE c.id_seccion = :idSeccion
             ORDER BY c.idCalificacion DESC'
        );
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlCalificacionesPorEstudiante($idSeccion, $idEstudiante)
    {
        $stmt = Conexion::conectar()->prepare(
            'SELECT c.idCalificacion, c.id_estudiante, c.id_seccion, c.id_modulo, c.id_curso, c.calificacion,
                    c.devolucion,
                    l.nombreLeccion, l.tipoLeccion
             FROM calificaciones c
             LEFT JOIN lecciones l ON l.idLeccion = c.id_modulo
             WHERE c.id_seccion = :idSeccion
               AND c.id_estudiante = :idEstudiante
             ORDER BY c.idCalificacion DESC'
        );
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
        $stmt->bindValue(':idEstudiante', (int) $idEstudiante, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlCalificacionPorLeccionYEstudiante($idSeccion, $idLeccion, $idEstudiante)
    {
        $stmt = Conexion::conectar()->prepare(
            'SELECT c.idCalificacion, c.id_estudiante, c.id_seccion, c.id_modulo, c.id_curso, c.calificacion, c.devolucion,
                    l.nombreLeccion, l.tipoLeccion
             FROM calificaciones c
             LEFT JOIN lecciones l ON l.idLeccion = c.id_modulo
             WHERE c.id_seccion = :idSeccion
               AND c.id_modulo = :idLeccion
               AND c.id_estudiante = :idEstudiante
             LIMIT 1'
        );
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
        $stmt->bindValue(':idLeccion', (int) $idLeccion, PDO::PARAM_INT);
        $stmt->bindValue(':idEstudiante', (int) $idEstudiante, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function mdlCalificacionPorSeccionYEstudiante($idSeccion, $idEstudiante)
    {
        $stmt = Conexion::conectar()->prepare(
            'SELECT c.idCalificacion, c.id_estudiante, c.id_seccion, c.id_modulo, c.id_curso, c.calificacion, c.devolucion,
                    l.nombreLeccion, l.tipoLeccion
             FROM calificaciones c
             LEFT JOIN lecciones l ON l.idLeccion = c.id_modulo
             WHERE c.id_seccion = :idSeccion
               AND c.id_estudiante = :idEstudiante
             ORDER BY c.idCalificacion DESC'
        );
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
        $stmt->bindValue(':idEstudiante', (int) $idEstudiante, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlCrearEvaluacion(array $datos)
    {
        self::prepararTablasEvaluaciones();

        $pdo = Conexion::conectar();
        $stmt = $pdo->prepare('
            INSERT INTO evaluaciones (id_seccion, id_curso, id_autor, temaEvaluacion, fechaEvaluacion)
            VALUES (:id_seccion, :id_curso, :id_autor, :temaEvaluacion, :fechaEvaluacion)
        ');
        $stmt->bindValue(':id_seccion', (int) $datos['id_seccion'], PDO::PARAM_INT);
        $stmt->bindValue(':id_curso', (int) $datos['id_curso'], PDO::PARAM_INT);
        $stmt->bindValue(':id_autor', (int) $datos['id_autor'], PDO::PARAM_INT);
        $stmt->bindValue(':temaEvaluacion', (string) $datos['temaEvaluacion'], PDO::PARAM_STR);
        $stmt->bindValue(':fechaEvaluacion', (string) $datos['fechaEvaluacion'], PDO::PARAM_STR);

        if (!$stmt->execute()) {
            return 'error';
        }

        return (int) $pdo->lastInsertId();
    }

    public static function mdlActualizarEvaluacion(array $datos)
    {
        self::prepararTablasEvaluaciones();

        $stmt = Conexion::conectar()->prepare('
            UPDATE evaluaciones
            SET temaEvaluacion = :temaEvaluacion,
                fechaEvaluacion = :fechaEvaluacion
            WHERE idEvaluacion = :idEvaluacion
        ');
        $stmt->bindValue(':temaEvaluacion', (string) $datos['temaEvaluacion'], PDO::PARAM_STR);
        $stmt->bindValue(':fechaEvaluacion', (string) $datos['fechaEvaluacion'], PDO::PARAM_STR);
        $stmt->bindValue(':idEvaluacion', (int) $datos['idEvaluacion'], PDO::PARAM_INT);

        return $stmt->execute() ? 'ok' : 'error';
    }

    public static function mdlEliminarEvaluacion($idEvaluacion)
    {
        self::prepararTablasEvaluaciones();

        $stmt = Conexion::conectar()->prepare('
            DELETE e, ec
            FROM evaluaciones e
            LEFT JOIN evaluaciones_calificaciones ec ON ec.id_evaluacion = e.idEvaluacion
            WHERE e.idEvaluacion = :idEvaluacion
        ');
        $stmt->bindValue(':idEvaluacion', (int) $idEvaluacion, PDO::PARAM_INT);

        return $stmt->execute() ? 'ok' : 'error';
    }

    public static function mdlEvaluacionPorId($idEvaluacion)
    {
        self::prepararTablasEvaluaciones();

        $stmt = Conexion::conectar()->prepare('
            SELECT e.*, s.tituloSeccion, c.nombreCurso
            FROM evaluaciones e
            INNER JOIN secciones s ON s.idSeccion = e.id_seccion
            INNER JOIN cursos c ON c.idCurso = e.id_curso
            WHERE e.idEvaluacion = :idEvaluacion
            LIMIT 1
        ');
        $stmt->bindValue(':idEvaluacion', (int) $idEvaluacion, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function mdlEvaluacionesPorSeccion($idSeccion)
    {
        self::prepararTablasEvaluaciones();

        $stmt = Conexion::conectar()->prepare('
            SELECT e.*, u.nombreUsuario, u.apellidoUsuario,
                   COUNT(ec.idEvaluacionCalificacion) AS totalCalificados,
                   ROUND(AVG(ec.calificacion), 2) AS promedio
            FROM evaluaciones e
            LEFT JOIN usuarios u ON u.idUsuario = e.id_autor
            LEFT JOIN evaluaciones_calificaciones ec ON ec.id_evaluacion = e.idEvaluacion
            WHERE e.id_seccion = :idSeccion
            GROUP BY e.idEvaluacion
            ORDER BY e.fechaEvaluacion DESC, e.idEvaluacion DESC
        ');
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlEvaluacionesPorEstudiante($idSeccion, $idEstudiante)
    {
        self::prepararTablasEvaluaciones();

        $stmt = Conexion::conectar()->prepare('
            SELECT e.idEvaluacion, e.temaEvaluacion, e.fechaEvaluacion,
                   ec.calificacion, ec.devolucion, ec.fechaActualizacion
            FROM evaluaciones e
            INNER JOIN evaluaciones_calificaciones ec ON ec.id_evaluacion = e.idEvaluacion
            WHERE e.id_seccion = :idSeccion
              AND ec.id_estudiante = :idEstudiante
            ORDER BY e.fechaEvaluacion DESC, e.idEvaluacion DESC
        ');
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
        $stmt->bindValue(':idEstudiante', (int) $idEstudiante, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlEstudiantesPorCurso($idCurso)
    {
        self::prepararTablasEvaluaciones();

        $stmt = Conexion::conectar()->prepare('
            SELECT DISTINCT u.idUsuario, u.nombreUsuario, u.apellidoUsuario, u.email
            FROM asignacioncursos a
            INNER JOIN usuarios u ON u.idUsuario = a.id_estudiante
            WHERE a.id_seccion = :idCurso
              AND u.rol = "ESTUDIANTE"
              AND u.activo = 1
            ORDER BY u.apellidoUsuario ASC, u.nombreUsuario ASC
        ');
        $stmt->bindValue(':idCurso', (int) $idCurso, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlCalificacionesEvaluacion($idEvaluacion)
    {
        self::prepararTablasEvaluaciones();

        $stmt = Conexion::conectar()->prepare('
            SELECT ec.*, u.nombreUsuario, u.apellidoUsuario, u.email
            FROM evaluaciones_calificaciones ec
            INNER JOIN usuarios u ON u.idUsuario = ec.id_estudiante
            WHERE ec.id_evaluacion = :idEvaluacion
            ORDER BY u.apellidoUsuario ASC, u.nombreUsuario ASC
        ');
        $stmt->bindValue(':idEvaluacion', (int) $idEvaluacion, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlGuardarCalificacionesEvaluacion($idEvaluacion, array $calificaciones)
    {
        self::prepararTablasEvaluaciones();

        $stmt = Conexion::conectar()->prepare('
            INSERT INTO evaluaciones_calificaciones
                (id_evaluacion, id_estudiante, calificacion, devolucion, fechaActualizacion)
            VALUES
                (:id_evaluacion, :id_estudiante, :calificacion, :devolucion, NOW())
            ON DUPLICATE KEY UPDATE
                calificacion = VALUES(calificacion),
                devolucion = VALUES(devolucion),
                fechaActualizacion = NOW()
        ');

        try {
            foreach ($calificaciones as $calificacion) {
                $stmt->bindValue(':id_evaluacion', (int) $idEvaluacion, PDO::PARAM_INT);
                $stmt->bindValue(':id_estudiante', (int) $calificacion['id_estudiante'], PDO::PARAM_INT);
                $stmt->bindValue(':calificacion', (string) $calificacion['calificacion'], PDO::PARAM_STR);
                $stmt->bindValue(':devolucion', (string) ($calificacion['devolucion'] ?? ''), PDO::PARAM_STR);
                if (!$stmt->execute()) {
                    return 'error';
                }
            }
        } catch (Throwable $e) {
            return 'error';
        }

        return 'ok';
    }
}
