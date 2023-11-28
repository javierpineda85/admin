<?php

class autocarga {

static public function cargarClases($clase)
{
    
    $clases= array();
    $clases['cursos']= "controladores/cursos.controller.php";
    $clases['materias']="controladores/materias.controller.php";
    $clases['mensajes']="controladores/mensajes.controller.php";
    $clases['perfiles']="controladores/perfiles.controller.php";
    $clases['usuarios']="controladores/usuarios.controller.php";    
    $clases['plantilla']="controladores/plantilla.controller.php";
    
   

    if (isset($clases[$clase])) {

        if (file_exists($clases[$clase])) {
            include $clases[$clase];

        } else {
            throw new Exception("Error al cargar la clase" . $clases[$clase]);
            
        }

    }
      
}
}
spl_autoload_register('autocarga::cargarClases');
?>