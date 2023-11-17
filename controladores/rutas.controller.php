<?php
    // Defino la clase Rutas para mejor funcionamiento
    class RutasController {
        public static function cargarVista(){ // se crea el array de rutas para poder escalar el proyecto

         $mapeo = [
            //usuario
            "crear-usuario" => "usuario/crear-usuario.php",
            "listado-usuarios" => "usuario/listado-usuarios.php",
            "perfil-usuario" => "usuario/perfil-usuario.php",
            //cursos
            "crear-curso" => "cursos/crear-curso.php",
            "listado-cursos" => "cursos/listado-cursos.php",
            "editar-curso" => "cursos/editar-curso.php",
            "detalle-curso" => "cursos/detalle-curso.php",
            //materias
            "listado-materias" => "materias/listado-materias.php",
            "crear-materia" => "materias/crear-materia.php",
            //Mensajes
            "bandeja-entrada" => "mensajes/bandeja-entrada.php",
            "nuevo-mensaje" => "mensajes/nuevo-mensaje.php",
            "mensajes-enviados" => "mensajes/mensajes-enviados.php",
        ];


        if (isset($_GET['r']) && array_key_exists($_GET['r'], $mapeo)) {
            $archivo = "vistas/paginas/" . $mapeo[$_GET['r']];
            include(file_exists($archivo) ? $archivo : "vistas/paginas/404.php");
        } else {
            include("vistas/paginas/inicio.php");
        }
    }
}