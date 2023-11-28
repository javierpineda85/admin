<?php
class Conexion {
    private $conexion;
    
    public 	function __construct() {
		$this->conexion = $this->conectar();
	}
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

    //Funcion para listado en general
    public function consultas($query) {
        try {
            $stmt = $this->conexion->query($query);
            $resultset = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $resultset;
        } catch (PDOException $e) {
            // Manejar la excepción aquí según tus necesidades
            echo "Error: " . $e->getMessage();
            return null;
        }
    }
    
}

?>