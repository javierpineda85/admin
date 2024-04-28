<?php
require_once("config.php");
require_once("controladores/rutas.controller.php");
require_once("controladores/plantilla.controller.php");
require_once("controladores/usuarios.controller.php");
require_once("controladores/cursos.controller.php");
require_once("controladores/materias.controller.php");
require_once("controladores/perfiles.controller.php");
require_once("controladores/mensajes.controller.php");


$plantilla= new PlantillaController();
 // ejecutar metodo
$plantilla->crtGetPlantilla();


?>