<?php
class Conexion {
    static public function conectar() {
        try {
            // Declaramos los parámetros de conexión
            $link = new PDO("mysql:host=localhost; port=3306;dbname=classroom", "root", "");
            $link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $link->exec("set names utf8"); // Esto es para no tener problemas con los caracteres
            
            // Iniciar la transacción
            $link->beginTransaction();
            
            return $link;
        } catch (PDOException $e) {
            // En caso de error, realiza un rollback y muestra un mensaje de error
            if ($link) {
                $link->rollBack();
            }
            echo "Error de conexión: " . $e->getMessage();
            return null;
        }
    }
}

?>