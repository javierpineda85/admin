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

        $link = new PDO($dsn, $user, $password);
        $link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $link->exec('set names utf8mb4');

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
            echo 'Error de conexion: ' . $e->getMessage();
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
            echo 'Error de conexion WordPress: ' . $e->getMessage();
            return null;
        }
    }

    public function consultas($query)
    {
        try {
            $stmt = $this->conexion->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo 'Error: ' . $e->getMessage();
            return null;
        }
    }
}

?>
