<?php

class Conexion
{
    private $conexion;
    private static $pdo = null;
    private static $pdoWordPress = null;

    public function __construct()
    {
        $this->conexion = self::conectar();
    }

    private static function crearPdo($host, $port, $dbName, $user, $password)
    {
        if ($dbName === '' || $user === '') {
            return null;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $host,
            $port,
            $dbName
        );

        $link = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $link->exec('set names utf8mb4');
        $link->exec("SET time_zone = '-03:00'");

        return $link;
    }

    public static function conectar()
    {
        try {
            if (self::$pdo instanceof PDO) {
                return self::$pdo;
            }

            self::$pdo = self::crearPdo(DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD);
            return self::$pdo;
        } catch (PDOException $e) {
            error_log('No se pudo conectar a la base principal: ' . get_class($e));
            if (defined('INSTITUCIONES_CONTEXTO_ACTIVO') && INSTITUCIONES_CONTEXTO_ACTIVO === true) {
                throw $e;
            }
            return null;
        }
    }

    public static function conectarWordPress()
    {
        try {
            if (self::$pdoWordPress instanceof PDO) {
                return self::$pdoWordPress;
            }

            self::$pdoWordPress = self::crearPdo(
                WP_DB_HOST,
                WP_DB_PORT,
                WP_DB_NAME,
                WP_DB_USER,
                WP_DB_PASSWORD
            );

            return self::$pdoWordPress;
        } catch (PDOException $e) {
            error_log('No se pudo conectar a WordPress: ' . get_class($e));
            if (defined('INSTITUCIONES_CONTEXTO_ACTIVO') && INSTITUCIONES_CONTEXTO_ACTIVO === true) {
                throw $e;
            }
            return null;
        }
    }
}

?>
