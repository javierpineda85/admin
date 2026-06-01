<?php

require_once('conexion.php');

class ModeloUsuarios
{
    public function authenticate($email, $password)
    {
        $storedPassword = $this->getStoredPasswordByUsername($email);

        return $storedPassword !== null && password_verify($password, $storedPassword);
    }

    private function getStoredPasswordByUsername($username)
    {
        $users = [
            'john' => '$2y$10$jWQxRc0kLlNhvX52nVpPve.hGzsOR5M10KgIrNzJwXvT4aQxir9jC',
        ];

        return $users[$username] ?? null;
    }

    public static function mdlObtenerUsuarioPorEmail($email)
    {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM usuarios WHERE email = :email LIMIT 1");
        $stmt->bindParam(":email", $email, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function mdlObtenerUsuarioPorId($idUsuario)
    {
        $stmt = Conexion::conectar()->prepare("SELECT * FROM usuarios WHERE idUsuario = :idUsuario LIMIT 1");
        $stmt->bindParam(":idUsuario", $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function mdlObtenerUsuarioCompleto($idUsuario)
    {
        $stmt = Conexion::conectar()->prepare("
            SELECT u.*,
                   p.idPerfil,
                   p.dniPerfil,
                   p.telefonoPerfil,
                   p.fnacPerfil,
                   p.domicilioPerfil,
                   p.provinciaPerfil,
                   p.contenidoPerfil,
                   DATE_FORMAT(u.fechaAlta, '%d/%m/%Y %H:%i') AS fechaAltaFmt,
                   DATE_FORMAT(u.fechaBaja, '%d/%m/%Y %H:%i') AS fechaBajaFmt,
                   CONCAT(u2.nombreUsuario, ' ', u2.apellidoUsuario) AS usuarioBajaNombre
            FROM usuarios u
            LEFT JOIN perfiles p ON p.id_usuario = u.idUsuario
            LEFT JOIN usuarios u2 ON u2.idUsuario = u.usuarioBaja
            WHERE u.idUsuario = :idUsuario
            LIMIT 1
        ");
        $stmt->bindParam(":idUsuario", $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function mdlRelacionesAcademicas($idUsuario)
    {
        $stmt = Conexion::conectar()->prepare("
            SELECT DISTINCT idCurso, nombreCurso, idSeccion, tituloSeccion, origen
            FROM (
                SELECT c.idCurso,
                       c.nombreCurso,
                       s.idSeccion,
                       s.tituloSeccion,
                       'ESTUDIANTE' AS origen
                FROM asignacioncursos a
                INNER JOIN secciones s ON s.idSeccion = a.id_seccion
                INNER JOIN cursos c ON c.idCurso = s.id_curso
                WHERE a.id_estudiante = :idEstudiante

                UNION

                SELECT c.idCurso,
                       c.nombreCurso,
                       s.idSeccion,
                       s.tituloSeccion,
                       'DOCENTE' AS origen
                FROM secciones s
                INNER JOIN cursos c ON c.idCurso = s.id_curso
                WHERE s.docente = :idDocente
            ) AS relaciones
            ORDER BY nombreCurso ASC, tituloSeccion ASC
        ");
        $stmt->bindParam(":idEstudiante", $idUsuario, PDO::PARAM_INT);
        $stmt->bindParam(":idDocente", $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlSeleccionarUsuarios($item, $valor)
    {
        if ($item === null || $valor === null) {
            $stmt = Conexion::conectar()->prepare("
                SELECT u.*,
                       DATE_FORMAT(u.fechaAlta, '%d/%m/%Y %H:%i') AS fechaAltaFmt,
                       DATE_FORMAT(u.fechaBaja, '%d/%m/%Y %H:%i') AS fechaBajaFmt,
                       CONCAT(u2.nombreUsuario, ' ', u2.apellidoUsuario) AS usuarioBajaNombre
                FROM usuarios u
                LEFT JOIN usuarios u2 ON u2.idUsuario = u.usuarioBaja
                ORDER BY u.apellidoUsuario ASC, u.nombreUsuario ASC
            ");
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $columnasPermitidas = ['idUsuario', 'nombreUsuario', 'apellidoUsuario', 'email', 'rol', 'activo', 'resetPass'];
        if (!in_array($item, $columnasPermitidas, true)) {
            return [];
        }

        $stmt = Conexion::conectar()->prepare("SELECT * FROM usuarios WHERE $item = :valor ORDER BY apellidoUsuario ASC, nombreUsuario ASC");
        $stmt->bindParam(":valor", $valor, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlDestinatariosPermitidos($idUsuarioActual, $rolActual)
    {
        $rolActual = strtoupper(trim((string) $rolActual));

        if (in_array($rolActual, ['ADMINISTRADOR', 'DOCENTE'], true)) {
            $stmt = Conexion::conectar()->prepare("
                SELECT idUsuario, nombreUsuario, apellidoUsuario, email, rol
                FROM usuarios
                WHERE activo = 1
                  AND idUsuario <> :idUsuarioActual
                ORDER BY rol ASC, apellidoUsuario ASC, nombreUsuario ASC
            ");
            $stmt->bindParam(":idUsuarioActual", $idUsuarioActual, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $stmt = Conexion::conectar()->prepare("
            SELECT DISTINCT u.idUsuario, u.nombreUsuario, u.apellidoUsuario, u.email, u.rol
            FROM usuarios u
            INNER JOIN asignacioncursos a1 ON a1.id_estudiante = :idUsuarioActual
            INNER JOIN asignacioncursos a2 ON a2.id_seccion = a1.id_seccion
            WHERE u.idUsuario = a2.id_estudiante
              AND u.activo = 1
              AND u.rol = 'ESTUDIANTE'
              AND u.idUsuario <> :idUsuarioActual
            ORDER BY u.apellidoUsuario ASC, u.nombreUsuario ASC
        ");
        $stmt->bindParam(":idUsuarioActual", $idUsuarioActual, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlCompartenCurso($idUsuario1, $idUsuario2)
    {
        $stmt = Conexion::conectar()->prepare("
            SELECT COUNT(*) AS total
            FROM asignacioncursos a1
            INNER JOIN asignacioncursos a2 ON a1.id_seccion = a2.id_seccion
            WHERE a1.id_estudiante = :idUsuario1
              AND a2.id_estudiante = :idUsuario2
        ");
        $stmt->bindParam(":idUsuario1", $idUsuario1, PDO::PARAM_INT);
        $stmt->bindParam(":idUsuario2", $idUsuario2, PDO::PARAM_INT);
        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return !empty($resultado) && (int) $resultado['total'] > 0;
    }

    public static function mdlActualizarPassword($idUsuario, $passwordHash)
    {
        $stmt = Conexion::conectar()->prepare("UPDATE usuarios SET pass = :pass, resetPass = 0 WHERE idUsuario = :idUsuario");
        $stmt->bindParam(":pass", $passwordHash, PDO::PARAM_STR);
        $stmt->bindParam(":idUsuario", $idUsuario, PDO::PARAM_INT);

        return $stmt->execute() ? "ok" : "error";
    }

    public static function mdlGuardarUsuario($tabla, $datos)
    {
        $registro = Conexion::conectar()->prepare("
            INSERT INTO $tabla
                (nombreUsuario, apellidoUsuario, email, pass, resetPass, imgUsuario, activo, rol, fechaAlta)
            VALUES
                (:nombreUsuario, :apellidoUsuario, :email, :pass, :resetPass, :imgUsuario, :activo, :rol, :fechaAlta)
        ");

        $registro->bindParam(":nombreUsuario", $datos["nombreUsuario"], PDO::PARAM_STR);
        $registro->bindParam(":apellidoUsuario", $datos["apellidoUsuario"], PDO::PARAM_STR);
        $registro->bindParam(":email", $datos["email"], PDO::PARAM_STR);
        $registro->bindParam(":pass", $datos["pass"], PDO::PARAM_STR);
        $registro->bindParam(":resetPass", $datos["resetPass"], PDO::PARAM_INT);
        $registro->bindParam(":imgUsuario", $datos["imgUsuario"], PDO::PARAM_STR);
        $registro->bindParam(":activo", $datos["activo"], PDO::PARAM_INT);
        $registro->bindParam(":rol", $datos["rol"], PDO::PARAM_STR);
        $registro->bindParam(":fechaAlta", $datos["fechaAlta"], PDO::PARAM_STR);

        return $registro->execute() ? "ok" : "error";
    }

    public static function mdlModificarUsuario($tabla, $datos)
    {
        $consulta = "UPDATE $tabla SET nombreUsuario = :nombreUsuario, apellidoUsuario = :apellidoUsuario, email = :email, rol = :rol";
        $valores = [
            ":nombreUsuario" => $datos["nombreUsuario"],
            ":apellidoUsuario" => $datos["apellidoUsuario"],
            ":email" => $datos["emailUsuario"],
            ":rol" => $datos["rol"],
        ];

        if (isset($datos["passUsuario"]) && $datos["passUsuario"] !== '') {
            $consulta .= ", pass = :passUsuario_hashed, resetPass = 1";
            $valores[":passUsuario_hashed"] = password_hash($datos["passUsuario"], PASSWORD_DEFAULT);
        }

        $consulta .= " WHERE idUsuario = :idUsuario";
        $registro = Conexion::conectar()->prepare($consulta);

        foreach ($valores as $clave => $valor) {
            $registro->bindValue($clave, $valor, PDO::PARAM_STR);
        }

        $registro->bindValue(":idUsuario", (int) $datos["idUsuario"], PDO::PARAM_INT);

        return $registro->execute() ? "ok" : "error";
    }

    public static function mdlDarBajaUsuario($datos)
    {
        $stmt = Conexion::conectar()->prepare("
            UPDATE usuarios
            SET activo = 0,
                fechaBaja = :fechaBaja,
                motivoBaja = :motivoBaja,
                usuarioBaja = :usuarioBaja
            WHERE idUsuario = :idUsuario
        ");

        $stmt->bindValue(':fechaBaja', $datos['fechaBaja'], PDO::PARAM_STR);
        $stmt->bindValue(':motivoBaja', $datos['motivoBaja'], PDO::PARAM_STR);
        $stmt->bindValue(':usuarioBaja', (int) $datos['usuarioBaja'], PDO::PARAM_INT);
        $stmt->bindValue(':idUsuario', (int) $datos['idUsuario'], PDO::PARAM_INT);

        return $stmt->execute() ? 'ok' : 'error';
    }

    public static function mdlReactivarUsuario($idUsuario)
    {
        $stmt = Conexion::conectar()->prepare("
            UPDATE usuarios
            SET activo = 1,
                fechaBaja = NULL,
                motivoBaja = NULL,
                usuarioBaja = NULL
            WHERE idUsuario = :idUsuario
        ");
        $stmt->bindValue(':idUsuario', (int) $idUsuario, PDO::PARAM_INT);

        return $stmt->execute() ? 'ok' : 'error';
    }
}
