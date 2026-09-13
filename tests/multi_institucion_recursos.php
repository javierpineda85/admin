<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
// Reutiliza fixtures aisladas y verifica además el flujo de autenticación completo.
ob_start();
require __DIR__ . '/multi_institucion_contexto.php';
require_once __DIR__ . '/../modelos/cursos.modelo.php';
require_once __DIR__ . '/../modelos/materias.modelo.php';
require_once __DIR__ . '/../modelos/lecciones.modelo.php';
require_once __DIR__ . '/../modelos/asistencias.modelo.php';
require_once __DIR__ . '/../modelos/calificaciones.modelo.php';
foreach (['secciones', 'lecciones', 'asignacioncursos', 'recursoslecciones', 'entregaslecciones', 'posteos',
    'calificaciones','entregaslecciones_adjuntos','archivoslecciones','actividades','actividades_preguntas','actividades_opciones',
    'asistencia_clases','asistencia_registros'] as $tabla) {
    $pdo->exec("CREATE TABLE `$tabla` LIKE `$origen`.`$tabla`");
}
ejecutarMigracion($pdo, __DIR__ . '/../sql/2026-09-14_multi_institucion_01_expandir.sql');
function denegado(callable $operacion, $mensaje) {
    try { $operacion(); } catch (RuntimeException $e) {
        verificar(strpos($e->getMessage(), 'institucional') !== false, $mensaje);
        return;
    }
    throw new RuntimeException('No se rechazó: ' . $mensaje);
}
sesionPara($ids['A']);
ControladorInstitucion::seleccionar($mm, ControladorInstitucion::csrf(), ControladorInstitucion::version());
$datosCurso = ['nombreCurso'=>'Curso MenteMotion','contenidoCurso'=>'Contenido de ensayo','estado'=>'ACTIVO',
    'fechaInicioCurso'=>'2026-01-01','fechaFinCurso'=>'2026-12-31','horarioCurso'=>'Lunes',
    'creadoPor'=>$ids['A'],'responsable'=>$ids['A'],'modalidadCalificacion'=>'DOS_TRAMOS','intensificacionActiva'=>1];
verificar(ModeloCursos::mdlGuardarCurso('cursos',$datosCurso)==='ok', 'Curso nuevo obtiene institución desde contexto');
$cursoMM=(int)$pdo->lastInsertId();
sesionPara($ids['B']);
$datosCurso['nombreCurso']='Curso Demo'; $datosCurso['creadoPor']=$ids['B']; $datosCurso['responsable']=$ids['B'];
ModeloCursos::mdlGuardarCurso('cursos',$datosCurso); $cursoDemo=(int)$pdo->lastInsertId();
verificar(ModeloCursos::mdlActualizarResponsableCurso($cursoDemo,$ids['B'])==='ok', 'Escritura de curso propio aplica filtro institucional');
verificar(array_column(ModeloCursos::mdlListarCursos(),'idCurso')==[$cursoDemo], 'Listado de cursos de Demo excluye MenteMotion');
$miembrosDemo=ModeloUsuarios::mdlSeleccionarUsuarios('activo',1);
verificar(!in_array($ids['C'],array_column($miembrosDemo,'idUsuario')), 'Administrador de Demo no lista usuarios exclusivos de MenteMotion');
verificar(!array_key_exists('pass',$miembrosDemo[0]), 'Listado institucional no expone hashes de identidad global');
verificar(array_column(ModeloUsuarios::mdlSeleccionarUsuarios('rol','ESTUDIANTE'),'idUsuario')==[$ids['A']], 'Filtro de estudiantes usa membresías y no rol global');
verificar(ModeloCursos::mdlBuscarCursoPorId($cursoMM)===null, 'Lectura directa por ID de curso ajeno no devuelve datos');
denegado(function() use ($cursoMM) { ModeloCursos::mdlEliminarCurso($cursoMM); }, 'B no puede eliminar curso de MenteMotion');
denegado(function() use ($cursoDemo,$ids) { ModeloCursos::mdlAsignarCurso('asignacioncursos',['idCurso'=>$cursoDemo,'idUsuario'=>$ids['C']]); }, 'Inscripción rechaza estudiante de otra institución');
verificar(ModeloCursos::mdlAsignarCurso('asignacioncursos',['idCurso'=>$cursoDemo,'idUsuario'=>$ids['A']])==='ok', 'Inscripción usa rol institucional de A en Demo');
$datosMateria=['tituloSeccion'=>'Materia Demo','contenidoSeccion'=>'Prueba','id_curso'=>$cursoDemo,
    'docente'=>$ids['B'],'tutor'=>0,'bannerSeccion'=>'','colorInicioBanner'=>'#000000','colorFinBanner'=>'#ffffff','creadoPor'=>$ids['B']];
