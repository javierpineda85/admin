<?php
require_once('conexion.php');

class ModeloCursos
{
    static public function mdlBuscarCursoPorId($idCurso)
    {
        $stmt = Conexion::conectar()->prepare("
            SELECT c.*,
                   DATE_FORMAT(c.fechaInicioCurso, '%d/%m/%Y') AS fInicio,
                   DATE_FORMAT(c.fechaFinCurso, '%d/%m/%Y') AS fFin,
                   CONCAT(u.nombreUsuario, ' ', u.apellidoUsuario) AS creadorNombre
            FROM cursos c
            LEFT JOIN usuarios u ON u.idUsuario = c.creadoPor
            WHERE c.idCurso = :idCurso
            LIMIT 1
        ");
        $stmt->bindValue(':idCurso', (int) $idCurso, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    static public function mdlListarCursos()
    {
        $stmt = Conexion::conectar()->prepare("
            SELECT c.*,
                   DATE_FORMAT(c.fechaInicioCurso, '%d/%m/%Y') AS fInicio,
                   DATE_FORMAT(c.fechaFinCurso, '%d/%m/%Y') AS fFin,
                   COUNT(DISTINCT s.idSeccion) AS totalSecciones,
                   COUNT(DISTINCT l.idLeccion) AS totalLecciones
            FROM cursos c
            LEFT JOIN secciones s ON s.id_curso = c.idCurso
            LEFT JOIN lecciones l ON l.id_modulo = s.idSeccion
            GROUP BY c.idCurso, c.nombreCurso, c.contenidoCurso, c.estado,
                     c.fechaInicioCurso, c.fechaFinCurso, c.horarioCurso
            ORDER BY c.nombreCurso ASC
        ");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlCursosPorEstudiante($idEstudiante)
    {
        $stmt = Conexion::conectar()->prepare("
            SELECT DISTINCT c.*,
                   DATE_FORMAT(c.fechaInicioCurso, '%d/%m/%Y') AS fInicio,
                   DATE_FORMAT(c.fechaFinCurso, '%d/%m/%Y') AS fFin,
                   COUNT(DISTINCT s.idSeccion) AS totalSecciones,
                   COUNT(DISTINCT l.idLeccion) AS totalLecciones
            FROM asignacioncursos a
            INNER JOIN cursos c ON c.idCurso = a.id_seccion
            LEFT JOIN secciones s ON s.id_curso = c.idCurso
            LEFT JOIN lecciones l ON l.id_modulo = s.idSeccion
            WHERE a.id_estudiante = :idEstudiante
              AND c.activo = 1
            GROUP BY c.idCurso, c.nombreCurso, c.contenidoCurso, c.estado, c.fechaInicioCurso, c.fechaFinCurso, c.horarioCurso
            ORDER BY c.nombreCurso ASC
        ");
        $stmt->bindValue(':idEstudiante', (int) $idEstudiante, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlCursosPorDocente($idDocente)
    {
        $stmt = Conexion::conectar()->prepare("
            SELECT DISTINCT c.*,
                   DATE_FORMAT(c.fechaInicioCurso, '%d/%m/%Y') AS fInicio,
                   DATE_FORMAT(c.fechaFinCurso, '%d/%m/%Y') AS fFin,
                   COUNT(DISTINCT s.idSeccion) AS totalSecciones,
                   COUNT(DISTINCT l.idLeccion) AS totalLecciones
            FROM cursos c
            LEFT JOIN secciones s ON c.idCurso = s.id_curso
            LEFT JOIN lecciones l ON l.id_modulo = s.idSeccion
            WHERE (c.creadoPor = :idDocente
               OR s.docente = :idDocente
               OR s.tutor = :idDocente)
              AND c.activo = 1
            GROUP BY c.idCurso, c.nombreCurso, c.contenidoCurso, c.estado, c.fechaInicioCurso, c.fechaFinCurso, c.horarioCurso, c.creadoPor
            ORDER BY c.nombreCurso ASC
        ");
        $stmt->bindValue(':idDocente', (int) $idDocente, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlDocentePuedeGestionarCurso($idCurso, $idDocente)
    {
        $stmt = Conexion::conectar()->prepare("
            SELECT COUNT(*) AS total
            FROM cursos c
            WHERE c.idCurso = :idCurso
              AND c.creadoPor = :idDocente
              AND c.activo = 1
        ");
        $stmt->bindValue(':idCurso', (int) $idCurso, PDO::PARAM_INT);
        $stmt->bindValue(':idDocente', (int) $idDocente, PDO::PARAM_INT);
        $stmt->execute();

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0) > 0;
    }

    static public function mdlEstudianteInscriptoCurso($idEstudiante, $idCurso)
    {
        $stmt = Conexion::conectar()->prepare("
            SELECT COUNT(*) AS total
            FROM asignacioncursos
            WHERE id_estudiante = :idEstudiante
              AND id_seccion = :idCurso
        ");
        $stmt->bindValue(':idEstudiante', (int) $idEstudiante, PDO::PARAM_INT);
        $stmt->bindValue(':idCurso', (int) $idCurso, PDO::PARAM_INT);
        $stmt->execute();

        return (int) (($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0)) > 0;
    }

    static public function mdlSeccionesPorCurso($idCurso)
    {
        $stmt = Conexion::conectar()->prepare("
            SELECT s.idSeccion, s.tituloSeccion, s.contenidoSeccion, s.id_curso, s.docente, s.tutor,
                   c.nombreCurso,
                   u.nombreUsuario, u.apellidoUsuario,
                   COUNT(DISTINCT l.idLeccion) AS totalLecciones,
                   SUM(CASE WHEN l.tipoLeccion = 'TAREA' THEN 1 ELSE 0 END) AS totalTareas,
                   SUM(CASE WHEN l.tipoLeccion = 'MATERIAL' THEN 1 ELSE 0 END) AS totalMateriales,
                   SUM(CASE WHEN l.tipoLeccion = 'PREGUNTA' THEN 1 ELSE 0 END) AS totalPreguntas
            FROM secciones s
            INNER JOIN cursos c ON c.idCurso = s.id_curso
            INNER JOIN usuarios u ON u.idUsuario = s.docente
            LEFT JOIN lecciones l ON l.id_modulo = s.idSeccion
            WHERE s.id_curso = :idCurso
            GROUP BY s.idSeccion, s.tituloSeccion, s.contenidoSeccion, s.id_curso, s.docente, s.tutor,
                     c.nombreCurso, u.nombreUsuario, u.apellidoUsuario
            ORDER BY s.tituloSeccion ASC
        ");
        $stmt->bindValue(':idCurso', (int) $idCurso, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlSeccionesPorCursoParaDocente($idCurso, $idDocente)
    {
        $stmt = Conexion::conectar()->prepare("
            SELECT s.idSeccion, s.tituloSeccion, s.contenidoSeccion, s.id_curso, s.docente, s.tutor,
                   c.nombreCurso,
                   u.nombreUsuario, u.apellidoUsuario,
                   COUNT(DISTINCT l.idLeccion) AS totalLecciones,
                   SUM(CASE WHEN l.tipoLeccion = 'TAREA' THEN 1 ELSE 0 END) AS totalTareas,
                   SUM(CASE WHEN l.tipoLeccion = 'MATERIAL' THEN 1 ELSE 0 END) AS totalMateriales,
                   SUM(CASE WHEN l.tipoLeccion = 'PREGUNTA' THEN 1 ELSE 0 END) AS totalPreguntas
            FROM secciones s
            INNER JOIN cursos c ON c.idCurso = s.id_curso
            INNER JOIN usuarios u ON u.idUsuario = s.docente
            LEFT JOIN lecciones l ON l.id_modulo = s.idSeccion
            WHERE s.id_curso = :idCurso
              AND (s.docente = :idDocente OR s.tutor = :idDocente)
            GROUP BY s.idSeccion, s.tituloSeccion, s.contenidoSeccion, s.id_curso, s.docente, s.tutor,
                     c.nombreCurso, u.nombreUsuario, u.apellidoUsuario
            ORDER BY s.tituloSeccion ASC
        ");
        $stmt->bindValue(':idCurso', (int) $idCurso, PDO::PARAM_INT);
        $stmt->bindValue(':idDocente', (int) $idDocente, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* GUARDAR CURSO */
    static public function mdlGuardarCurso($tabla, $datos)
    {

        $registro = Conexion::conectar()->prepare("INSERT INTO $tabla(nombreCurso, contenidoCurso, estado, fechaInicioCurso, fechaFinCurso, horarioCurso, creadoPor) VALUES(:nombreCurso, :contenidoCurso, :estado, :fechaInicioCurso, :fechaFinCurso, :horarioCurso, :creadoPor)");

        $registro->bindParam(":nombreCurso", $datos["nombreCurso"], PDO::PARAM_STR);
        $registro->bindParam(":contenidoCurso", $datos["contenidoCurso"], PDO::PARAM_STR);
        $registro->bindParam(":estado", $datos["estado"], PDO::PARAM_STR);
        $registro->bindParam(":fechaInicioCurso", $datos["fechaInicioCurso"], PDO::PARAM_STR);
        $registro->bindParam(":fechaFinCurso", $datos["fechaFinCurso"], PDO::PARAM_STR);
        $registro->bindParam(":horarioCurso", $datos["horarioCurso"], PDO::PARAM_STR);
        $registro->bindParam(":creadoPor", $datos["creadoPor"], PDO::PARAM_INT);

        if ($registro->execute()) {
            return "ok";
        } else {
            print_r(Conexion::conectar()->errorInfo());
        }

        $registro->closeCursor();
        $registro = null;
    }

    /*EDITAR CURSO */

    static public function mdlModificarCurso($tabla, $datos)
    {

        $registro = Conexion::conectar()->prepare("UPDATE $tabla SET nombreCurso=:nombreCurso, contenidoCurso = :contenidoCurso, estado = :estado, fechaInicioCurso = :fechaInicioCurso, fechaFinCurso = :fechaFinCurso, horarioCurso = :horarioCurso WHERE idCurso = :idCurso");
        $registro->bindParam(":idCurso", $datos["idCurso"], PDO::PARAM_INT);
        $registro->bindParam(":nombreCurso", $datos["nombreCurso"], PDO::PARAM_STR);
        $registro->bindParam(":contenidoCurso", $datos["contenidoCurso"], PDO::PARAM_STR);
        $registro->bindParam(":estado", $datos["estado"], PDO::PARAM_STR);
        $registro->bindParam(":fechaInicioCurso", $datos["fechaInicioCurso"], PDO::PARAM_STR);
        $registro->bindParam(":fechaFinCurso", $datos["fechaFinCurso"], PDO::PARAM_STR);
        $registro->bindParam(":horarioCurso", $datos["horarioCurso"], PDO::PARAM_STR);

        if ($registro->execute()) {
            return "ok";
        } else {
            print_r(Conexion::conectar()->errorInfo());
        }

        $registro->closeCursor();
        $registro = null;
    }

    static public function mdlCambiarEstadoActivoCurso($idCurso, $activo, $motivo, $idAdministrador)
    {
        $stmt = Conexion::conectar()->prepare("
            UPDATE cursos
            SET activo = :activo,
                fechaBaja = :fechaBaja,
                motivoBaja = :motivoBaja,
                usuarioBaja = :usuarioBaja
            WHERE idCurso = :idCurso
        ");
        $esActivo = (int) $activo === 1;
        $stmt->bindValue(':activo', $esActivo ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':fechaBaja', $esActivo ? null : date('Y-m-d H:i:s'), $esActivo ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':motivoBaja', $esActivo ? null : trim((string) $motivo), $esActivo ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':usuarioBaja', $esActivo ? null : (int) $idAdministrador, $esActivo ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':idCurso', (int) $idCurso, PDO::PARAM_INT);

        return $stmt->execute() ? 'ok' : 'error';
    }

    static public function mdlDependenciasCurso($idCurso)
    {
        $relaciones = [
            'secciones' => 'id_curso',
            'asignacioncursos' => 'id_seccion',
            'calificaciones' => 'id_curso',
            'entregaslecciones' => 'id_curso',
            'posteos' => 'id_curso',
            'evaluaciones' => 'id_curso',
            'actividades' => 'id_curso',
        ];
        $pdo = Conexion::conectar();
        $dependencias = [];

        foreach ($relaciones as $tabla => $columna) {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM {$tabla} WHERE {$columna} = :idCurso");
                $stmt->bindValue(':idCurso', (int) $idCurso, PDO::PARAM_INT);
                $stmt->execute();
                $total = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
                if ($total > 0) {
                    $dependencias[$tabla] = $total;
                }
            } catch (PDOException $e) {
                // Algunas instalaciones antiguas aun no tienen todas las tablas opcionales.
                if ((int) ($e->errorInfo[1] ?? 0) !== 1146) {
                    throw $e;
                }
            }
        }

        return $dependencias;
    }

    static public function mdlEliminarCurso($idCurso)
    {
        $stmt = Conexion::conectar()->prepare('DELETE FROM cursos WHERE idCurso = :idCurso');
        $stmt->bindValue(':idCurso', (int) $idCurso, PDO::PARAM_INT);

        return $stmt->execute() && $stmt->rowCount() === 1 ? 'ok' : 'error';
    }

    /* ASIGNAR CURSO */
    static public function mdlEstudiantesDisponiblesCurso($idCurso)
    {
        $stmt = Conexion::conectar()->prepare("
            SELECT u.*
            FROM usuarios u
            WHERE u.rol = 'ESTUDIANTE'
              AND u.activo = 1
              AND NOT EXISTS (
                  SELECT 1
                  FROM asignacioncursos a
                  WHERE a.id_estudiante = u.idUsuario
                    AND a.id_seccion = :idCurso
              )
            ORDER BY u.apellidoUsuario ASC, u.nombreUsuario ASC
        ");
        $stmt->bindValue(':idCurso', (int) $idCurso, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    static public function mdlAsignarCurso($tabla, $datos)
    {
        $registro = Conexion::conectar()->prepare("
            INSERT INTO $tabla (id_estudiante,id_seccion)
            SELECT u.idUsuario, :idCurso
            FROM usuarios u
            WHERE u.idUsuario = :idUsuario
              AND u.rol = 'ESTUDIANTE'
              AND u.activo = 1
              AND NOT EXISTS (
                SELECT 1
                FROM $tabla
                WHERE id_estudiante = :idUsuarioExiste
                  AND id_seccion = :idCursoExiste
            )
        ");

        $registro->bindParam(":idCurso", $datos["idCurso"], PDO::PARAM_INT);
        $registro->bindParam(":idUsuario", $datos["idUsuario"], PDO::PARAM_INT);
        $registro->bindParam(":idCursoExiste", $datos["idCurso"], PDO::PARAM_INT);
        $registro->bindParam(":idUsuarioExiste", $datos["idUsuario"], PDO::PARAM_INT);

     
        if ($registro->execute()) {
            return $registro->rowCount() === 1 ? "ok" : "exists";
        } else {
            print_r(Conexion::conectar()->errorInfo());
        }

        $registro->closeCursor();
        $registro = null;
        
    }

    static public function mdlQuitarEstudianteCurso($idCurso, $idUsuario)
    {
        $registro = Conexion::conectar()->prepare("
            DELETE FROM asignacioncursos
            WHERE id_seccion = :idCurso
              AND id_estudiante = :idUsuario
        ");
        $registro->bindValue(":idCurso", (int) $idCurso, PDO::PARAM_INT);
        $registro->bindValue(":idUsuario", (int) $idUsuario, PDO::PARAM_INT);

        return $registro->execute() ? "ok" : "error";
    }
}
