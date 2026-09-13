<?php
require_once __DIR__ . '/conexion.php';

class ModeloInstituciones
{
    private static function codigosRolesPermitidos()
    {
        return ['ADMINISTRADOR', 'DOCENTE', 'ESTUDIANTE'];
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
}
