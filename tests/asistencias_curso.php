<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
ob_start();
require __DIR__ . '/multi_institucion_contexto.php';
require_once __DIR__ . '/../controladores/asistencias.controller.php';
require_once __DIR__ . '/../modelos/cursos.modelo.php';
foreach (['secciones','asignacioncursos','asistencia_clases','asistencia_registros'] as $tabla) {
    $pdo->exec("CREATE TABLE `$tabla` LIKE `$origen`.`$tabla`");
}
sesionPara($ids['B']);
$pdo->prepare("INSERT INTO cursos(id_institucion,nombreCurso,contenidoCurso,estado,fechaInicioCurso,fechaFinCurso,horarioCurso,creadoPor,responsable)
    VALUES (?,'Curso Demo','Prueba','ACTIVO','2026-01-01','2026-12-31','Lunes',?,?)")->execute([$demo,$ids['B'],$ids['B']]);
$cursoDemo=(int)$pdo->lastInsertId();
$pdo->prepare("INSERT INTO cursos(id_institucion,nombreCurso,contenidoCurso,estado,fechaInicioCurso,fechaFinCurso,horarioCurso,creadoPor,responsable)
    VALUES (?,'Curso MenteMotion','Prueba','ACTIVO','2026-01-01','2026-12-31','Lunes',?,?)")->execute([$mm,$ids['A'],$ids['A']]);
$cursoMM=(int)$pdo->lastInsertId();
$pdo->prepare("INSERT INTO secciones(tituloSeccion,contenidoSeccion,id_curso,docente,tutor,creadoPor)
    VALUES ('Materia MenteMotion','Prueba',?,?,0,?)")->execute([$cursoMM,$ids['A'],$ids['A']]);
$materiaMM=(int)$pdo->lastInsertId();
ModeloCursos::mdlAsignarCurso('asignacioncursos',['idCurso'=>$cursoDemo,'idUsuario'=>$ids['A']]);
ejecutarMigracion($pdo, __DIR__ . '/../sql/2026-09-21_asistencias_curso.sql');
ejecutarMigracion($pdo, __DIR__ . '/../sql/2026-09-21_asistencias_curso.sql');
sesionPara($ids['B']);
$pdo->prepare("UPDATE asignacioncursos SET estadoInscripcion='ACTIVA',fechaAlta='2026-01-01',fechaBaja=NULL WHERE id_seccion=? AND id_estudiante=?")->execute([$cursoDemo,$ids['A']]);
$historialAntes = (int)$pdo->query('SELECT COUNT(*) FROM asistencia_registros')->fetchColumn();
$claseCurso = ModeloAsistenciasCurso::crearClase($cursoDemo,'2026-09-21','Curso completo',$ids['B']);
verificar(count(ModeloAsistenciasCurso::registros($claseCurso,$cursoDemo))===1, 'Curso incluye al estudiante una sola vez');
ModeloAsistenciasCurso::guardar($claseCurso,$cursoDemo,[$ids['A']=>'AUSENTE'],[$ids['A']=>'Avisó'],$ids['B']);
verificar(ModeloAsistenciasCurso::crearClase($cursoDemo,'2026-09-21','Otro tema',$ids['B'])===$claseCurso, 'Fecha repetida reabre la misma planilla del curso');
$registro = ModeloAsistenciasCurso::registros($claseCurso,$cursoDemo)[0];
verificar($registro['estado']==='AUSENTE' && $registro['observacion']==='Avisó' && ModeloAsistenciasCurso::clase($claseCurso,$cursoDemo)['tema']==='Curso completo', 'Reabrir conserva estado, observación y tema');
verificar((int)ModeloAsistenciasCurso::resumenCurso($cursoDemo)[0]['ausentes']===1, 'Contador del curso no duplica ausencias');
verificar((int)ModeloAsistenciasCurso::resumenEstudiante($ids['A'])[0]['clases']===1, 'Resumen del estudiante agrupa por curso');
verificar((int)$pdo->query('SELECT COUNT(*) FROM asistencia_registros')->fetchColumn()===$historialAntes, 'Historial por materia permanece intacto');
verificar(ModeloAsistenciasCurso::clase($claseCurso,$cursoMM)===null, 'Clase no puede abrirse bajo otro curso');
try { ModeloAsistenciasCurso::guardar($claseCurso,$cursoMM,[$ids['A']=>'PRESENTE'],[],$ids['B']); throw new LogicException('Aceptó curso ajeno'); }
catch (RuntimeException $e) { verificar(true, 'Guardado de curso ajeno rechazado'); }
$pdo->prepare("UPDATE asignacioncursos SET estadoInscripcion='BAJA',fechaBaja='2026-09-22' WHERE id_seccion=? AND id_estudiante=?")->execute([$cursoDemo,$ids['A']]);
$previa = ModeloAsistenciasCurso::crearClase($cursoDemo,'2026-09-20','Anterior a baja',$ids['B']);
$posterior = ModeloAsistenciasCurso::crearClase($cursoDemo,'2026-09-23','Posterior a baja',$ids['B']);
verificar(count(ModeloAsistenciasCurso::registros($previa,$cursoDemo))===1 && count(ModeloAsistenciasCurso::registros($posterior,$cursoDemo))===0, 'Baja respeta la fecha histórica y excluye fechas posteriores');
sesionPara($ids['A']);
ControladorInstitucion::seleccionar($mm,ControladorInstitucion::csrf(),ControladorInstitucion::version());
verificar(!ModeloAsistenciasCurso::puedeGestionar($cursoDemo,$ids['A'],false), 'Docente de otra institución no accede al curso');
verificar(ModeloAsistenciasCurso::puedeGestionar($cursoMM,$ids['A'],false), 'Responsable puede gestionar su curso');
verificar(ModeloAsistenciasCurso::clase($claseCurso,$cursoDemo)===null, 'Cambio de institución oculta planilla anterior');
$pdo->prepare('UPDATE cursos SET responsable=0 WHERE idCurso=?')->execute([$cursoMM]);
verificar(ModeloAsistenciasCurso::puedeGestionar($cursoMM,$ids['A'],false), 'Docente de una materia puede tomar asistencia del curso');
$pdo->prepare('UPDATE secciones SET docente=0,tutor=0 WHERE id_curso=?')->execute([$cursoMM]);
verificar(!ModeloAsistenciasCurso::puedeGestionar($cursoMM,$ids['A'],false), 'Docente no asignado pierde acceso a la planilla');
$pdo->prepare('UPDATE secciones SET tutor=? WHERE idSeccion=?')->execute([$ids['A'],$materiaMM]);
verificar(ModeloAsistenciasCurso::puedeGestionar($cursoMM,$ids['A'],false), 'Tutor de una materia puede tomar asistencia del curso');
sesionPara($ids['B']);
$_GET=['idCurso'=>$cursoDemo];
$_POST=['accion_asistencia'=>'crear_clase','id_curso'=>$cursoDemo,'fechaClase'=>'2026-09-25','csrf_asistencia'=>'incorrecto'];
verificar(ControladorAsistencias::crtProcesarCurso()===0, 'POST sin token válido no crea una clase');
$_POST['csrf_asistencia']=ControladorAsistencias::csrfCurso();
$_POST['fechaClase']='2026-02-30';
verificar(ControladorAsistencias::crtProcesarCurso()===0, 'Fecha inexistente no crea una clase');
$_POST['fechaClase']='2026-09-25';
verificar(ControladorAsistencias::crtProcesarCurso()>0, 'POST válido crea una fecha del curso');
$_POST=[];
// La página real utiliza tablas auxiliares del encabezado; copiar solo estructura.
foreach ($pdo->query("SHOW TABLES FROM `$origen`")->fetchAll(PDO::FETCH_COLUMN) as $tabla) {
    if (!preg_match('/^[a-zA-Z0-9_]+$/D', $tabla)) { continue; }
    $pdo->exec("CREATE TABLE IF NOT EXISTS `$tabla` LIKE `$origen`.`$tabla`");
}
[$servidorCurso,$urlCurso,$sesionesCurso]=levantarServidor('LOCAL');
$curlCurso=curl_init();
try {
    $r=peticion($curlCurso,$urlCurso.'?r=login',['login_email'=>'b@campus.example','login_pass'=>$password]);
    verificar($r['codigo']===303, 'HTTP: administrador inicia sesión en base sintética');
    $r=peticion($curlCurso,$urlCurso.'?r=asistencias');
    verificar($r['codigo']===200 && str_contains($r['body'],'Asistencia por curso') && str_contains($r['body'],'Curso Demo') && !str_contains($r['body'],'Curso MenteMotion'), 'HTTP: listado de cursos respeta institución');
    $r=peticion($curlCurso,$urlCurso.'?r=asistencia-curso&idCurso='.$cursoDemo);
    verificar($r['codigo']===200 && str_contains($r['body'],'csrf_asistencia'), 'HTTP: planilla del curso renderiza formulario');
    preg_match('/name="csrf_asistencia" value="([^"]+)"/', $r['body'], $token);
    $post=['accion_asistencia'=>'crear_clase','id_curso'=>$cursoDemo,'fechaClase'=>'2026-09-26','tema'=>'Clase HTTP','csrf_asistencia'=>$token[1]??''];
    $r=peticion($curlCurso,$urlCurso.'?r=asistencia-curso&idCurso='.$cursoDemo,$post);
    verificar($r['codigo']===302 && str_contains($r['destino'],'idClase='), 'HTTP: creación redirige antes de emitir HTML');
    $r=peticion($curlCurso,$urlCurso.'?r=asistencia-curso&idCurso='.$cursoMM,$post);
    verificar($r['codigo']===403, 'HTTP: POST hacia curso ajeno se rechaza');
    $post=['accion_asistencia'=>'guardar_asistencia','id_curso'=>$cursoDemo,'id_clase'=>$claseCurso,'estados'=>[$ids['A']=>'TARDANZA'],'csrf_asistencia'=>$token[1]??''];
    $r=peticion($curlCurso,$urlCurso.'?r=asistencia-curso&idCurso='.$cursoDemo,$post);
    verificar($r['codigo']===302 && ModeloAsistenciasCurso::registros($claseCurso,$cursoDemo)[0]['estado']==='TARDANZA', 'HTTP: planilla guarda estado del estudiante');
    $post['id_curso']=$cursoMM;
    $r=peticion($curlCurso,$urlCurso.'?r=asistencia-curso&idCurso='.$cursoDemo,$post);
    verificar($r['codigo']===403, 'HTTP: ID oculto de otro curso se rechaza');
} finally {
    curl_close($curlCurso);
    proc_terminate($servidorCurso);
    proc_close($servidorCurso);
}
echo "Asistencia por curso verificada en base sintética: $base\n";
ob_end_flush();