verificar(ModeloMaterias::mdlGuardarMateria('secciones',$datosMateria)==='ok', 'Materia se crea con docente de la institución');
$materiaDemo=(int)$pdo->lastInsertId();
verificar(ModeloMaterias::mdlActualizarDocentesMateria($materiaDemo,$ids['B'],0)==='ok', 'Escritura de materia propia aplica filtro institucional');
$claseDemo=ModeloAsistencias::mdlCrearClase($materiaDemo,$cursoDemo,'2026-09-13','Clase Demo',$ids['B']);
verificar($claseDemo>0, 'Asistencia crea una clase dentro de la institución activa');
$registrosDemo=ModeloAsistencias::mdlRegistrosClase($claseDemo);
verificar(array_column($registrosDemo,'id_estudiante')==[$ids['A']], 'Asistencia incorpora solo estudiantes activos de la institución y del curso');
verificar(ModeloAsistencias::mdlGuardar($claseDemo,[$ids['A']=>'AUSENTE'],[$ids['A']=>'Ensayo'],$ids['B'])==='ok', 'Asistencia propia se actualiza con contexto institucional');
verificar((string)$pdo->query('SELECT estado FROM asistencia_registros WHERE id_clase='.(int)$claseDemo)->fetchColumn()==='AUSENTE', 'Estado de asistencia queda persistido');
denegado(function() use($materiaDemo,$cursoMM,$ids) {
    ModeloAsistencias::mdlCrearClase($materiaDemo,$cursoMM,'2026-09-14','Curso cruzado',$ids['B']);
}, 'Asistencia rechaza combinación de materia y curso de instituciones diferentes');
$pdo->prepare('INSERT INTO asistencia_clases(id_seccion,id_curso,fechaClase,tema,creadaPor) VALUES(?,?,?,?,?)')
    ->execute([$materiaDemo,$cursoMM,'2026-09-15','Dato incoherente',$ids['B']]);
$claseIncoherente=(int)$pdo->lastInsertId();
denegado(function() use($claseIncoherente) { ModeloAsistencias::mdlClase($claseIncoherente); }, 'Asistencia histórica incoherente no se revela por ID');
verificar(array_column(ModeloAsistencias::mdlClasesSeccion($materiaDemo),'idClase')==[$claseDemo], 'Listado de asistencia excluye clases con curso cruzado');
$leccion=['nombreLeccion'=>'Lección Demo','tipoLeccion'=>'MATERIAL','contenidoLeccion'=>'Contenido privado',
    'estadoLeccion'=>'PUBLICADA','fechaPublicacionLeccion'=>null,'id_modulo'=>$materiaDemo];
verificar(ModeloLecciones::mdlGuardarLeccion('lecciones',$leccion)==='ok', 'Lección hereda institución desde materia');
$leccionDemo=(int)$pdo->lastInsertId();
verificar(ModeloLecciones::mdlBuscarLeccionPorId($leccionDemo)['nombreLeccion']==='Lección Demo', 'Lectura de lección propia permitida');
ModeloLecciones::mdlGuardarRecursoLeccion('recursoslecciones',['id_leccion'=>$leccionDemo,'tipoRecurso'=>'ENLACE',
    'tituloRecurso'=>'Recurso Demo','urlRecurso'=>'https://example.invalid','creadoPor'=>$ids['B']]);
