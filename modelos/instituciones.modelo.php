<?php
require_once __DIR__ . '/conexion.php';

class ModeloInstituciones
{
    private static function codigosRolesPermitidos()
    {
        return ['ADMINISTRADOR', 'DOCENTE', 'ESTUDIANTE'];
    }

    private static function exigirSuperAdmin()
    {
        $idUsuario = (int) ($_SESSION['usuario']['id'] ?? 0);
        if ($idUsuario <= 0 || ($_SESSION['logueado'] ?? false) !== true) {
            throw new RuntimeException('Acceso global no autorizado.');
        }

        $stmt = Conexion::conectar()->prepare('SELECT 1 FROM usuarios WHERE idUsuario = ? AND activo = 1 AND esSuperAdmin = 1');
        $stmt->execute([$idUsuario]);
        if (!$stmt->fetchColumn()) {
            throw new RuntimeException('Acceso global no autorizado.');
        }
    }

    private static function normalizarSlug($slug)
    {
        $slug = trim((string) $slug);
        if (function_exists('transliterator_transliterate')) {
            $slug = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $slug);
        } else {
            $slug = strtolower($slug);
        }
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        return trim((string) $slug, '-');
    }

    private static function datosInstitucionValidos(array $datos, $idExcluir = 0)
    {
        $nombre = trim((string) ($datos['nombre'] ?? ''));
        $slug = self::normalizarSlug($datos['slug'] ?? $nombre);
        $logo = trim((string) ($datos['logo'] ?? ''));
        if ($nombre === '' || $slug === '' || strlen($nombre) > 150 || strlen($slug) > 120 || strlen($logo) > 255) {
            throw new InvalidArgumentException('Revisá el nombre, el slug y el logo de la institución.');
        }
        if ($logo !== '' && !preg_match('~^img/instituciones/[a-zA-Z0-9_-]+\.(png|jpe?g|webp|gif)$~i', $logo)) {
            throw new InvalidArgumentException('La ruta del logo debe estar dentro de img/instituciones/.');
        }

        $stmt = Conexion::conectar()->prepare('SELECT idInstitucion FROM instituciones WHERE slug = ? AND idInstitucion <> ? LIMIT 1');
        $stmt->execute([$slug, (int) $idExcluir]);
        if ($stmt->fetchColumn()) {
            throw new InvalidArgumentException('El slug ya pertenece a otra institución.');
        }
        return [$nombre, $slug, $logo];
    }

    private static function normalizarRoles(array $roles)
    {
        $permitidos = array_flip(self::codigosRolesPermitidos());
        $normalizados = [];
        foreach ($roles as $rol) {
            $codigo = strtoupper(trim((string) $rol));
            if (isset($permitidos[$codigo])) { $normalizados[$codigo] = true; }
        }
        $normalizados = array_keys($normalizados);
        if (!$normalizados) { throw new InvalidArgumentException('Seleccioná al menos un rol institucional.'); }
        return $normalizados;
    }

    private static function reemplazarRoles(PDO $pdo, $idMembresia, array $roles)
    {
        $roles = self::normalizarRoles($roles);
        $marcadores = implode(',', array_fill(0, count($roles), '?'));
        $stmt = $pdo->prepare("SELECT idRol,codigo FROM roles WHERE codigo IN ($marcadores)");
        $stmt->execute($roles);
        $catalogo = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        if (count($catalogo) !== count($roles)) { throw new RuntimeException('El catálogo de roles institucionales está incompleto.'); }
        $pdo->prepare('DELETE FROM usuarios_instituciones_roles WHERE id_usuario_institucion=?')->execute([(int)$idMembresia]);
        $insertar = $pdo->prepare('INSERT INTO usuarios_instituciones_roles(id_usuario_institucion,id_rol) VALUES(?,?)');
        foreach (array_keys($catalogo) as $idRol) { $insertar->execute([(int)$idMembresia,(int)$idRol]); }
    }

    public static function mdlVerificarEsquema()
    {
        $pdo = Conexion::conectar();
        if (!$pdo instanceof PDO) {
            throw new RuntimeException('No hay conexión para validar el contexto institucional.');
        }
        $stmt = $pdo->prepare('SELECT codigo FROM campus_migraciones WHERE codigo = ?');
        $stmt->execute(['multi_institucion_01_expandir']);
        if (!$stmt->fetchColumn()) {
            throw new RuntimeException('La expansión institucional no está completa.');
        }
    }

    public static function mdlIdentidadActiva($idUsuario)
    {
        $stmt = Conexion::conectar()->prepare('SELECT idUsuario, nombreUsuario, apellidoUsuario, email, imgUsuario, esSuperAdmin FROM usuarios WHERE idUsuario = ? AND activo = 1');
        $stmt->execute([(int) $idUsuario]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function mdlMembresiasActivas($idUsuario)
    {
        $stmt = Conexion::conectar()->prepare('
            SELECT i.idInstitucion, i.nombre, i.slug, i.logo,
                   ui.idUsuarioInstitucion, ui.id_usuario, ui.fechaAlta,
                   r.codigo
            FROM usuarios_instituciones ui
            INNER JOIN instituciones i ON i.idInstitucion = ui.id_institucion AND i.activo = 1
            INNER JOIN usuarios u ON u.idUsuario = ui.id_usuario AND u.activo = 1
            LEFT JOIN usuarios_instituciones_roles ur ON ur.id_usuario_institucion = ui.idUsuarioInstitucion
            LEFT JOIN roles r ON r.idRol = ur.id_rol
            WHERE ui.id_usuario = ? AND ui.activo = 1
            ORDER BY i.nombre, i.idInstitucion, r.codigo
        ');
        $stmt->execute([(int) $idUsuario]);
        $membresias = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $id = (int) $fila['idInstitucion'];
            if (!isset($membresias[$id])) {
                $membresias[$id] = [
                    'idInstitucion' => $id,
                    'nombre' => (string) $fila['nombre'],
                    'slug' => (string) $fila['slug'],
                    'logo' => (string) ($fila['logo'] ?? ''),
                    'idUsuarioInstitucion' => (int) $fila['idUsuarioInstitucion'],
                    'id_usuario' => (int) $fila['id_usuario'],
                    'fechaAlta' => $fila['fechaAlta'],
                    'roles' => [],
                ];
            }
            // Catálogo conocido. GESTOR y códigos sin permisos no se promueven.
            if (in_array($fila['codigo'], self::codigosRolesPermitidos(), true)) {
                $membresias[$id]['roles'][] = $fila['codigo'];
            }
        }
        return array_values($membresias);
    }

    /** Roles efectivos de otro usuario dentro de una institución activa. */
    public static function mdlRolesUsuarioInstitucion($idUsuario, $idInstitucion)
    {
        $idUsuario = (int) $idUsuario;
        $idInstitucion = (int) $idInstitucion;
        if ($idUsuario <= 0 || $idInstitucion <= 0) {
            return [];
        }

        $stmt = Conexion::conectar()->prepare('
            SELECT DISTINCT r.codigo
            FROM usuarios_instituciones ui
            INNER JOIN usuarios u ON u.idUsuario = ui.id_usuario AND u.activo = 1
            INNER JOIN instituciones i ON i.idInstitucion = ui.id_institucion AND i.activo = 1
            INNER JOIN usuarios_instituciones_roles ur ON ur.id_usuario_institucion = ui.idUsuarioInstitucion
            INNER JOIN roles r ON r.idRol = ur.id_rol
            WHERE ui.id_usuario = ?
              AND ui.id_institucion = ?
              AND ui.activo = 1
            ORDER BY r.codigo
        ');
        $stmt->execute([$idUsuario, $idInstitucion]);

        return array_values(array_filter(
            array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN)),
            static function ($codigo) {
                return in_array($codigo, self::codigosRolesPermitidos(), true);
            }
        ));
    }

    public static function mdlResumenPlataforma()
    {
        self::exigirSuperAdmin();
        $pdo = Conexion::conectar();
        return [
            'instituciones' => (int) $pdo->query('SELECT COUNT(*) FROM instituciones')->fetchColumn(),
            'institucionesActivas' => (int) $pdo->query('SELECT COUNT(*) FROM instituciones WHERE activo = 1')->fetchColumn(),
            'institucionesSuspendidas' => (int) $pdo->query('SELECT COUNT(*) FROM instituciones WHERE activo = 0')->fetchColumn(),
            'usuariosGlobalesActivos' => (int) $pdo->query('SELECT COUNT(*) FROM usuarios WHERE activo = 1')->fetchColumn(),
            'membresiasActivas' => (int) $pdo->query('SELECT COUNT(*) FROM usuarios_instituciones WHERE activo = 1')->fetchColumn(),
        ];
    }

    public static function mdlListarInstituciones()
    {
        self::exigirSuperAdmin();
        $stmt = Conexion::conectar()->query("
            SELECT i.*,
                   COUNT(DISTINCT CASE WHEN ui.activo = 1 AND u.activo = 1 THEN ui.id_usuario END) AS totalUsuarios,
                   COUNT(DISTINCT CASE WHEN ui.activo = 1 AND u.activo = 1 AND r.codigo = 'ADMINISTRADOR' THEN ui.id_usuario END) AS totalAdministradores
            FROM instituciones i
            LEFT JOIN usuarios_instituciones ui ON ui.id_institucion = i.idInstitucion
            LEFT JOIN usuarios u ON u.idUsuario = ui.id_usuario
            LEFT JOIN usuarios_instituciones_roles uir ON uir.id_usuario_institucion = ui.idUsuarioInstitucion
            LEFT JOIN roles r ON r.idRol = uir.id_rol
            GROUP BY i.idInstitucion
            ORDER BY i.nombre, i.idInstitucion
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlObtenerInstitucion($idInstitucion)
    {
        self::exigirSuperAdmin();
        $stmt = Conexion::conectar()->prepare('SELECT * FROM instituciones WHERE idInstitucion=? LIMIT 1');
        $stmt->execute([(int)$idInstitucion]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function mdlLogoUsadoPorOtraInstitucion($ruta, $idExcluir)
    {
        self::exigirSuperAdmin();
        $stmt = Conexion::conectar()->prepare('SELECT 1 FROM instituciones WHERE logo=? AND idInstitucion<>? LIMIT 1');
        $stmt->execute([(string)$ruta,(int)$idExcluir]);
        return (bool)$stmt->fetchColumn();
    }

    public static function mdlRolesDisponibles()
    {
        self::exigirSuperAdmin();
        $permitidos = self::codigosRolesPermitidos();
        $marcadores = implode(',', array_fill(0, count($permitidos), '?'));
        $stmt = Conexion::conectar()->prepare("SELECT codigo,nombre FROM roles WHERE codigo IN ($marcadores) ORDER BY FIELD(codigo,'ADMINISTRADOR','DOCENTE','ESTUDIANTE')");
        $stmt->execute($permitidos);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlListarMembresias()
    {
        self::exigirSuperAdmin();
        $stmt = Conexion::conectar()->query("SELECT ui.idUsuarioInstitucion,ui.id_usuario,ui.id_institucion,ui.activo,
                ui.fechaAlta,ui.fechaBaja,ui.motivoBaja,u.nombreUsuario,u.apellidoUsuario,u.email,u.activo usuarioActivo,
                i.nombre institucion,i.activo institucionActiva,
                GROUP_CONCAT(DISTINCT r.codigo ORDER BY FIELD(r.codigo,'ADMINISTRADOR','DOCENTE','ESTUDIANTE') SEPARATOR ',') roles
            FROM usuarios_instituciones ui
            INNER JOIN usuarios u ON u.idUsuario=ui.id_usuario
            INNER JOIN instituciones i ON i.idInstitucion=ui.id_institucion
            LEFT JOIN usuarios_instituciones_roles uir ON uir.id_usuario_institucion=ui.idUsuarioInstitucion
            LEFT JOIN roles r ON r.idRol=uir.id_rol
            GROUP BY ui.idUsuarioInstitucion
            ORDER BY i.nombre,u.apellidoUsuario,u.nombreUsuario,u.idUsuario");
        $membresias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($membresias as &$membresia) {
            $membresia['roles'] = array_values(array_filter(explode(',', (string)($membresia['roles'] ?? ''))));
        }
        unset($membresia);
        return $membresias;
    }

    public static function mdlCrearInstitucion(array $datos)
    {
        self::exigirSuperAdmin();
        [$nombre, $slug, $logo] = self::datosInstitucionValidos($datos);
        $stmt = Conexion::conectar()->prepare('INSERT INTO instituciones (nombre, slug, logo, activo, fechaAlta) VALUES (?, ?, ?, 1, NOW())');
        $stmt->execute([$nombre, $slug, $logo !== '' ? $logo : null]);
        return (int) Conexion::conectar()->lastInsertId();
    }

    public static function mdlActualizarInstitucion($idInstitucion, array $datos)
    {
        self::exigirSuperAdmin();
        $idInstitucion = (int) $idInstitucion;
        if ($idInstitucion <= 0) { throw new InvalidArgumentException('Institución inválida.'); }
        [$nombre, $slug, $logo] = self::datosInstitucionValidos($datos, $idInstitucion);
        $stmt = Conexion::conectar()->prepare('UPDATE instituciones SET nombre = ?, slug = ?, logo = ? WHERE idInstitucion = ?');
        $stmt->execute([$nombre, $slug, $logo !== '' ? $logo : null, $idInstitucion]);
        if ($stmt->rowCount() === 0) {
            $existe = Conexion::conectar()->prepare('SELECT 1 FROM instituciones WHERE idInstitucion = ?');
            $existe->execute([$idInstitucion]);
            if (!$existe->fetchColumn()) { throw new RuntimeException('La institución no existe.'); }
        }
        return true;
    }

    public static function mdlCambiarEstadoInstitucion($idInstitucion, $activo, $motivo = '')
    {
        self::exigirSuperAdmin();
        $idInstitucion = (int) $idInstitucion;
        $activo = (int) ((bool) $activo);
        $motivo = trim((string) $motivo);
        if ($idInstitucion <= 0 || (!$activo && $motivo === '')) {
            throw new InvalidArgumentException('Indicá la institución y el motivo de suspensión.');
        }
        $stmt = Conexion::conectar()->prepare($activo
            ? 'UPDATE instituciones SET activo = 1, fechaBaja = NULL, motivoBaja = NULL WHERE idInstitucion = ?'
            : 'UPDATE instituciones SET activo = 0, fechaBaja = NOW(), motivoBaja = ? WHERE idInstitucion = ?');
        $stmt->execute($activo ? [$idInstitucion] : [$motivo, $idInstitucion]);
        return $stmt->rowCount() > 0;
    }

    public static function mdlAsignarAdministrador($idInstitucion, $email)
    {
        return self::mdlGuardarMembresia($idInstitucion, $email, ['ADMINISTRADOR']);
    }

    public static function mdlUsuariosParaMembresias()
    {
        self::exigirSuperAdmin();
        return Conexion::conectar()->query('SELECT idUsuario,nombreUsuario,apellidoUsuario,email FROM usuarios
            WHERE activo=1 ORDER BY apellidoUsuario,nombreUsuario,idUsuario')->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Agrega accesos sin modificar membresías anteriores, ni siquiera las suspendidas. */
    public static function mdlAgregarMembresias($idInstitucion, array $usuarios, array $roles)
    {
        self::exigirSuperAdmin();
        $roles = self::normalizarRoles($roles);
        if (!$usuarios || count($usuarios) > 200) {
            throw new InvalidArgumentException('Seleccioná entre 1 y 200 usuarios por operación.');
        }
        $ids = [];
        foreach ($usuarios as $usuario) {
            if (!is_scalar($usuario) || !ctype_digit((string)$usuario) || (int)$usuario <= 0) {
                throw new InvalidArgumentException('La selección contiene un usuario inválido.');
            }
            $ids[(int)$usuario] = (int)$usuario;
        }
        sort($ids);
        $pdo = Conexion::conectar();
        $pdo->beginTransaction();
        try {
            $institucion = $pdo->prepare('SELECT idInstitucion FROM instituciones WHERE idInstitucion=? AND activo=1 FOR UPDATE');
            $institucion->execute([(int)$idInstitucion]);
            if (!$institucion->fetchColumn()) {
                throw new InvalidArgumentException('Seleccioná una institución activa.');
            }
            $cuenta = $pdo->prepare('SELECT idUsuario FROM usuarios WHERE idUsuario=? AND activo=1 FOR UPDATE');
            $existente = $pdo->prepare('SELECT idUsuarioInstitucion FROM usuarios_instituciones WHERE id_usuario=? AND id_institucion=? FOR UPDATE');
            $insertar = $pdo->prepare('INSERT INTO usuarios_instituciones(id_usuario,id_institucion,activo,fechaAlta) VALUES(?,?,1,NOW())');
            $resultado = ['agregadas'=>0, 'existentes'=>0];
            foreach ($ids as $idUsuario) {
                $cuenta->execute([$idUsuario]);
                if (!$cuenta->fetchColumn()) {
                    throw new InvalidArgumentException('Una cuenta seleccionada ya no está activa o no existe. No se agregó ninguna membresía.');
                }
                $existente->execute([$idUsuario,(int)$idInstitucion]);
                if ($existente->fetchColumn()) {
                    $resultado['existentes']++;
                    continue;
                }
                $insertar->execute([$idUsuario,(int)$idInstitucion]);
                self::reemplazarRoles($pdo, (int)$pdo->lastInsertId(), $roles);
                $resultado['agregadas']++;
            }
            $pdo->commit();
            return $resultado;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            throw $e;
        }
    }

    public static function mdlGuardarMembresia($idInstitucion, $email, array $roles)
    {
        self::exigirSuperAdmin();
        $idInstitucion = (int) $idInstitucion;
        $email = strtolower(trim((string) $email));
        $roles = self::normalizarRoles($roles);
        if ($idInstitucion <= 0 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Indicá una institución y un email válidos.');
        }
        $pdo = Conexion::conectar();
        $stmt = $pdo->prepare('SELECT idUsuario FROM usuarios WHERE LOWER(email) = ? AND activo = 1 LIMIT 1');
        $stmt->execute([$email]);
        $idUsuario = (int) $stmt->fetchColumn();
        if ($idUsuario <= 0) {
            throw new RuntimeException('No existe una cuenta global activa con ese email.');
        }
        $stmt = $pdo->prepare('SELECT 1 FROM instituciones WHERE idInstitucion = ? AND activo = 1');
        $stmt->execute([$idInstitucion]);
        if (!$stmt->fetchColumn()) {
            throw new RuntimeException('La institución debe estar activa para asignar administradores.');
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT idUsuarioInstitucion FROM usuarios_instituciones WHERE id_usuario = ? AND id_institucion = ? LIMIT 1 FOR UPDATE');
            $stmt->execute([$idUsuario, $idInstitucion]);
            $idMembresia = (int) $stmt->fetchColumn();
            if ($idMembresia > 0) {
                $pdo->prepare('UPDATE usuarios_instituciones SET activo=1,fechaBaja=NULL,motivoBaja=NULL WHERE idUsuarioInstitucion=?')->execute([$idMembresia]);
            } else {
                $pdo->prepare('INSERT INTO usuarios_instituciones (id_usuario, id_institucion, activo, fechaAlta) VALUES (?, ?, 1, NOW())')->execute([$idUsuario, $idInstitucion]);
                $idMembresia = (int) $pdo->lastInsertId();
            }
            self::reemplazarRoles($pdo, $idMembresia, $roles);
            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            throw $e;
        }
    }

    public static function mdlActualizarRolesMembresia($idMembresia, array $roles)
    {
        self::exigirSuperAdmin();
        $idMembresia = (int)$idMembresia;
        $roles = self::normalizarRoles($roles);
        $pdo = Conexion::conectar();
        $stmt = $pdo->prepare('SELECT 1 FROM usuarios_instituciones ui INNER JOIN usuarios u ON u.idUsuario=ui.id_usuario INNER JOIN instituciones i ON i.idInstitucion=ui.id_institucion WHERE ui.idUsuarioInstitucion=? LIMIT 1');
        $stmt->execute([$idMembresia]);
        if (!$stmt->fetchColumn()) { throw new RuntimeException('La membresía no existe.'); }
        $pdo->beginTransaction();
        try {
            self::reemplazarRoles($pdo, $idMembresia, $roles);
            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            throw $e;
        }
    }

    public static function mdlCambiarEstadoMembresia($idMembresia, $activo, $motivo = '')
    {
        self::exigirSuperAdmin();
        $idMembresia = (int)$idMembresia;
        $activo = (bool)$activo;
        $motivo = trim((string)$motivo);
        if ($idMembresia <= 0 || (!$activo && $motivo === '')) {
            throw new InvalidArgumentException('Indicá la membresía y el motivo de suspensión.');
        }
        $pdo = Conexion::conectar();
        if ($activo) {
            $stmt = $pdo->prepare('UPDATE usuarios_instituciones ui INNER JOIN usuarios u ON u.idUsuario=ui.id_usuario AND u.activo=1 INNER JOIN instituciones i ON i.idInstitucion=ui.id_institucion AND i.activo=1 SET ui.activo=1,ui.fechaBaja=NULL,ui.motivoBaja=NULL WHERE ui.idUsuarioInstitucion=? AND ui.activo=0');
            $stmt->execute([$idMembresia]);
        } else {
            $stmt = $pdo->prepare('UPDATE usuarios_instituciones SET activo=0,fechaBaja=NOW(),motivoBaja=? WHERE idUsuarioInstitucion=? AND activo=1');
            $stmt->execute([$motivo,$idMembresia]);
        }
        if ($stmt->rowCount() === 0) {
            $existe = $pdo->prepare('SELECT activo FROM usuarios_instituciones WHERE idUsuarioInstitucion=?');
            $existe->execute([$idMembresia]);
            $estado = $existe->fetchColumn();
            if ($estado === false) { throw new RuntimeException('La membresía no existe.'); }
            if ($activo && (int)$estado === 0) { throw new RuntimeException('La cuenta o la institución no están activas.'); }
        }
        return true;
    }
}
