<?php

require_once __DIR__ . '/conexion.php';

class ModeloSeguridadAuth
{
    private static $esquemaVerificado = false;

    public static function asegurarEsquema()
    {
        if (self::$esquemaVerificado) { return; }
        $pdo = Conexion::conectar();
        $pdo->exec("CREATE TABLE IF NOT EXISTS auth_recuperaciones (
            idRecuperacion BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            id_usuario INT NOT NULL,
            tokenHash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
            creadoEn DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            venceEn DATETIME NOT NULL,
            usadoEn DATETIME NULL,
            ipHash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
            PRIMARY KEY (idRecuperacion),
            UNIQUE KEY uq_auth_recuperacion_token (tokenHash),
            KEY idx_auth_recuperacion_usuario (id_usuario, usadoEn, venceEn)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE TABLE IF NOT EXISTS auth_intentos (
            idIntento BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            tipo VARCHAR(30) NOT NULL,
            claveHash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
            creadoEn DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (idIntento),
            KEY idx_auth_intento_limite (tipo, claveHash, creadoEn)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        self::$esquemaVerificado = true;
    }

    private static function hashClave($clave)
    {
        $secreto = (string) DB_PASSWORD . '|' . (string) DB_NAME . '|' . (string) APP_BASE_URL;
        return hash_hmac('sha256', (string) $clave, hash('sha256', $secreto, true));
    }

    public static function limitado($tipo, $clave, $maximo, $ventanaSegundos)
    {
        self::asegurarEsquema();
        $pdo = Conexion::conectar();
        $pdo->exec("DELETE FROM auth_intentos WHERE creadoEn < DATE_SUB(NOW(), INTERVAL 2 DAY)");
        $ventanaSegundos = max(1, (int) $ventanaSegundos);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM auth_intentos
            WHERE tipo = :tipo AND claveHash = :claveHash
              AND creadoEn >= DATE_SUB(NOW(), INTERVAL ' . $ventanaSegundos . ' SECOND)');
        $stmt->bindValue(':tipo', (string) $tipo, PDO::PARAM_STR);
        $stmt->bindValue(':claveHash', self::hashClave($clave), PDO::PARAM_STR);
        $stmt->execute();
        return (int) $stmt->fetchColumn() >= max(1, (int) $maximo);
    }

    public static function registrarFallo($tipo, $clave)
    {
        self::asegurarEsquema();
        $stmt = Conexion::conectar()->prepare('INSERT INTO auth_intentos (tipo, claveHash) VALUES (:tipo, :claveHash)');
        return $stmt->execute([
            ':tipo' => (string) $tipo,
            ':claveHash' => self::hashClave($clave),
        ]);
    }

    public static function limpiarIntentos($tipo, $clave)
    {
        self::asegurarEsquema();
        $stmt = Conexion::conectar()->prepare('DELETE FROM auth_intentos WHERE tipo = :tipo AND claveHash = :claveHash');
        return $stmt->execute([
            ':tipo' => (string) $tipo,
            ':claveHash' => self::hashClave($clave),
        ]);
    }

    public static function crearRecuperacion($idUsuario, $ip, $duracionSegundos = 3600)
    {
        self::asegurarEsquema();
        $pdo = Conexion::conectar();
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $vence = date('Y-m-d H:i:s', time() + max(300, (int) $duracionSegundos));
        $transaccionPropia = !$pdo->inTransaction();
        try {
            if ($transaccionPropia) { $pdo->beginTransaction(); }
            $invalidar = $pdo->prepare('UPDATE auth_recuperaciones SET usadoEn = NOW()
                WHERE id_usuario = :idUsuario AND usadoEn IS NULL');
            $invalidar->execute([':idUsuario' => (int) $idUsuario]);
            $insertar = $pdo->prepare('INSERT INTO auth_recuperaciones
                (id_usuario, tokenHash, venceEn, ipHash) VALUES (:idUsuario, :tokenHash, :venceEn, :ipHash)');
            $insertar->execute([
                ':idUsuario' => (int) $idUsuario,
                ':tokenHash' => $tokenHash,
                ':venceEn' => $vence,
                ':ipHash' => self::hashClave('ip|' . (string) $ip),
            ]);
            if ($transaccionPropia) { $pdo->commit(); }
            return $token;
        } catch (Throwable $e) {
            if ($transaccionPropia && $pdo->inTransaction()) { $pdo->rollBack(); }
            throw $e;
        }
    }

    public static function invalidarRecuperacion($token)
    {
        self::asegurarEsquema();
        $stmt = Conexion::conectar()->prepare('UPDATE auth_recuperaciones SET usadoEn = NOW()
            WHERE tokenHash = :tokenHash AND usadoEn IS NULL');
        return $stmt->execute([':tokenHash' => hash('sha256', (string) $token)]);
    }

    public static function buscarRecuperacion($token)
    {
        if (!is_string($token) || preg_match('/^[a-f0-9]{64}$/D', $token) !== 1) { return null; }
        self::asegurarEsquema();
        $stmt = Conexion::conectar()->prepare('SELECT r.idRecuperacion, r.id_usuario, r.venceEn, u.email
            FROM auth_recuperaciones r INNER JOIN usuarios u ON u.idUsuario = r.id_usuario
            WHERE r.tokenHash = :tokenHash AND r.usadoEn IS NULL AND r.venceEn >= NOW() AND u.activo = 1
            LIMIT 1');
        $stmt->execute([':tokenHash' => hash('sha256', $token)]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    public static function consumirRecuperacion($token, $passwordHash)
    {
        if (!is_string($token) || preg_match('/^[a-f0-9]{64}$/D', $token) !== 1) { return 0; }
        self::asegurarEsquema();
        $pdo = Conexion::conectar();
        $transaccionPropia = !$pdo->inTransaction();
        try {
            if ($transaccionPropia) { $pdo->beginTransaction(); }
            $buscar = $pdo->prepare('SELECT r.idRecuperacion, r.id_usuario
                FROM auth_recuperaciones r INNER JOIN usuarios u ON u.idUsuario = r.id_usuario
                WHERE r.tokenHash = :tokenHash AND r.usadoEn IS NULL AND r.venceEn >= NOW() AND u.activo = 1
                LIMIT 1 FOR UPDATE');
            $buscar->execute([':tokenHash' => hash('sha256', $token)]);
            $recuperacion = $buscar->fetch(PDO::FETCH_ASSOC);
            if (!$recuperacion) {
                if ($transaccionPropia) { $pdo->rollBack(); }
                return 0;
            }
            $idUsuario = (int) $recuperacion['id_usuario'];
            $actualizar = $pdo->prepare('UPDATE usuarios SET pass = :pass, resetPass = 0 WHERE idUsuario = :idUsuario AND activo = 1');
            $actualizar->execute([':pass' => (string) $passwordHash, ':idUsuario' => $idUsuario]);
            if ($actualizar->rowCount() !== 1) {
                if ($transaccionPropia) { $pdo->rollBack(); }
                return 0;
            }
            $consumir = $pdo->prepare('UPDATE auth_recuperaciones SET usadoEn = NOW()
                WHERE id_usuario = :idUsuario AND usadoEn IS NULL');
            $consumir->execute([':idUsuario' => $idUsuario]);
            if ($transaccionPropia) { $pdo->commit(); }
            return $idUsuario;
        } catch (Throwable $e) {
            if ($transaccionPropia && $pdo->inTransaction()) { $pdo->rollBack(); }
            throw $e;
        }
    }
}
