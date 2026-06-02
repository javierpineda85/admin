<?php
require_once('conexion.php');

class ModeloCursos
{
    static public function mdlBuscarCursoPorId($idCurso)
    {
        $stmt = Conexion::conectar()->prepare("
            SELECT *,
                   DATE_FORMAT(fechaInicioCurso, '%d/%m/%Y') AS fInicio,
                   DATE_FORMAT(fechaFinCurso, '%d/%m/%Y') AS fFin
            FROM cursos
            WHERE idCurso = :idCurso
            LIMIT 1
        ");
        $stmt->bindValue(':idCurso', (int) $idCurso, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    static public function mdlListarCursos()
    {
        $stmt = Conexion::conectar()->prepare("
            SELECT *,
                   DATE_FORMAT(fechaInicioCurso, '%d/%m/%Y') AS fInicio,
                   DATE_FORMAT(fechaFinCurso, '%d/%m/%Y') AS fFin
            FROM cursos
            ORDER BY nombreCurso ASC
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
            GROUP BY c.idCurso, c.nombreCurso, c.contenidoCurso, c.estado, c.fechaInicioCurso, c.fechaFinCurso, c.horarioCurso
            ORDER BY c.nombreCurso ASC
        ");
        $stmt->bindValue(':idEstudiante', (int) $idEstudiante, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    /* GUARDAR CURSO */
    static public function mdlGuardarCurso($tabla, $datos)
    {

        $registro = Conexion::conectar()->prepare("INSERT INTO $tabla(nombreCurso, contenidoCurso, estado, fechaInicioCurso, fechaFinCurso, horarioCurso) VALUES(:nombreCurso, :contenidoCurso, :estado, :fechaInicioCurso, :fechaFinCurso, :horarioCurso)");

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

    /* ASIGNAR CURSO */
    static public function mdlAsignarCurso($tabla, $datos)
    {
        $registro = Conexion::conectar()->prepare("INSERT INTO $tabla (id_estudiante,id_seccion) VALUES(:idUsuario, :idCurso)");

        $registro->bindParam(":idCurso", $datos["idCurso"], PDO::PARAM_INT);
        $registro->bindParam(":idUsuario", $datos["idUsuario"], PDO::PARAM_INT);

     
        if ($registro->execute()) {
            return "ok";
        } else {
            print_r(Conexion::conectar()->errorInfo());
        }

        $registro->closeCursor();
        $registro = null;
        
    }
}
