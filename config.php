<?php
/* esto lo usamos para usar la rutas correctamentes sin .php  y que tome siempre el index
al no colocar nada en la url*/

$folderPath = dirname($_SERVER['SCRIPT_NAME']);
$urlPath =$_SERVER['REQUEST_URI'];
$url =substr($urlPath,strlen($folderPath)); //esto extrae el nombre de la carpeta raiz

define('URL', $url);

//$_SESSION['id_usuario']=5;
date_default_timezone_set('America/Argentina/Mendoza'); 

?>