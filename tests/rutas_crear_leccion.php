<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

// Prueba aislada del control de rutas: no conecta ni modifica la base de datos.
require_once __DIR__ . '/../controladores/rutas.controller.php';

class ControladorInstitucion
{
    public static function activo() { return true; }
}

class ModeloMaterias
{
    public static function mdlBuscarMateriaPorId($id)
    {
        return $id === 3 ? ['idSeccion' => 3, 'id_curso' => 10] : false;
    }
}

class ModeloLecciones
{
    public static $lecciones = [];

    public static function mdlBuscarLeccionPorId($id)
    {
        return self::$lecciones[$id] ?? null;
    }
}

$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET = ['idSeccion' => 3];
$validar = new ReflectionMethod(RutasController::class, 'validarAccionPostInstitucional');
$crear = ['accion' => 'crear_leccion', 'id_modulo' => 3,
    'nombreLeccion' => 'Nueva tarea', 'tipoLeccion' => 'TAREA'];

function comprobarRutaLeccion($mensaje, array $post, array $lecciones, $esperado)
{
    global $validar;
    $_POST = $post;
    ModeloLecciones::$lecciones = $lecciones;
    if ($validar->invoke(null, 'detalle-seccion') !== $esperado) {
        throw new RuntimeException($mensaje);
    }
    echo "OK: $mensaje\n";
}

comprobarRutaLeccion('Crear en una materia sin lecciones se acepta', $crear, [], true);
comprobarRutaLeccion('El ID de una leccion ajena no interfiere con la materia de creacion',
    $crear, [3 => ['idLeccion' => 3, 'id_modulo' => 8]], true);
comprobarRutaLeccion('Crear tambien funciona si coinciden los IDs de materia y leccion',
    $crear, [3 => ['idLeccion' => 3, 'id_modulo' => 3]], true);
comprobarRutaLeccion('Crear en una materia distinta de la URL se rechaza',
    array_replace($crear, ['id_modulo' => 8]), [8 => ['idLeccion' => 8, 'id_modulo' => 3]], false);
comprobarRutaLeccion('Crear sin materia se rechaza',
    array_diff_key($crear, ['id_modulo' => true]), [], false);
comprobarRutaLeccion('Crear con un curso distinto del de la materia se rechaza',
    $crear + ['id_curso' => 99], [], false);

$lecciones = [12 => ['idLeccion' => 12, 'id_modulo' => 3],
    15 => ['idLeccion' => 15, 'id_modulo' => 8]];
comprobarRutaLeccion('Calificar una leccion propia conserva id_modulo como ID de leccion',
    ['accion' => 'guardar_calificaciones_entregas', 'id_modulo' => 12], $lecciones, true);
comprobarRutaLeccion('Calificar una leccion ajena se rechaza',
    ['accion' => 'guardar_calificaciones_entregas', 'id_modulo' => 15], $lecciones, false);
comprobarRutaLeccion('Actualizar una leccion propia se acepta',
    ['accion' => 'actualizar_leccion', 'idLeccion' => 12], $lecciones, true);
comprobarRutaLeccion('Actualizar una leccion ajena se rechaza',
    ['accion' => 'actualizar_leccion', 'idLeccion' => 15], $lecciones, false);
comprobarRutaLeccion('Crear un recurso para una leccion ajena se rechaza',
    ['accion' => 'crear_recurso', 'id_leccion' => 15], $lecciones, false);
comprobarRutaLeccion('Crear no permite inyectar una referencia a una leccion ajena',
    $crear + ['idLeccion' => 15], $lecciones, false);

echo "Pruebas de rutas de lecciones: 12/12 OK.\n";