$recursoDemo=(int)$pdo->lastInsertId();
$entrega=['id_leccion'=>$leccionDemo,'id_seccion'=>$materiaDemo,'id_curso'=>$cursoDemo,
    'id_estudiante'=>$ids['A'],'urlArchivo'=>'','comentarioEntrega'=>'Ensayo','fechaEntrega'=>'2026-09-13 12:00:00','estadoEntrega'=>'ENTREGADA'];
$entregaCruzada=$entrega; $entregaCruzada['id_curso']=$cursoMM;
denegado(function() use($entregaCruzada) { ModeloLecciones::mdlGuardarEntregaLeccion($entregaCruzada); }, 'Entrega rechaza combinación de lección y curso de instituciones diferentes');
$pdo->prepare("UPDATE asignacioncursos SET estadoInscripcion='BAJA' WHERE id_estudiante=? AND id_seccion=?")->execute([$ids['A'],$cursoDemo]);
denegado(function() use($entrega) { ModeloLecciones::mdlGuardarEntregaLeccion($entrega); }, 'Entrega rechaza inscripción dada de baja aunque conserve membresía');
$pdo->prepare("UPDATE asignacioncursos SET estadoInscripcion='ACTIVA' WHERE id_estudiante=? AND id_seccion=?")->execute([$ids['A'],$cursoDemo]);
verificar(ModeloLecciones::mdlGuardarEntregaLeccion($entrega)==='ok', 'Entrega coherente e inscripta se guarda');
$entregaId=(int)$pdo->lastInsertId();
verificar(count(ModeloLecciones::mdlBuscarEntregasPorLeccion($leccionDemo))===1, 'Listado devuelve entrega coherente');
$notaDemo=['id_estudiante'=>$ids['A'],'id_seccion'=>$materiaDemo,'id_modulo'=>$leccionDemo,
    'id_curso'=>$cursoDemo,'calificacion'=>8,'devolucion'=>'Bien'];
verificar(ModeloCalificaciones::mdlGuardarCalificacion($notaDemo)==='ok', 'Calificación de tarea coherente se guarda en la institución activa');
verificar(count(ModeloCalificaciones::mdlCalificacionesPorSeccion($materiaDemo))===1, 'Listado de calificaciones devuelve la nota institucional propia');
$notaCruzada=$notaDemo; $notaCruzada['id_curso']=$cursoMM;
denegado(function() use($notaCruzada) { ModeloCalificaciones::mdlGuardarCalificacion($notaCruzada); }, 'Calificación rechaza combinación cruzada de curso y materia');
$notaAjena=$notaDemo; $notaAjena['id_estudiante']=$ids['C'];
denegado(function() use($notaAjena) { ModeloCalificaciones::mdlGuardarCalificacion($notaAjena); }, 'Calificación rechaza estudiante sin membresía en la institución');
$pdo->prepare('INSERT INTO calificaciones(id_estudiante,id_seccion,id_modulo,id_curso,calificacion,devolucion) VALUES(?,?,?,?,?,?)')
    ->execute([$ids['C'],$materiaDemo,$leccionDemo,$cursoMM,10,'Dato incoherente']);
