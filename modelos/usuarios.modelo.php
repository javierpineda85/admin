<?php

require_once('conexion.php');
require_once __DIR__ . '/tenant.modelo.php';

class ModeloUsuarios
{
    private static function normalizarRolesInstitucionales(array $roles)
    {
        $permitidos = ['ADMINISTRADOR', 'DOCENTE', 'ESTUDIANTE'];
        $normalizados = [];
        foreach ($roles as $rol) {
            $rol = strtoupper(trim((string) $rol));
            if (in_array($rol, $permitidos, true)) { $normalizados[$rol] = true; }
        }
        return array_keys($normalizados);
    }

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
        if (defined('INSTITUCIONES_CONTEXTO_ACTIVO') && INSTITUCIONES_CONTEXTO_ACTIVO === true) {
            $stmt = Conexion::conectar()->prepare('SELECT * FROM usuarios WHERE LOWER(TRIM(email)) = LOWER(TRIM(?)) LIMIT 2');
            $stmt->execute([(string) $email]);
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return count($usuarios) === 1 ? $usuarios[0] : false;
        }
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

        if (defined('INSTITUCIONES_CONTEXTO_ACTIVO') && INSTITUCIONES_CONTEXTO_ACTIVO === true) {
            return self::sincronizarIdentidadWordPress($usuarioWp);
        }

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
            // WordPress autentica la identidad, pero el rol academico se administra
            // desde Campus una vez que la cuenta local ya fue creada.
            $rolLocal = strtoupper(trim((string) ($usuarioLocal['rol'] ?? '')));
            $emailWp = strtolower(trim((string) ($usuarioWp['user_email'] ?? '')));
            $esSuperAdmin = $emailWp !== '' && in_array($emailWp, array_map('strtolower', WP_SUPER_ADMIN_EMAILS), true);
            if (!$esSuperAdmin && in_array($rolLocal, ['ADMINISTRADOR', 'DOCENTE', 'ESTUDIANTE'], true)) {
                $rol = $rolLocal;
            }

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

