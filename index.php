<?php
require_once("controladores/plantilla.controller.php");
//require_once ("controladores/formularios.controller.php");
//require_once ("modelos/formularios.modelo.php");

//Instanciar objeto

$plantilla= new PlantillaController();
 // ejecutar metodo
$plantilla->crtGetPlantilla();
?>