verificar(count(ModeloCalificaciones::mdlCalificacionesPorSeccion($materiaDemo))===1, 'Listado excluye calificación histórica con curso cruzado');
$pdo->prepare('UPDATE entregaslecciones SET id_curso=? WHERE idEntregaLeccion=?')->execute([$cursoMM,$entregaId]);
verificar(ModeloLecciones::mdlBuscarEntregasPorLeccion($leccionDemo)===[], 'Lectura no revela entrega con curso cruzado heredada de datos históricos');
verificar(ModeloLecciones::mdlBuscarEntregaPorLeccionEstudiante($leccionDemo,$ids['A'])===null, 'Consulta individual excluye entrega incoherente');
verificar((int)ModeloLecciones::mdlResumenSeccion($materiaDemo)['totalEntregas']===0, 'Agregado excluye entrega incoherente');
denegado(function() use($leccionDemo) { ModeloLecciones::mdlEliminarLeccion($leccionDemo); }, 'Borrado con dependencias inconsistentes no elimina antecedentes');
$pdo->prepare('UPDATE entregaslecciones SET id_curso=? WHERE idEntregaLeccion=?')->execute([$cursoDemo,$entregaId]);
verificar(count(ModeloCursos::mdlInscripcionesCurso($cursoDemo))===1, 'Consulta MVC de cursantes conserva inscriptos válidos');
$pdo->prepare('INSERT INTO actividades(tituloActividad,slug,id_autor,id_curso,id_seccion,id_institucion) VALUES (?,?,?,?,?,?)')
    ->execute(['Actividad Demo','actividad-demo',$ids['B'],$cursoDemo,$materiaDemo,$demo]);
$actividadDemo=(int)$pdo->lastInsertId();
$pdo->prepare('INSERT INTO actividades_preguntas(id_actividad,tipoPregunta,textoPregunta) VALUES (?,?,?)')->execute([$actividadDemo,'OPCION_MULTIPLE','Pregunta']);
$preguntaDemo=(int)$pdo->lastInsertId();
$pdo->prepare('INSERT INTO actividades_opciones(id_pregunta,textoOpcion) VALUES (?,?)')->execute([$preguntaDemo,'Respuesta']);
$pdo->prepare('INSERT INTO actividades(tituloActividad,slug,id_autor,id_curso,id_seccion,id_institucion) VALUES (?,?,?,?,?,?)')
    ->execute(['Actividad incoherente','actividad-incoherente',$ids['A'],$cursoDemo,$materiaDemo,$mm]);
