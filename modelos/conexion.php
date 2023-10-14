<?php
class Conexion{
    static public function conectar(){
        //Declaramos los parametros de conexion
        $link =  new PDO("mysql:host=localhost; port=3306;dbname=classroom","root","");
        $link->exec("set names utf8"); // esto es para no tener problemas con los caracteres
        return $link;
    }
}
?>