    private static function sincronizarIdentidadWordPress(array $usuarioWp)
    {
        $email = strtolower(trim((string) ($usuarioWp['user_email'] ?? '')));
        // No truncar emails: dos identidades diferentes podrían terminar iguales.
        if ((int) ($usuarioWp['ID'] ?? 0) <= 0 || (int) ($usuarioWp['user_status'] ?? 0) !== 0 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 50) {
            return null;
        }
        $pdo = Conexion::conectar();
        // Usuarios legacy puede ser MyISAM: serializar el alta/sincronización de
        // identidades hasta que la fase de esquema definitivo imponga unicidad.
        $candado = 'campus_identidad_' . substr(hash('sha256', DB_NAME), 0, 40);
        $lock = $pdo->prepare('SELECT GET_LOCK(?, 5)');
        $lock->execute([$candado]);
        if ((int) $lock->fetchColumn() !== 1) { return null; }
        try {
            $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE wpUserId = ? OR LOWER(TRIM(email)) = ? LIMIT 2');
            $stmt->execute([(int) $usuarioWp['ID'], $email]);
            $coincidencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($coincidencias) > 1) { return null; }
            $existente = $coincidencias[0] ?? null;
            if ($existente && ((int) $existente['activo'] !== 1
                || (!empty($existente['wpUserId']) && (int) $existente['wpUserId'] !== (int) $usuarioWp['ID']))) {
                return null;
            }
            [$nombreBase, $apellidoBase] = self::separarNombreApellido($usuarioWp['display_name'] ?? '', $usuarioWp['user_login'] ?? '');
            $nombre = self::truncarTexto(trim((string) ($usuarioWp['first_name'] ?? '')) ?: $nombreBase, 20);
            $apellido = self::truncarTexto(trim((string) ($usuarioWp['last_name'] ?? '')) ?: $apellidoBase, 20);
            if ($existente) {
                $id = (int) $existente['idUsuario'];
                // Roles, membresías, estado global, foto y privilegios no vienen de WP.
                $pdo->prepare("UPDATE usuarios SET nombreUsuario=?, apellidoUsuario=?, email=?, wpUserId=?, origenAuth='WORDPRESS' WHERE idUsuario=?")
                    ->execute([$nombre, $apellido, $email, (int) $usuarioWp['ID'], $id]);
            } else {
                $pdo->prepare("INSERT INTO usuarios(nombreUsuario,apellidoUsuario,email,pass,resetPass,imgUsuario,activo,rol,fechaAlta,wpUserId,origenAuth) VALUES (?,?,?, ?,0,'',1,'',NOW(),?,'WORDPRESS')")
                    ->execute([$nombre, $apellido, $email, password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT), (int) $usuarioWp['ID']]);
                $id = (int) $pdo->lastInsertId();
            }
            self::asegurarPerfilBasico($id);
            return self::mdlObtenerUsuarioPorId($id);
        } finally {
            $pdo->prepare('SELECT RELEASE_LOCK(?)')->execute([$candado]);
        }
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
        if (ModeloTenant::activo()) {
            $stmt = Conexion::conectar()->prepare("SELECT u.idUsuario,u.nombreUsuario,u.apellidoUsuario,u.email,u.imgUsuario,
                    u.ultimaConexion,u.origenAuth,u.activo activoGlobal,
                    ui.idUsuarioInstitucion,ui.activo,ui.fechaAlta,ui.fechaBaja,ui.motivoBaja,
                    p.idPerfil,p.dniPerfil,p.telefonoPerfil,p.fnacPerfil,p.domicilioPerfil,p.provinciaPerfil,p.contenidoPerfil,
                    DATE_FORMAT(p.fnacPerfil, '%d/%m/%Y') fnacFormateada,
                    DATE_FORMAT(ui.fechaAlta, '%d/%m/%Y %H:%i') fechaAltaFmt,
                    DATE_FORMAT(u.ultimaConexion, '%d/%m/%Y %H:%i') ultimaConexionFmt,
                    DATE_FORMAT(ui.fechaBaja, '%d/%m/%Y %H:%i') fechaBajaFmt,
                    GROUP_CONCAT(DISTINCT r.codigo ORDER BY r.codigo SEPARATOR ' · ') rol
                FROM usuarios_instituciones ui
                INNER JOIN usuarios u ON u.idUsuario=ui.id_usuario
                LEFT JOIN perfiles p ON p.id_usuario=u.idUsuario
                LEFT JOIN usuarios_instituciones_roles ur ON ur.id_usuario_institucion=ui.idUsuarioInstitucion
                LEFT JOIN roles r ON r.idRol=ur.id_rol AND r.codigo IN ('ADMINISTRADOR','DOCENTE','ESTUDIANTE')
                WHERE ui.id_institucion=:idInstitucion AND u.idUsuario=:idUsuario AND u.activo=1
                    AND " . ModeloTenant::sesionActiva() . "
                GROUP BY ui.idUsuarioInstitucion,u.idUsuario,p.idPerfil
                LIMIT 1");
            $stmt->execute([':idInstitucion'=>ModeloTenant::id(),':idUsuario'=>(int)$idUsuario]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
        }
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
        $filtroCurso = ModeloTenant::activo() ? ' AND ' . ModeloTenant::cursos('c') : '';
        $filtroSeccion = ModeloTenant::activo() ? ' AND ' . ModeloTenant::secciones('s') : '';
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
                WHERE a.id_estudiante = :idEstudiante AND a.estadoInscripcion='ACTIVA'
                    {$filtroCurso}

                UNION

                SELECT c.idCurso,
                       c.nombreCurso,
                       s.idSeccion,
                       s.tituloSeccion,
                       'DOCENTE' AS origen
                FROM secciones s
                INNER JOIN cursos c ON c.idCurso = s.id_curso
                WHERE (s.docente = :idDocente
                   OR s.tutor = :idDocente)
                   {$filtroSeccion}
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
        if (defined('INSTITUCIONES_CONTEXTO_ACTIVO') && INSTITUCIONES_CONTEXTO_ACTIVO === true) {
            require_once __DIR__ . '/tenant.modelo.php';
            $institucion = ModeloTenant::id();
            $filtro = '';
            $parametros = [$institucion];
            if ($item !== null && $valor !== null) {
                if ($item === 'rol') {
                    $filtro = ' AND ' . ModeloTenant::usuarioConRol('u.idUsuario', [(string)$valor]);
                } elseif ($item === 'activo') {
                    $filtro = ' AND ui.activo = ?'; $parametros[] = (int)$valor;
                } elseif (in_array($item, ['idUsuario','nombreUsuario','apellidoUsuario','email'], true)) {
                    $filtro = ' AND u.' . $item . ' = ?'; $parametros[] = $valor;
                } else { return []; }
            }
            $stmt = Conexion::conectar()->prepare("SELECT u.idUsuario,u.nombreUsuario,u.apellidoUsuario,u.email,u.imgUsuario,
                u.ultimaConexion,u.activo activoGlobal,ui.activo,ui.fechaAlta,ui.fechaBaja,ui.motivoBaja,
                DATE_FORMAT(ui.fechaAlta,'%d/%m/%Y %H:%i') fechaAltaFmt,
                DATE_FORMAT(u.ultimaConexion,'%d/%m/%Y %H:%i') ultimaConexionFmt,
                DATE_FORMAT(ui.fechaBaja,'%d/%m/%Y %H:%i') fechaBajaFmt,
                GROUP_CONCAT(DISTINCT r.codigo ORDER BY r.codigo SEPARATOR ' · ') rol
                FROM usuarios_instituciones ui INNER JOIN usuarios u ON u.idUsuario=ui.id_usuario
                LEFT JOIN usuarios_instituciones_roles ur ON ur.id_usuario_institucion=ui.idUsuarioInstitucion
                LEFT JOIN roles r ON r.idRol=ur.id_rol AND r.codigo IN ('ADMINISTRADOR','DOCENTE','ESTUDIANTE')
                WHERE ui.id_institucion=? AND u.activo=1 AND " . ModeloTenant::sesionActiva() . " " . $filtro . ' GROUP BY ui.idUsuarioInstitucion,u.idUsuario ORDER BY u.apellidoUsuario,u.nombreUsuario');
            $stmt->execute($parametros);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
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
        if (defined('INSTITUCIONES_CONTEXTO_ACTIVO') && INSTITUCIONES_CONTEXTO_ACTIVO === true) {
            $usuarios = [];
            foreach (['DOCENTE', 'ADMINISTRADOR'] as $rol) {
                foreach (self::mdlSeleccionarUsuarios('rol', $rol) as $usuario) { $usuarios[$usuario['idUsuario']] = $usuario; }
            }
            return array_values($usuarios);
        }
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
        if (ModeloTenant::activo()) {
            $limite = time() - ($minutos * 60);
            return array_values(array_filter(self::mdlSeleccionarUsuarios('activo', 1), static function ($usuario) use ($limite) {
                $ultima = strtotime((string) ($usuario['ultimaConexion'] ?? ''));
                return $ultima !== false && $ultima >= $limite;
            }));
        }
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
        if (ModeloTenant::activo()) {
            $limite = time() - ($minutos * 60);
            return array_values(array_filter(self::mdlSeleccionarUsuarios('activo', 1), static function ($usuario) use ($limite) {
                $ultima = strtotime((string) ($usuario['ultimaConexion'] ?? ''));
                return $ultima === false || $ultima < $limite;
            }));
        }
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
        if (ModeloTenant::activo()) {
            return count(self::mdlUsuariosConectadosRecientes($minutos));
        }
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

        if (defined('INSTITUCIONES_CONTEXTO_ACTIVO') && INSTITUCIONES_CONTEXTO_ACTIVO === true) {
            require_once __DIR__ . '/tenant.modelo.php';
            $prioridadRol="CASE
                WHEN ".ModeloTenant::usuarioConRol('u.idUsuario',['ADMINISTRADOR'])." THEN 'ADMINISTRADOR'
                WHEN ".ModeloTenant::usuarioConRol('u.idUsuario',['DOCENTE'])." THEN 'DOCENTE'
                ELSE 'ESTUDIANTE' END";
            if (in_array($rolActual,['ADMINISTRADOR','DOCENTE'],true)) {
                $stmt=Conexion::conectar()->prepare("SELECT DISTINCT u.idUsuario,u.nombreUsuario,u.apellidoUsuario,u.email,$prioridadRol rol
                    FROM usuarios u INNER JOIN usuarios_instituciones ui ON ui.id_usuario=u.idUsuario
                    WHERE ui.id_institucion=? AND ui.activo=1 AND u.activo=1 AND u.idUsuario<>?
                    AND ".ModeloTenant::usuarioConRol('u.idUsuario',['ADMINISTRADOR','DOCENTE','ESTUDIANTE'])."
                    ORDER BY apellidoUsuario,nombreUsuario");
                $stmt->execute([ModeloTenant::id(),(int)$idUsuarioActual]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            $stmt=Conexion::conectar()->prepare("SELECT DISTINCT u.idUsuario,u.nombreUsuario,u.apellidoUsuario,u.email,$prioridadRol rol
                FROM usuarios u
                WHERE u.activo=1 AND u.idUsuario<>:actual
                AND ".ModeloTenant::usuarioConRol('u.idUsuario',['ADMINISTRADOR','DOCENTE','ESTUDIANTE'])."
                AND ((".ModeloTenant::usuarioConRol('u.idUsuario',['ESTUDIANTE'])." AND EXISTS(
                    SELECT 1 FROM asignacioncursos a1 INNER JOIN asignacioncursos a2 ON a2.id_seccion=a1.id_seccion
                    INNER JOIN cursos c ON c.idCurso=a1.id_seccion
                    WHERE a1.id_estudiante=:actual1 AND a2.id_estudiante=u.idUsuario
                    AND a1.estadoInscripcion='ACTIVA' AND a2.estadoInscripcion='ACTIVA' AND ".ModeloTenant::cursos('c')."))
                OR (".ModeloTenant::usuarioConRol('u.idUsuario',['ADMINISTRADOR','DOCENTE'])." AND EXISTS(
                    SELECT 1 FROM asignacioncursos a INNER JOIN cursos c ON c.idCurso=a.id_seccion
                    INNER JOIN secciones s ON s.id_curso=c.idCurso
                    WHERE a.id_estudiante=:actual2 AND a.estadoInscripcion='ACTIVA'
                    AND (s.docente=u.idUsuario OR s.tutor=u.idUsuario) AND ".ModeloTenant::cursos('c').")))
                ORDER BY rol,apellidoUsuario,nombreUsuario");
            $stmt->execute([':actual'=>(int)$idUsuarioActual,':actual1'=>(int)$idUsuarioActual,':actual2'=>(int)$idUsuarioActual]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

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
              AND a1.estadoInscripcion='ACTIVA' AND a2.estadoInscripcion='ACTIVA'
              AND u.activo = 1
              AND u.rol = 'ESTUDIANTE'
              AND u.idUsuario <> :idUsuarioActual

            UNION

            SELECT DISTINCT u.idUsuario, u.nombreUsuario, u.apellidoUsuario, u.email, u.rol
            FROM asignacioncursos a
            INNER JOIN secciones s ON s.id_curso = a.id_seccion
            INNER JOIN usuarios u ON u.idUsuario IN (s.docente, s.tutor)
            WHERE a.id_estudiante = :idUsuarioActual
              AND a.estadoInscripcion='ACTIVA'
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
              AND a.estadoInscripcion='ACTIVA'
              AND (s.docente = :idDocente OR s.tutor = :idDocente)
              AND " . (defined('INSTITUCIONES_CONTEXTO_ACTIVO') && INSTITUCIONES_CONTEXTO_ACTIVO === true ? ModeloTenant::secciones('s') : '1=1') . "
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
              AND a1.estadoInscripcion='ACTIVA' AND a2.estadoInscripcion='ACTIVA'
              AND " . (defined('INSTITUCIONES_CONTEXTO_ACTIVO') && INSTITUCIONES_CONTEXTO_ACTIVO === true
                  ? 'EXISTS (SELECT 1 FROM cursos c WHERE c.idCurso=a1.id_seccion AND '.ModeloTenant::cursos('c').')' : '1=1') . "
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

    public static function mdlCrearOMatricularUsuarioInstitucional(array $datos, array $perfil, array $roles)
    {
        ModeloTenant::exigirUsuario((int)($_SESSION['usuario']['id']??0), ['ADMINISTRADOR']);
        $roles = self::normalizarRolesInstitucionales($roles);
        $idInstitucion = ModeloTenant::id();
        $email = strtolower(trim((string) ($datos['email'] ?? '')));
        $nombre = trim((string) ($datos['nombreUsuario'] ?? ''));
        $apellido = trim((string) ($datos['apellidoUsuario'] ?? ''));
        $largoNombre = function_exists('mb_strlen') ? mb_strlen($nombre) : strlen($nombre);
        $largoApellido = function_exists('mb_strlen') ? mb_strlen($apellido) : strlen($apellido);
        if ($idInstitucion <= 0 || !$roles || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || strlen($email) > 50 || $nombre === '' || $apellido === ''
            || $largoNombre > 20 || $largoApellido > 20) {
            throw new RuntimeException('Los datos de la membresía institucional no son válidos.');
        }

        $pdo = Conexion::conectar();
        $candado = 'campus_alta_' . substr(hash('sha256', DB_NAME . '|' . $email), 0, 48);
        $lock = $pdo->prepare('SELECT GET_LOCK(?, 5)');
        $lock->execute([$candado]);
        if ((int) $lock->fetchColumn() !== 1) {
            throw new RuntimeException('No se pudo reservar la identidad para el alta.');
        }

        $idUsuarioNuevo = 0;
        $transaccionPropia = false;
        try {
            if (!$pdo->inTransaction()) { $pdo->beginTransaction(); $transaccionPropia = true; }
            $buscar = $pdo->prepare('SELECT idUsuario,activo FROM usuarios WHERE LOWER(TRIM(email))=? LIMIT 2');
            $buscar->execute([$email]);
            $coincidencias = $buscar->fetchAll(PDO::FETCH_ASSOC);
            if (count($coincidencias) > 1) {
                throw new RuntimeException('El email coincide con más de una identidad global.');
            }

            $identidadNueva = !$coincidencias;
            if ($identidadNueva) {
                if (strlen((string) ($datos['pass'] ?? '')) < 8) {
                    throw new RuntimeException('La contraseña debe tener al menos 8 caracteres para una identidad nueva.');
                }
                $insertar = $pdo->prepare("INSERT INTO usuarios
                    (nombreUsuario,apellidoUsuario,email,pass,resetPass,imgUsuario,activo,rol,fechaAlta)
                    VALUES (?,?,?,?,1,?,1,'',NOW())");
                $insertar->execute([
                    $nombre,
                    $apellido,
                    $email,
                    password_hash((string) $datos['pass'], PASSWORD_DEFAULT),
                    (string) ($datos['imgUsuario'] ?? ''),
                ]);
                $idUsuario = $idUsuarioNuevo = (int) $pdo->lastInsertId();
                $insertarPerfil = $pdo->prepare('INSERT INTO perfiles
                    (id_usuario,dniPerfil,telefonoPerfil,fnacPerfil,domicilioPerfil,provinciaPerfil,contenidoPerfil)
                    VALUES (?,?,?,?,?,?,?)');
                $insertarPerfil->execute([
                    $idUsuario,
                    ($perfil['dniPerfil'] ?? '') !== '' ? (int) $perfil['dniPerfil'] : null,
                    trim((string) ($perfil['telefonoPerfil'] ?? '')),
                    trim((string) ($perfil['fnacPerfil'] ?? '')) ?: null,
                    trim((string) ($perfil['domicilioPerfil'] ?? '')),
                    trim((string) ($perfil['provinciaPerfil'] ?? '')),
                    (string) ($perfil['contenidoPerfil'] ?? ''),
                ]);
            } else {
                if ((int) $coincidencias[0]['activo'] !== 1) {
                    throw new RuntimeException('La identidad global asociada al email está desactivada.');
                }
                $idUsuario = (int) $coincidencias[0]['idUsuario'];
                self::asegurarPerfilBasico($idUsuario);
            }

            $buscarMembresia = $pdo->prepare('SELECT idUsuarioInstitucion,activo FROM usuarios_instituciones
                WHERE id_usuario=? AND id_institucion=? FOR UPDATE');
            $buscarMembresia->execute([$idUsuario, $idInstitucion]);
            $membresia = $buscarMembresia->fetch(PDO::FETCH_ASSOC);
            if ($membresia && (int) $membresia['activo'] === 1) {
                throw new RuntimeException('El usuario ya pertenece a la institución activa.');
            }
            if ($membresia) {
                $idMembresia = (int) $membresia['idUsuarioInstitucion'];
                $pdo->prepare('UPDATE usuarios_instituciones SET activo=1,fechaBaja=NULL,motivoBaja=NULL
                    WHERE idUsuarioInstitucion=? AND id_institucion=?')->execute([$idMembresia, $idInstitucion]);
            } else {
                $pdo->prepare('INSERT INTO usuarios_instituciones(id_usuario,id_institucion,activo,fechaAlta) VALUES(?,?,1,NOW())')
                    ->execute([$idUsuario, $idInstitucion]);
                $idMembresia = (int) $pdo->lastInsertId();
            }
            self::reemplazarRolesMembresia($pdo, $idMembresia, $roles);
            if ($transaccionPropia && $pdo->inTransaction()) { $pdo->commit(); }
            return ['estado'=>'ok','idUsuario'=>$idUsuario,'identidadNueva'=>$identidadNueva];
        } catch (Throwable $e) {
            if ($transaccionPropia && $pdo->inTransaction()) { $pdo->rollBack(); }
            if ($idUsuarioNuevo > 0) {
                $pdo->prepare('DELETE FROM perfiles WHERE id_usuario=?')->execute([$idUsuarioNuevo]);
                $pdo->prepare('DELETE FROM usuarios WHERE idUsuario=? AND NOT EXISTS
                    (SELECT 1 FROM usuarios_instituciones WHERE id_usuario=?)')->execute([$idUsuarioNuevo,$idUsuarioNuevo]);
            }
            throw $e;
        } finally {
            $pdo->prepare('SELECT RELEASE_LOCK(?)')->execute([$candado]);
        }
    }

    private static function reemplazarRolesMembresia(PDO $pdo, $idMembresia, array $roles)
    {
        $roles = self::normalizarRolesInstitucionales($roles);
        if (!$roles) { throw new RuntimeException('La membresía debe conservar al menos un rol.'); }
        $marcadores = implode(',', array_fill(0, count($roles), '?'));
        $consulta = $pdo->prepare("SELECT idRol,codigo FROM roles WHERE codigo IN ($marcadores)");
        $consulta->execute($roles);
        $ids = $consulta->fetchAll(PDO::FETCH_KEY_PAIR);
        if (count($ids) !== count($roles)) { throw new RuntimeException('El catálogo de roles está incompleto.'); }
        $pdo->prepare('DELETE FROM usuarios_instituciones_roles WHERE id_usuario_institucion=?')->execute([(int)$idMembresia]);
        $insertar = $pdo->prepare('INSERT INTO usuarios_instituciones_roles(id_usuario_institucion,id_rol) VALUES(?,?)');
        foreach ($ids as $idRol => $codigo) { $insertar->execute([(int)$idMembresia,(int)$idRol]); }
    }

    public static function mdlActualizarRolesInstitucionales($idUsuario, array $roles)
    {
        ModeloTenant::exigirUsuario((int)($_SESSION['usuario']['id']??0), ['ADMINISTRADOR']);
        $roles = self::normalizarRolesInstitucionales($roles);
        $pdo = Conexion::conectar();
        $stmt = $pdo->prepare('SELECT ui.idUsuarioInstitucion FROM usuarios_instituciones ui
            INNER JOIN usuarios u ON u.idUsuario=ui.id_usuario AND u.activo=1
            WHERE ui.id_usuario=? AND ui.id_institucion=? AND ui.activo=1 AND ' . ModeloTenant::sesionActiva() . ' LIMIT 1');
        $stmt->execute([(int)$idUsuario,ModeloTenant::id()]);
        $idMembresia = (int) $stmt->fetchColumn();
        if ($idMembresia <= 0) { throw new RuntimeException('Acceso institucional denegado.'); }
        $transaccionPropia = false;
        try {
            if (!$pdo->inTransaction()) { $pdo->beginTransaction(); $transaccionPropia = true; }
            self::reemplazarRolesMembresia($pdo, $idMembresia, $roles);
            if ($transaccionPropia && $pdo->inTransaction()) { $pdo->commit(); }
            return 'ok';
        } catch (Throwable $e) {
            if ($transaccionPropia && $pdo->inTransaction()) { $pdo->rollBack(); }
            throw $e;
        }
    }

    public static function mdlModificarUsuario($tabla, $datos)
    {
        if (ModeloTenant::activo()) {
            return self::mdlActualizarRolesInstitucionales((int)($datos['idUsuario'] ?? 0), (array)($datos['roles'] ?? [$datos['rol'] ?? '']));
        }
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
        if (ModeloTenant::activo()) {
            ModeloTenant::exigirUsuario((int)($_SESSION['usuario']['id']??0), ['ADMINISTRADOR']);
            $stmt = Conexion::conectar()->prepare('UPDATE usuarios_instituciones objetivo
                INNER JOIN usuarios_instituciones acceso ON acceso.id_usuario=:idActor AND acceso.id_institucion=:idInstitucion AND acceso.activo=1
                INNER JOIN usuarios actor ON actor.idUsuario=acceso.id_usuario AND actor.activo=1
                INNER JOIN instituciones institucion ON institucion.idInstitucion=acceso.id_institucion AND institucion.activo=1
                SET objetivo.activo=0,objetivo.fechaBaja=:fechaBaja,objetivo.motivoBaja=:motivoBaja
                WHERE objetivo.id_usuario=:idUsuario AND objetivo.id_institucion=:idInstitucionObjetivo AND objetivo.activo=1');
            $stmt->execute([':fechaBaja'=>$datos['fechaBaja'],':motivoBaja'=>$datos['motivoBaja'],
                ':idUsuario'=>(int)$datos['idUsuario'],':idActor'=>(int)($_SESSION['usuario']['id']??0),
                ':idInstitucion'=>ModeloTenant::id(),':idInstitucionObjetivo'=>ModeloTenant::id()]);
            return $stmt->rowCount() === 1 ? 'ok' : 'error';
        }
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
        if (ModeloTenant::activo()) {
            ModeloTenant::exigirUsuario((int)($_SESSION['usuario']['id']??0), ['ADMINISTRADOR']);
            $stmt = Conexion::conectar()->prepare('UPDATE usuarios_instituciones objetivo
                INNER JOIN usuarios_instituciones acceso ON acceso.id_usuario=:idActor AND acceso.id_institucion=:idInstitucion AND acceso.activo=1
                INNER JOIN usuarios actor ON actor.idUsuario=acceso.id_usuario AND actor.activo=1
                INNER JOIN instituciones institucion ON institucion.idInstitucion=acceso.id_institucion AND institucion.activo=1
                SET objetivo.activo=1,objetivo.fechaBaja=NULL,objetivo.motivoBaja=NULL
                WHERE objetivo.id_usuario=:idUsuario AND objetivo.id_institucion=:idInstitucionObjetivo AND objetivo.activo=0');
            $stmt->execute([':idUsuario'=>(int)$idUsuario,':idActor'=>(int)($_SESSION['usuario']['id']??0),
                ':idInstitucion'=>ModeloTenant::id(),':idInstitucionObjetivo'=>ModeloTenant::id()]);
            return $stmt->rowCount() === 1 ? 'ok' : 'error';
        }
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
        if (ModeloTenant::activo()) {
            $stmt = Conexion::conectar()->prepare("INSERT INTO usuarios_historial
                (id_usuario,accion,detalle,id_usuario_accion,fechaEvento,id_institucion)
                SELECT :id_usuario,:accion,:detalle,:id_usuario_accion,:fechaEvento,:id_institucion
                FROM usuarios_instituciones objetivo_ui
                INNER JOIN usuarios objetivo_u ON objetivo_u.idUsuario=objetivo_ui.id_usuario
                WHERE objetivo_ui.id_usuario=:id_usuario_objetivo
                  AND objetivo_ui.id_institucion=:id_institucion_objetivo
                  AND :id_usuario_accion=:id_actor_sesion
                  AND " . ModeloTenant::sesionActiva() . '
                LIMIT 1');
            $stmt->execute([
                ':id_usuario'=>(int)$datos['id_usuario'],':accion'=>(string)$datos['accion'],
                ':detalle'=>(string)$datos['detalle'],':id_usuario_accion'=>(int)$datos['id_usuario_accion'],
                ':fechaEvento'=>(string)$datos['fechaEvento'],':id_institucion'=>ModeloTenant::id(),
                ':id_usuario_objetivo'=>(int)$datos['id_usuario'],':id_institucion_objetivo'=>ModeloTenant::id(),
                ':id_actor_sesion'=>(int)($_SESSION['usuario']['id'] ?? 0),
            ]);
            return $stmt->rowCount() === 1 ? 'ok' : 'error';
        }
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
        $filtroInstitucion = ModeloTenant::activo()
            ? ' AND h.id_institucion=' . ModeloTenant::id() . ' AND ' . ModeloTenant::sesionActiva()
            : '';
        $stmt = Conexion::conectar()->prepare("
            SELECT h.*,
                   CONCAT(u.nombreUsuario, ' ', u.apellidoUsuario) AS usuarioAccionNombre,
                   DATE_FORMAT(h.fechaEvento, '%d/%m/%Y %H:%i') AS fechaEventoFmt
            FROM usuarios_historial h
            LEFT JOIN usuarios u ON u.idUsuario = h.id_usuario_accion
            WHERE h.id_usuario = :idUsuario
            {$filtroInstitucion}
            ORDER BY h.fechaEvento DESC, h.idHistorial DESC
            LIMIT 20
        ");
        $stmt->bindValue(':idUsuario', (int) $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