$copia=ModeloCursos::mdlDuplicarCurso($cursoDemo,['idUsuario'=>$ids['B'],'nombreCurso'=>'Copia Demo','fechaInicioCurso'=>'2026-01-01','fechaFinCurso'=>'2026-12-31']);
verificar($copia>0 && (int)ModeloCursos::mdlBuscarCursoPorId($copia)['id_institucion']===$demo, 'Duplicación mantiene institución automáticamente');
$materiasCopia=ModeloMaterias::mdlBuscarMateriaXcurso('join-1-curso',$copia);
verificar(count($materiasCopia)===1, 'Duplicación conserva la materia');
$leccionesCopia=ModeloLecciones::mdlBuscarLeccionesPorSeccion($materiasCopia[0]['idSeccion']);
verificar(count($leccionesCopia)===1 && $leccionesCopia[0]['estadoLeccion']==='BORRADOR', 'Duplicación conserva lección en borrador');
verificar(ModeloCursos::mdlInscripcionesCurso($copia)===[], 'Duplicación no copia inscripciones');
$materiaMovida=$datosMateria; $materiaMovida['idSeccion']=$materiaDemo; $materiaMovida['id_curso']=$copia;
denegado(function() use($materiaMovida) { ModeloMaterias::mdlModificarMateria('secciones',$materiaMovida); }, 'Cambio de curso de una materia no rompe referencias de entregas');
$consulta=$pdo->prepare('SELECT * FROM actividades WHERE id_curso=?'); $consulta->execute([$copia]); $actividadesCopia=$consulta->fetchAll(PDO::FETCH_ASSOC);
verificar(count($actividadesCopia)===1 && (int)$actividadesCopia[0]['id_institucion']===$demo && $actividadesCopia[0]['estadoActividad']==='BORRADOR', 'Duplicación conserva solo actividades coherentes en borrador e institución actual');
$consulta=$pdo->prepare('SELECT COUNT(*) FROM actividades_opciones o INNER JOIN actividades_preguntas p ON p.idPregunta=o.id_pregunta WHERE p.id_actividad=?');
$consulta->execute([$actividadesCopia[0]['idActividad']]);
verificar((int)$consulta->fetchColumn()===1, 'Duplicación preserva preguntas y opciones de la actividad propia');
$marca='ensayo_mt_' . bin2hex(random_bytes(6));
$rutaArchivo='uploads/lecciones/' . $marca . '.txt';
$archivoAbsoluto=dirname(__DIR__) . '/' . $rutaArchivo;
$copiasFisicas=[];
try {
    file_put_contents($archivoAbsoluto, 'Archivo sintético de prueba');
    ModeloLecciones::mdlGuardarRecursoLeccion('recursoslecciones',['id_leccion'=>$leccionDemo,'tipoRecurso'=>'ARCHIVO',
        'tituloRecurso'=>$marca,'urlRecurso'=>$rutaArchivo,'creadoPor'=>$ids['B']]);
    $copiaArchivo=ModeloCursos::mdlDuplicarCurso($cursoDemo,['idUsuario'=>$ids['B'],'nombreCurso'=>'Copia con archivo','fechaInicioCurso'=>'2026-01-01','fechaFinCurso'=>'2026-12-31']);
    $consulta=$pdo->prepare('SELECT r.urlRecurso FROM recursoslecciones r INNER JOIN lecciones l ON l.idLeccion=r.id_leccion
        INNER JOIN secciones s ON s.idSeccion=l.id_modulo WHERE s.id_curso=? AND r.tituloRecurso=?');
    $consulta->execute([$copiaArchivo,$marca]);
    $rutaCopia=(string)$consulta->fetchColumn();
    verificar((bool)preg_match('~^uploads/lecciones/copia_[a-zA-Z0-9_]+\.txt$~D',$rutaCopia), 'Duplicación crea un archivo independiente dentro del directorio permitido');
    $copiasFisicas[]=dirname(__DIR__) . '/' . $rutaCopia;
    verificar(hash_file('sha256',$archivoAbsoluto)===hash_file('sha256',$copiasFisicas[0]), 'Duplicación preserva contenido del archivo');
    // Una atribución huérfana al mismo archivo impide copiarlo.
    $pdo->prepare('INSERT INTO recursoslecciones(id_leccion,tipoRecurso,tituloRecurso,urlRecurso,creadoPor) VALUES (0,?,?,?,?)')
        ->execute(['ARCHIVO',$marca,$rutaArchivo,$ids['A']]);
    $cantidadAntes=(int)$pdo->query('SELECT COUNT(*) FROM cursos')->fetchColumn();
    denegado(function() use($cursoDemo,$ids) { ModeloCursos::mdlDuplicarCurso($cursoDemo,['idUsuario'=>$ids['B']]); }, 'Archivo con atribución ambigua bloquea duplicación antes de escribir');
    verificar((int)$pdo->query('SELECT COUNT(*) FROM cursos')->fetchColumn()===$cantidadAntes, 'Preflight de archivos no deja un curso parcial');
} finally {
    // Solo archivos creados por este ensayo, nunca directorios ni adjuntos del usuario.
    foreach (array_merge([$archivoAbsoluto],$copiasFisicas) as $archivoPrueba) {
        if (is_file($archivoPrueba)) { unlink($archivoPrueba); }
    }
}
// Simula revocación entre preparación y ejecución de SQL.
$preparada=$pdo->prepare('SELECT idCurso FROM cursos c WHERE ' . ModeloTenant::cursos());
$pdo->prepare('UPDATE usuarios_instituciones SET activo=0 WHERE id_usuario=? AND id_institucion=?')->execute([$ids['B'],$demo]);
$preparada->execute();
verificar($preparada->fetchAll(PDO::FETCH_ASSOC)===[], 'SQL ya preparado respeta revocación posterior de membresía');
$pdo->prepare('UPDATE usuarios_instituciones SET activo=1 WHERE id_usuario=? AND id_institucion=?')->execute([$ids['B'],$demo]);
sesionPara($ids['A']);
ControladorInstitucion::seleccionar($mm,ControladorInstitucion::csrf(),ControladorInstitucion::version());
verificar(ModeloMaterias::mdlBuscarMateriaPorId($materiaDemo)===false, 'Docente no puede leer materia ajena por ID');
verificar(ModeloMaterias::mdlListarMateriasGestion()===[], 'Listado de materias excluye otras instituciones');
denegado(function() use($leccionDemo) { ModeloLecciones::mdlBuscarLeccionPorId($leccionDemo); }, 'Lección ajena denegada por ID');
denegado(function() use($recursoDemo) { ModeloLecciones::mdlBuscarRecursoPorId($recursoDemo); }, 'Recurso ajeno denegado por ID');
denegado(function() use($leccionDemo) { ModeloLecciones::mdlEliminarLeccion($leccionDemo); }, 'Borrado de lección ajena rechazado antes de eliminar dependencias');
denegado(function() use($claseDemo) { ModeloAsistencias::mdlClase($claseDemo); }, 'Clase de asistencia ajena no se revela por ID');
denegado(function() use($materiaDemo) { ModeloAsistencias::mdlClasesSeccion($materiaDemo); }, 'Listado de asistencia ajeno se rechaza por materia');
denegado(function() use($materiaDemo) { ModeloCalificaciones::mdlCalificacionesPorSeccion($materiaDemo); }, 'Calificaciones de otra institución no se revelan por materia');
denegado(function() use($cursoDemo,$ids) { ModeloCursos::mdlDuplicarCurso($cursoDemo,['idUsuario'=>$ids['A']]); }, 'Duplicación de curso ajeno rechazada antes de crear datos');
$datosMateria['idSeccion']=$materiaDemo;
denegado(function() use($datosMateria) { ModeloMaterias::mdlModificarMateria('secciones',$datosMateria); }, 'Docente no puede modificar materia de otra institución');
denegado(function() use($materiaDemo) { ModeloMaterias::mdlEliminarMateria($materiaDemo); }, 'Docente no puede eliminar materia de otra institución');
$datosMateria['id_curso']=$cursoMM;
denegado(function() use($datosMateria) { ModeloMaterias::mdlGuardarMateria('secciones',$datosMateria); }, 'Materia rechaza docente sin membresía institucional');
require_once __DIR__ . '/../controladores/cursos.controller.php';
require_once __DIR__ . '/../controladores/lecciones.controller.php';
require_once __DIR__ . '/../controladores/asistencias.controller.php';
require_once __DIR__ . '/../controladores/calificaciones.controller.php';
denegado(function() use($cursoDemo) { ControladorCursos::crtPuedeGestionarCurso($cursoDemo); }, 'Controlador no concede gestión de curso ajeno');
denegado(function() use($materiaDemo) { ControladorAsistencias::crtPuedeGestionar($materiaDemo); }, 'Controlador no concede gestión de asistencia ajena a un administrador institucional');
denegado(function() use($materiaDemo) { ControladorCalificaciones::crtCalificacionesPorSeccion($materiaDemo); }, 'Controlador no revela calificaciones de otra institución');
$_POST=['accion_curso'=>'duplicar_curso','idCurso'=>$cursoDemo];
denegado(function() { ControladorCursos::crtDuplicarCurso(); }, 'POST de duplicación manipulado rechazado por controlador');
$_POST=[];
ControladorInstitucion::limpiar();
denegado(function() { ModeloCursos::mdlListarCursos(); }, 'Ausencia de contexto seleccionado rechaza listado');
echo "Ensayo de recursos conservado: $base\n";
ob_end_flush();
