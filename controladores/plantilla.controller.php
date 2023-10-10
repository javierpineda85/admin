<?php
//creamos el controlador de la plantilla principal
class PlantillaController{
    public function crtGetPlantilla(){// Esto crea una funcion publica, es decir, que puedo acceder al metodo sin problemas
        include "vistas/plantilla.php"; // llama a la plantilla principal
}   
}
