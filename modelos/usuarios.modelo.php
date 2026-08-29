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

    private static function sanitizarPrefijoWordPress()
    {
        $prefijo = preg_replace('/[^A-Za-z0-9_]/', '', (string) WP_TABLE_PREFIX);
        return $prefijo !== '' ? $prefijo : 'wp_';
    }

    private static function truncarTexto($texto, $limite)
    {
        $texto = trim((string) $texto);
        if ($texto === '') {
            return '';
        }

        if (function_exists('mb_substr')) {
            return mb_substr($texto, 0, $limite);
        }

        return substr($texto, 0, $limite);
    }

    private static function separarNombreApellido($displayName, $fallbackLogin)
    {
        $displayName = trim((string) $displayName);
        if ($displayName === '') {
            $displayName = trim((string) $fallbackLogin);
        }

        $partes = preg_split('/\s+/', $displayName) ?: [];
        $nombre = $partes[0] ?? 'Usuario';
        unset($partes[0]);
        $apellido = trim(implode(' ', $partes));

        if ($apellido === '') {
            $apellido = 'Campus';
        }

        return [$nombre, $apellido];
    }

    private static function resolverRolWordPress($usuarioWp)
    {
        if ((int) ($usuarioWp['user_status'] ?? 0) !== 0) {
            return null;
        }

        $email = strtolower(trim((string) ($usuarioWp['user_email'] ?? '')));
        $superAdmins = array_map('strtolower', WP_SUPER_ADMIN_EMAILS);
        if ($email !== '' && in_array($email, $superAdmins, true)) {
            return 'ADMINISTRADOR';
        }

        $capabilities = strtolower((string) ($usuarioWp['capabilities'] ?? ''));
        $esAdmin = str_contains($capabilities, 'administrator');
        $esInstructor = !empty($usuarioWp['is_tutor_instructor'])
            && strtolower((string) ($usuarioWp['tutor_instructor_status'] ?? '')) === 'approved';
        $esEstudiante = !empty($usuarioWp['is_tutor_student'])
            || str_contains($capabilities, 'customer')
            || str_contains($capabilities, 'subscriber');

        if ($esAdmin) {
            return 'ADMINISTRADOR';
        }

        if ($esInstructor) {
            return 'DOCENTE';
        }

        if ($esEstudiante) {
            return 'ESTUDIANTE';
        }

        return null;
    }

    public static function mdlAsegurarColumnasIntegracionWordPress()
    {
        static $columnasVerificadas = false;
        if ($columnasVerificadas) {
            return;
        }

        $conexion = Conexion::conectar();
        if (!$conexion) {
            return;
        }

        $stmtWp = $conexion->query("SHOW COLUMNS FROM usuarios LIKE 'wpUserId'");
        if (!$stmtWp->fetch(PDO::FETCH_ASSOC)) {
            $conexion->exec("ALTER TABLE usuarios ADD COLUMN wpUserId BIGINT NULL DEFAULT NULL AFTER usuarioBaja");
        }

        $stmtOrigen = $conexion->query("SHOW COLUMNS FROM usuarios LIKE 'origenAuth'");
        if (!$stmtOrigen->fetch(PDO::FETCH_ASSOC)) {
            $conexion->exec("ALTER TABLE usuarios ADD COLUMN origenAuth VARCHAR(20) NOT NULL DEFAULT 'LOCAL' AFTER wpUserId");
        }

        $stmtIndice = $conexion->query("SHOW INDEX FROM usuarios WHERE Key_name = 'idx_wp_user'");
        if (!$stmtIndice->fetch(PDO::FETCH_ASSOC)) {
            $conexion->exec("ALTER TABLE usuarios ADD INDEX idx_wp_user (wpUserId)");
        }

        $columnasVerificadas = true;
    }

    public static function mdlObtenerUsuarioWordPressPorEmail($email)
    {
        $conexionWp = Conexion::conectarWordPress();
        if (!$conexionWp) {
            return null;
        }

        $prefijo = self::sanitizarPrefijoWordPress();
        $tablaUsuarios = $prefijo . 'users';
        $tablaMeta = $prefijo . 'usermeta';
        $metaCapabilities = $prefijo . 'capabilities';

        $stmt = $conexionWp->prepare("
            SELECT u.ID,
                   u.user_login,
                   u.user_pass,
                   u.user_email,
                   u.user_status,
                   u.display_name,
                   MAX(CASE WHEN um.meta_key = 'first_name' THEN um.meta_value END) AS first_name,
                   MAX(CASE WHEN um.meta_key = 'last_name' THEN um.meta_value END) AS last_name,
                   MAX(CASE WHEN um.meta_key = :metaCapabilities THEN um.meta_value END) AS capabilities,
                   MAX(CASE WHEN um.meta_key = '_is_tutor_instructor' THEN um.meta_value END) AS is_tutor_instructor,
                   MAX(CASE WHEN um.meta_key = '_tutor_instructor_status' THEN um.meta_value END) AS tutor_instructor_status,
                   MAX(CASE WHEN um.meta_key = '_is_tutor_student' THEN um.meta_value END) AS is_tutor_student
            FROM {$tablaUsuarios} u
            LEFT JOIN {$tablaMeta} um ON um.user_id = u.ID
            WHERE u.user_email = :email OR u.user_login = :email
            GROUP BY u.ID, u.user_login, u.user_pass, u.user_email, u.user_status, u.display_name
            LIMIT 1
        ");
        $stmt->bindValue(':metaCapabilities', $metaCapabilities, PDO::PARAM_STR);
        $stmt->bindValue(':email', (string) $email, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private static function asegurarPerfilBasico($idUsuario)
    {
        $stmt = Conexion::conectar()->prepare("SELECT idPerfil FROM perfiles WHERE id_usuario = :idUsuario LIMIT 1");
        $stmt->bindValue(':idUsuario', (int) $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            return;
        }

        $insert = Conexion::conectar()->prepare("
            INSERT INTO perfiles (id_usuario, dniPerfil, telefonoPerfil, fnacPerfil, domicilioPerfil, provinciaPerfil, contenidoPerfil)
            VALUES (:idUsuario, NULL, NULL, NULL, NULL, NULL, '')
        ");
        $insert->bindValue(':idUsuario', (int) $idUsuario, PDO::PARAM_INT);
        $insert->execute();
    }

    public static function mdlSincronizarUsuarioWordPress($usuarioWp)
    {
        if (empty($usuarioWp) || empty($usuarioWp['ID'])) {
            return null;
        }

        self::mdlAsegurarColumnasIntegracionWordPress();

        $rol = self::resolverRolWordPress($usuarioWp);
        if ($rol === null) {
            return null;
        }

        $conexion = Conexion::conectar();
        $wpUserId = (int) $usuarioWp['ID'];

        $nombre = trim((string) ($usuarioWp['first_name'] ?? ''));
        $apellido = trim((string) ($usuarioWp['last_name'] ?? ''));
        if ($nombre === '' || $apellido === '') {
            [$nombreFallback, $apellidoFallback] = self::separarNombreApellido(
                $usuarioWp['display_name'] ?? '',
                $usuarioWp['user_login'] ?? ''
            );
            if ($nombre === '') {
                $nombre = $nombreFallback;
            }
            if ($apellido === '') {
                $apellido = $apellidoFallback;
            }
        }

        $nombre = self::truncarTexto($nombre, 20);
        $apellido = self::truncarTexto($apellido, 20);
        $email = self::truncarTexto((string) ($usuarioWp['user_email'] ?? ''), 50);

        $stmt = $conexion->prepare("
            SELECT *
            FROM usuarios
            WHERE wpUserId = :wpUserId OR email = :email
            ORDER BY wpUserId = :wpUserIdOrder DESC, idUsuario ASC
            LIMIT 1
        ");
        $stmt->bindValue(':wpUserId', $wpUserId, PDO::PARAM_INT);
        $stmt->bindValue(':wpUserIdOrder', $wpUserId, PDO::PARAM_INT);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $usuarioLocal = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuarioLocal) {
            $update = $conexion->prepare("
                UPDATE usuarios
                SET nombreUsuario = :nombre,
                    apellidoUsuario = :apellido,
                    email = :email,
                    rol = :rol,
                    wpUserId = :wpUserId,
                    origenAuth = 'WORDPRESS'
                WHERE idUsuario = :idUsuario
            ");
            $update->bindValue(':nombre', $nombre, PDO::PARAM_STR);
            $update->bindValue(':apellido', $apellido, PDO::PARAM_STR);
            $update->bindValue(':email', $email, PDO::PARAM_STR);
            $update->bindValue(':rol', $rol, PDO::PARAM_STR);
            $update->bindValue(':wpUserId', $wpUserId, PDO::PARAM_INT);
            $update->bindValue(':idUsuario', (int) $usuarioLocal['idUsuario'], PDO::PARAM_INT);
            $update->execute();

            self::asegurarPerfilBasico((int) $usuarioLocal['idUsuario']);
            return self::mdlObtenerUsuarioPorId((int) $usuarioLocal['idUsuario']);
        }

        $passwordPlaceholder = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        $insert = $conexion->prepare("
            INSERT INTO usuarios
                (nombreUsuario, apellidoUsuario, email, pass, resetPass, imgUsuario, activo, rol, fechaAlta, wpUserId, origenAuth)
            VALUES
                (:nombre, :apellido, :email, :pass, 0, '', 1, :rol, NOW(), :wpUserId, 'WORDPRESS')
        ");
        $insert->bindValue(':nombre', $nombre, PDO::PARAM_STR);
        $insert->bindValue(':apellido', $apellido, PDO::PARAM_STR);
        $insert->bindValue(':email', $email, PDO::PARAM_STR);
        $insert->bindValue(':pass', $passwordPlaceholder, PDO::PARAM_STR);
        $insert->bindValue(':rol', $rol, PDO::PARAM_STR);
        $insert->bindValue(':wpUserId', $wpUserId, PDO::PARAM_INT);
        $insert->execute();

        $idNuevoUsuario = (int) $conexion->lastInsertId();
        self::asegurarPerfilBasico($idNuevoUsuario);

        return self::mdlObtenerUsuarioPorId($idNuevoUsuario);
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
                   DATE_FORMAT(p.fnacPerfil, '%d/%m/%Y') AS fnacFormateada,
                   DATE_FORMAT(u.fechaAlta, '%d/%m/%Y %H:%i') AS fechaAltaFmt,
                   DATE_FORMAT(u.ultimaConexion, '%d/%m/%Y %H:%i') AS ultimaConexionFmt,
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
                INNER JOIN cursos c ON c.idCurso = a.id_seccion
                LEFT JOIN secciones s ON s.id_curso = c.idCurso
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
                   OR s.tutor = :idDocente
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
                       DATE_FORMAT(u.ultimaConexion, '%d/%m/%Y %H:%i') AS ultimaConexionFmt,
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

        $stmt = Conexion::conectar()->prepare("
            SELECT u.*,
                   DATE_FORMAT(u.fechaAlta, '%d/%m/%Y %H:%i') AS fechaAltaFmt,
                   DATE_FORMAT(u.ultimaConexion, '%d/%m/%Y %H:%i') AS ultimaConexionFmt,
                   DATE_FORMAT(u.fechaBaja, '%d/%m/%Y %H:%i') AS fechaBajaFmt,
                   CONCAT(u2.nombreUsuario, ' ', u2.apellidoUsuario) AS usuarioBajaNombre
            FROM usuarios u
            LEFT JOIN usuarios u2 ON u2.idUsuario = u.usuarioBaja
            WHERE u.$item = :valor
            ORDER BY u.apellidoUsuario ASC, u.nombreUsuario ASC
        ");
        $stmt->bindParam(":valor", $valor, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlUsuariosDocentesAsignables()
    {
        $stmt = Conexion::conectar()->prepare("
            SELECT u.*,
                   DATE_FORMAT(u.fechaAlta, '%d/%m/%Y %H:%i') AS fechaAltaFmt,
                   DATE_FORMAT(u.ultimaConexion, '%d/%m/%Y %H:%i') AS ultimaConexionFmt,
                   DATE_FORMAT(u.fechaBaja, '%d/%m/%Y %H:%i') AS fechaBajaFmt,
                   CONCAT(u2.nombreUsuario, ' ', u2.apellidoUsuario) AS usuarioBajaNombre
            FROM usuarios u
            LEFT JOIN usuarios u2 ON u2.idUsuario = u.usuarioBaja
            WHERE u.activo = 1
              AND u.rol IN ('DOCENTE', 'ADMINISTRADOR')
            ORDER BY FIELD(u.rol, 'DOCENTE', 'ADMINISTRADOR'), u.apellidoUsuario ASC, u.nombreUsuario ASC
        ");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlUsuariosConectadosRecientes($minutos = 60)
    {
        $minutos = max(1, (int) $minutos);
        $stmt = Conexion::conectar()->prepare("
            SELECT u.*,
                   DATE_FORMAT(u.fechaAlta, '%d/%m/%Y %H:%i') AS fechaAltaFmt,
                   DATE_FORMAT(u.ultimaConexion, '%d/%m/%Y %H:%i') AS ultimaConexionFmt,
                   DATE_FORMAT(u.fechaBaja, '%d/%m/%Y %H:%i') AS fechaBajaFmt,
                   CONCAT(u2.nombreUsuario, ' ', u2.apellidoUsuario) AS usuarioBajaNombre
            FROM usuarios u
            LEFT JOIN usuarios u2 ON u2.idUsuario = u.usuarioBaja
            WHERE u.activo = 1
              AND u.ultimaConexion >= (NOW() - INTERVAL {$minutos} MINUTE)
            ORDER BY u.ultimaConexion DESC, u.apellidoUsuario ASC, u.nombreUsuario ASC
        ");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlUsuariosNoConectadosRecientes($minutos = 60)
    {
        $minutos = max(1, (int) $minutos);
        $stmt = Conexion::conectar()->prepare("
            SELECT u.*,
                   DATE_FORMAT(u.fechaAlta, '%d/%m/%Y %H:%i') AS fechaAltaFmt,
                   DATE_FORMAT(u.ultimaConexion, '%d/%m/%Y %H:%i') AS ultimaConexionFmt,
                   DATE_FORMAT(u.fechaBaja, '%d/%m/%Y %H:%i') AS fechaBajaFmt,
                   CONCAT(u2.nombreUsuario, ' ', u2.apellidoUsuario) AS usuarioBajaNombre
            FROM usuarios u
            LEFT JOIN usuarios u2 ON u2.idUsuario = u.usuarioBaja
            WHERE u.activo = 1
              AND (u.ultimaConexion IS NULL OR u.ultimaConexion < (NOW() - INTERVAL {$minutos} MINUTE))
            ORDER BY u.ultimaConexion IS NULL DESC, u.ultimaConexion ASC, u.apellidoUsuario ASC, u.nombreUsuario ASC
        ");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlContarUsuariosConectadosRecientes($minutos = 60)
    {
        $minutos = max(1, (int) $minutos);
        $stmt = Conexion::conectar()->prepare("
            SELECT COUNT(*) AS total
            FROM usuarios
            WHERE activo = 1
              AND ultimaConexion >= (NOW() - INTERVAL {$minutos} MINUTE)
        ");
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) ($resultado['total'] ?? 0);
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

            UNION

            SELECT DISTINCT u.idUsuario, u.nombreUsuario, u.apellidoUsuario, u.email, u.rol
            FROM asignacioncursos a
            INNER JOIN secciones s ON s.id_curso = a.id_seccion
            INNER JOIN usuarios u ON u.idUsuario IN (s.docente, s.tutor)
            WHERE a.id_estudiante = :idUsuarioActual
              AND u.activo = 1
              AND u.rol IN ('DOCENTE', 'ADMINISTRADOR')
              AND u.idUsuario <> :idUsuarioActual

            ORDER BY rol ASC, apellidoUsuario ASC, nombreUsuario ASC
        ");
        $stmt->bindParam(":idUsuarioActual", $idUsuarioActual, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlDocenteTutorDeCursosEstudiante($idEstudiante, $idDocente)
    {
        $stmt = Conexion::conectar()->prepare("
            SELECT COUNT(*) AS total
            FROM asignacioncursos a
            INNER JOIN secciones s ON s.id_curso = a.id_seccion
            WHERE a.id_estudiante = :idEstudiante
              AND (s.docente = :idDocente OR s.tutor = :idDocente)
        ");
        $stmt->bindParam(":idEstudiante", $idEstudiante, PDO::PARAM_INT);
        $stmt->bindParam(":idDocente", $idDocente, PDO::PARAM_INT);
        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return !empty($resultado) && (int) $resultado['total'] > 0;
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

    public static function mdlActualizarUltimaConexion($idUsuario)
    {
        $stmt = Conexion::conectar()->prepare("UPDATE usuarios SET ultimaConexion = NOW() WHERE idUsuario = :idUsuario");
        $stmt->bindValue(':idUsuario', (int) $idUsuario, PDO::PARAM_INT);

        return $stmt->execute() ? 'ok' : 'error';
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

        if (isset($datos["imgUsuario"]) && $datos["imgUsuario"] !== '') {
            $consulta .= ", imgUsuario = :imgUsuario";
            $valores[":imgUsuario"] = $datos["imgUsuario"];
        }

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

    public static function mdlActualizarImagenUsuario($idUsuario, $imgUsuario)
    {
        $stmt = Conexion::conectar()->prepare("UPDATE usuarios SET imgUsuario = :imgUsuario WHERE idUsuario = :idUsuario");
        $stmt->bindValue(':imgUsuario', (string) $imgUsuario, PDO::PARAM_STR);
        $stmt->bindValue(':idUsuario', (int) $idUsuario, PDO::PARAM_INT);

        return $stmt->execute() ? 'ok' : 'error';
    }

    public static function mdlRegistrarHistorial($datos)
    {
        $stmt = Conexion::conectar()->prepare("
            INSERT INTO usuarios_historial
                (id_usuario, accion, detalle, id_usuario_accion, fechaEvento)
            VALUES
                (:id_usuario, :accion, :detalle, :id_usuario_accion, :fechaEvento)
        ");
        $stmt->bindValue(':id_usuario', (int) $datos['id_usuario'], PDO::PARAM_INT);
        $stmt->bindValue(':accion', (string) $datos['accion'], PDO::PARAM_STR);
        $stmt->bindValue(':detalle', (string) $datos['detalle'], PDO::PARAM_STR);
        $stmt->bindValue(':id_usuario_accion', (int) $datos['id_usuario_accion'], PDO::PARAM_INT);
        $stmt->bindValue(':fechaEvento', (string) $datos['fechaEvento'], PDO::PARAM_STR);

        return $stmt->execute() ? 'ok' : 'error';
    }

    public static function mdlHistorialUsuario($idUsuario)
    {
        $stmt = Conexion::conectar()->prepare("
            SELECT h.*,
                   CONCAT(u.nombreUsuario, ' ', u.apellidoUsuario) AS usuarioAccionNombre,
                   DATE_FORMAT(h.fechaEvento, '%d/%m/%Y %H:%i') AS fechaEventoFmt
            FROM usuarios_historial h
            LEFT JOIN usuarios u ON u.idUsuario = h.id_usuario_accion
            WHERE h.id_usuario = :idUsuario
            ORDER BY h.fechaEvento DESC, h.idHistorial DESC
            LIMIT 20
        ");
        $stmt->bindValue(':idUsuario', (int) $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
