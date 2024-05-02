<?php

class autocarga
{
    static public function cargarClases($clase)
    {
        $clases = array(
            'cursos'    => "controladores/cursos.controller.php",
            'materias'  => "controladores/materias.controller.php",
            'mensajes'  => "controladores/mensajes.controller.php",
            'perfiles'  => "controladores/perfiles.controller.php",
            'usuarios'  => "controladores/usuarios.controller.php",
            'plantilla' => "controladores/plantilla.controller.php",
            'rutas'     => "controladores/rutas.controller.php"
        );

        if (isset($clases[$clase])) {
            if (file_exists($clases[$clase])) {
                include $clases[$clase];
            } else {
                throw new Exception("Error al cargar la clase" . $clases[$clase]);
            }
        }
    }
}
