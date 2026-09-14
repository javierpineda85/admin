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
require_once __DIR__ . '/../modelos/actividades.modelo.php';
require_once __DIR__ . '/../modelos/mensajes.modelo.php';
require_once __DIR__ . '/../modelos/notificaciones.modelo.php';
require_once __DIR__ . '/../modelos/panel.modelo.php';
require_once __DIR__ . '/../controladores/descargas.controller.php';
require_once __DIR__ . '/../controladores/usuarios.controller.php';
require_once __DIR__ . '/../controladores/perfiles.controller.php';
foreach (['secciones', 'lecciones', 'asignacioncursos', 'recursoslecciones', 'entregaslecciones', 'posteos','usuarios_historial',
    'calificaciones','entregaslecciones_adjuntos','archivoslecciones','actividades','actividades_preguntas','actividades_opciones',
    'asistencia_clases','asistencia_registros','ciclos_lectivos','periodos_calificacion','instrumentos_evaluacion',
    'periodos_seccion_estado','cierres_periodo_calificaciones','evaluaciones','evaluaciones_calificaciones',
    'actividades_intentos','actividades_respuestas','mensajes','mensajes_participantes','mensajes_adjuntos',
    'notificaciones','notificaciones_lecturas'] as $tabla) {
    $pdo->exec("CREATE TABLE `$tabla` LIKE `$origen`.`$tabla`");
}
ejecutarMigracion($pdo, __DIR__ . '/../sql/2026-09-14_multi_institucion_01_expandir.sql');
ejecutarMigracion($pdo, __DIR__ . '/../sql/2026-09-14_multi_institucion_03_catalogos_calificacion.sql');
ejecutarMigracion($pdo, __DIR__ . '/../sql/2026-09-14_multi_institucion_03_catalogos_calificacion.sql');
verificar((int)$pdo->query("SELECT COUNT(*) FROM campus_migraciones WHERE codigo='multi_institucion_03_catalogos_calificacion'")->fetchColumn()===1, 'Migración de catálogos de calificación es reanudable e idempotente');
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
$datosMateriaMM=['tituloSeccion'=>'Materia MenteMotion','contenidoSeccion'=>'Prueba','id_curso'=>$cursoMM,
    'docente'=>$ids['A'],'tutor'=>0,'bannerSeccion'=>'','colorInicioBanner'=>'#000000','colorFinBanner'=>'#ffffff','creadoPor'=>$ids['A']];
verificar(ModeloMaterias::mdlGuardarMateria('secciones',$datosMateriaMM)==='ok', 'Materia de MenteMotion queda vinculada a su curso');
$materiaMM=(int)$pdo->lastInsertId();
$contextoMM=ModeloCalificaciones::mdlContextoAcademicoSeccion($materiaMM);
verificar(!empty($contextoMM['periodos'])&&!empty($contextoMM['instrumentos']), 'MenteMotion obtiene catálogos de calificación institucionales');
$evaluacionMM=ModeloCalificaciones::mdlCrearEvaluacion(['id_seccion'=>$materiaMM,'id_curso'=>$cursoMM,
    'id_periodo'=>$contextoMM['periodos'][0]['idPeriodo'],'id_instrumento'=>$contextoMM['instrumentos'][0]['idInstrumento'],
    'id_autor'=>$ids['A'],'temaEvaluacion'=>'Evaluación MenteMotion','fechaEvaluacion'=>'2026-09-20']);
verificar($evaluacionMM>0, 'Evaluación se crea dentro del catálogo de MenteMotion');
sesionPara($ids['B']);
$datosCurso['nombreCurso']='Curso Demo'; $datosCurso['creadoPor']=$ids['B']; $datosCurso['responsable']=$ids['B'];
ModeloCursos::mdlGuardarCurso('cursos',$datosCurso); $cursoDemo=(int)$pdo->lastInsertId();
verificar(ModeloCursos::mdlActualizarResponsableCurso($cursoDemo,$ids['B'])==='ok', 'Escritura de curso propio aplica filtro institucional');
verificar(array_column(ModeloCursos::mdlListarCursos(),'idCurso')==[$cursoDemo], 'Listado de cursos de Demo excluye MenteMotion');
$miembrosDemo=ModeloUsuarios::mdlSeleccionarUsuarios('activo',1);
verificar(!in_array($ids['C'],array_column($miembrosDemo,'idUsuario')), 'Administrador de Demo no lista usuarios exclusivos de MenteMotion');
verificar(!array_key_exists('pass',$miembrosDemo[0]), 'Listado institucional no expone hashes de identidad global');
verificar(array_column(ModeloUsuarios::mdlSeleccionarUsuarios('rol','ESTUDIANTE'),'idUsuario')==[$ids['A']], 'Filtro de estudiantes usa membresías y no rol global');
verificar(ModeloUsuarios::mdlObtenerUsuarioCompleto($ids['C'])===false, 'Detalle de usuario ajeno no se revela por ID');
$detalleUsuarioB=ModeloUsuarios::mdlObtenerUsuarioCompleto($ids['B']);
verificar($detalleUsuarioB!==false && !array_key_exists('pass',$detalleUsuarioB)
    && str_contains((string)$detalleUsuarioB['rol'],'ADMINISTRADOR'), 'Detalle propio usa estado y roles de la membresía sin exponer contraseña');
$pdo->prepare('UPDATE usuarios SET ultimaConexion=NULL WHERE idUsuario IN (?,?,?)')->execute([$ids['A'],$ids['B'],$ids['C']]);
$pdo->prepare('UPDATE usuarios SET ultimaConexion=NOW() WHERE idUsuario IN (?,?)')->execute([$ids['B'],$ids['C']]);
verificar(array_map('intval',array_column(ModeloUsuarios::mdlUsuariosConectadosRecientes(60),'idUsuario'))===[$ids['B']], 'Listado de conectados excluye usuarios de otras instituciones');
verificar(array_map('intval',array_column(ModeloUsuarios::mdlUsuariosNoConectadosRecientes(60),'idUsuario'))===[$ids['A']], 'Listado de no conectados queda limitado a la membresía institucional');
verificar(ModeloUsuarios::mdlContarUsuariosConectadosRecientes(60)===1, 'Contador de conectados usa el tenant activo');
$perfilCAntes=(int)$pdo->query('SELECT COUNT(*) FROM perfiles WHERE id_usuario='.(int)$ids['C'])->fetchColumn();
$_POST=['id_usuario'=>$ids['C'],'contenidoPerfil'=>'Intento cruzado'];
verificar(ControladorPerfiles::crtEditarPerfil()===false
    && (int)$pdo->query('SELECT COUNT(*) FROM perfiles WHERE id_usuario='.(int)$ids['C'])->fetchColumn()===$perfilCAntes,
    'Edición de perfil ignora un ID de otro usuario enviado por POST');
$_POST=['id_usuario'=>$ids['B'],'contenidoPerfil'=>'Perfil propio'];
verificar(ControladorPerfiles::crtEditarPerfil()==='ok'
    && (string)$pdo->query('SELECT contenidoPerfil FROM perfiles WHERE id_usuario='.(int)$ids['B'])->fetchColumn()==='Perfil propio',
    'Usuario autenticado conserva la edición de su propio perfil global');
$_POST=['idUsuario'=>$ids['C'],'nombreUsuario'=>'Alterado','apellidoUsuario'=>'Ajeno','emailUsuario'=>'ajeno@campus.example','rol'=>'ADMINISTRADOR'];
verificar(ControladorUsuarios::crtModificarUsuario()===false, 'Administrador institucional no modifica por POST un usuario de otra institución');
$_POST=['accion_usuario'=>'baja_usuario','idUsuario'=>$ids['C'],'motivoBaja'=>'Intento cruzado'];
verificar(ControladorUsuarios::crtDarBajaUsuario()===false && (int)$pdo->query('SELECT activo FROM usuarios WHERE idUsuario='.(int)$ids['C'])->fetchColumn()===1,
    'Administrador institucional no da de baja una identidad de otra institución');
verificar(ControladorUsuarios::crtReactivarUsuario($ids['C'])===false, 'Administrador institucional no reactiva una identidad de otra institución');
$_POST=[];
$usuariosAntesAlta=(int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
$_POST=['nombreUsuario'=>'Nombre ignorado','apellidoUsuario'=>'Existente','emailUsuario'=>'d@campus.example','passUsuario'=>'',
    'roles'=>['ESTUDIANTE']];
verificar(ControladorUsuarios::crtGuardarUsuario()==='ok'
    && (int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn()===$usuariosAntesAlta
    && ModeloInstituciones::mdlRolesUsuarioInstitucion($ids['D'],$demo)===['ESTUDIANTE'],
    'Alta institucional reutiliza la identidad global existente sin duplicarla');
verificar(ControladorUsuarios::crtGuardarUsuario()===false
    && ModeloInstituciones::mdlRolesUsuarioInstitucion($ids['D'],$demo)===['ESTUDIANTE'],
    'Alta repetida no duplica ni reemplaza una membresía institucional activa');
$emailNuevo='nuevo.'.bin2hex(random_bytes(4)).'@campus.example';
$_POST=['nombreUsuario'=>'Elena','apellidoUsuario'=>'Nueva','emailUsuario'=>$emailNuevo,'passUsuario'=>'corta',
    'roles'=>['DOCENTE']];
verificar(ControladorUsuarios::crtGuardarUsuario()===false
    && (int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn()===$usuariosAntesAlta,
    'Identidad global nueva exige una contraseña inicial suficiente');
$_POST=['nombreUsuario'=>'Elena','apellidoUsuario'=>'Nueva','emailUsuario'=>$emailNuevo,'passUsuario'=>'Clave-segura-123',
    'roles'=>['DOCENTE','ESTUDIANTE'],'dniPerfil'=>'30111222','contenidoPerfil'=>'Perfil inicial'];
verificar(ControladorUsuarios::crtGuardarUsuario()==='ok', 'Alta institucional crea una identidad global cuando el email no existe');
$idUsuarioNuevo=(int)$pdo->query("SELECT idUsuario FROM usuarios WHERE email=".$pdo->quote($emailNuevo))->fetchColumn();
$identidadNueva=$pdo->query('SELECT nombreUsuario,email,pass,rol,activo FROM usuarios WHERE idUsuario='.(int)$idUsuarioNuevo)->fetch(PDO::FETCH_ASSOC);
verificar($idUsuarioNuevo>0 && (string)$identidadNueva['rol']==='' && (int)$identidadNueva['activo']===1
    && ModeloInstituciones::mdlRolesUsuarioInstitucion($idUsuarioNuevo,$demo)===['DOCENTE','ESTUDIANTE'],
    'Identidad nueva conserva roles académicos exclusivamente en su membresía');
$hashNuevo=(string)$identidadNueva['pass'];
$_POST=['idUsuario'=>$idUsuarioNuevo,'nombreUsuario'=>'Nombre manipulado','apellidoUsuario'=>'Cambio global',
    'emailUsuario'=>'cambio@campus.example','passUsuario'=>'Otra-clave-123','roles'=>['ADMINISTRADOR','DOCENTE']];
verificar(ControladorUsuarios::crtModificarUsuario()==='ok', 'Administrador actualiza múltiples roles de una membresía propia');
$identidadTrasRoles=$pdo->query('SELECT nombreUsuario,email,pass FROM usuarios WHERE idUsuario='.(int)$idUsuarioNuevo)->fetch(PDO::FETCH_ASSOC);
verificar($identidadTrasRoles['nombreUsuario']==='Elena' && $identidadTrasRoles['email']===$emailNuevo
    && $identidadTrasRoles['pass']===$hashNuevo
    && ModeloInstituciones::mdlRolesUsuarioInstitucion($idUsuarioNuevo,$demo)===['ADMINISTRADOR','DOCENTE'],
    'Cambio institucional no modifica nombre, email ni contraseña de la identidad global');
$pdo->prepare('INSERT INTO usuarios_instituciones(id_usuario,id_institucion) VALUES(?,?)')->execute([$idUsuarioNuevo,$mm]);
$membresiaNuevaMM=(int)$pdo->lastInsertId();
$pdo->prepare("INSERT INTO usuarios_instituciones_roles(id_usuario_institucion,id_rol) SELECT ?,idRol FROM roles WHERE codigo='ESTUDIANTE'")
    ->execute([$membresiaNuevaMM]);
$fechaAltaMembresiaDemo=(string)$pdo->query('SELECT fechaAlta FROM usuarios_instituciones WHERE id_usuario='.(int)$idUsuarioNuevo.' AND id_institucion='.(int)$demo)->fetchColumn();
$_POST=['accion_usuario'=>'baja_usuario','idUsuario'=>$idUsuarioNuevo,'motivoBaja'=>'Baja sólo Demo'];
verificar(ControladorUsuarios::crtDarBajaUsuario()==='ok'
    && (int)$pdo->query('SELECT activo FROM usuarios_instituciones WHERE id_usuario='.(int)$idUsuarioNuevo.' AND id_institucion='.(int)$demo)->fetchColumn()===0
    && (int)$pdo->query('SELECT activo FROM usuarios_instituciones WHERE id_usuario='.(int)$idUsuarioNuevo.' AND id_institucion='.(int)$mm)->fetchColumn()===1
    && (int)$pdo->query('SELECT activo FROM usuarios WHERE idUsuario='.(int)$idUsuarioNuevo)->fetchColumn()===1,
    'Baja institucional desactiva sólo la membresía seleccionada');
verificar(ControladorUsuarios::crtReactivarUsuario($idUsuarioNuevo)==='ok'
    && ModeloInstituciones::mdlRolesUsuarioInstitucion($idUsuarioNuevo,$demo)===['ADMINISTRADOR','DOCENTE']
    && (string)$pdo->query('SELECT fechaAlta FROM usuarios_instituciones WHERE id_usuario='.(int)$idUsuarioNuevo.' AND id_institucion='.(int)$demo)->fetchColumn()===$fechaAltaMembresiaDemo,
    'Reactivación institucional conserva la identidad, fecha de alta y roles de la membresía');
verificar((int)$pdo->query('SELECT COUNT(*) FROM usuarios_historial WHERE id_usuario='.(int)$idUsuarioNuevo.' AND id_institucion='.(int)$demo)->fetchColumn()>=4,
    'Historial de altas, roles y estado registra la institución activa');
sesionPara($ids['A']); ControladorInstitucion::seleccionar($demo,ControladorInstitucion::csrf(),ControladorInstitucion::version());
denegado(function() use($idUsuarioNuevo) { ModeloUsuarios::mdlActualizarRolesInstitucionales($idUsuarioNuevo,['ESTUDIANTE']); },
    'Modelo de membresías exige rol administrador para cambiar roles');
sesionPara($ids['B']); ControladorInstitucion::seleccionar($demo,ControladorInstitucion::csrf(),ControladorInstitucion::version());
$_POST=[];
verificar(ModeloCursos::mdlBuscarCursoPorId($cursoMM)===null, 'Lectura directa por ID de curso ajeno no devuelve datos');
denegado(function() use ($cursoMM) { ModeloCursos::mdlEliminarCurso($cursoMM); }, 'B no puede eliminar curso de MenteMotion');
denegado(function() use ($cursoDemo,$ids) { ModeloCursos::mdlAsignarCurso('asignacioncursos',['idCurso'=>$cursoDemo,'idUsuario'=>$ids['C']]); }, 'Inscripción rechaza estudiante de otra institución');
verificar(ModeloCursos::mdlAsignarCurso('asignacioncursos',['idCurso'=>$cursoDemo,'idUsuario'=>$ids['A']])==='ok', 'Inscripción usa rol institucional de A en Demo');
$datosMateria=['tituloSeccion'=>'Materia Demo','contenidoSeccion'=>'Prueba','id_curso'=>$cursoDemo,
    'docente'=>$ids['B'],'tutor'=>0,'bannerSeccion'=>'','colorInicioBanner'=>'#000000','colorFinBanner'=>'#ffffff','creadoPor'=>$ids['B']];
verificar(ModeloMaterias::mdlGuardarMateria('secciones',$datosMateria)==='ok', 'Materia se crea con docente de la institución');
$materiaDemo=(int)$pdo->lastInsertId();
verificar(ModeloMaterias::mdlActualizarDocentesMateria($materiaDemo,$ids['B'],0)==='ok', 'Escritura de materia propia aplica filtro institucional');
$relacionesA=ModeloUsuarios::mdlRelacionesAcademicas($ids['A']);
verificar($relacionesA!==[] && array_values(array_unique(array_map('intval',array_column($relacionesA,'idCurso'))))===[$cursoDemo],
    'Relaciones académicas del perfil excluyen cursos de otras instituciones');
$contextoDemo=ModeloCalificaciones::mdlContextoAcademicoSeccion($materiaDemo);
verificar((int)$contextoDemo['ciclo']['idCicloLectivo']!==(int)$contextoMM['ciclo']['idCicloLectivo'], 'Cada institución usa un ciclo lectivo independiente para el mismo año');
verificar($contextoDemo['instrumentos'][0]['nombre']===$contextoMM['instrumentos'][0]['nombre']
    && (int)$contextoDemo['instrumentos'][0]['idInstrumento']!==(int)$contextoMM['instrumentos'][0]['idInstrumento'], 'Instrumentos con el mismo nombre poseen IDs institucionales diferentes');
$evaluacionDemo=ModeloCalificaciones::mdlCrearEvaluacion(['id_seccion'=>$materiaDemo,'id_curso'=>$cursoDemo,
    'id_periodo'=>$contextoDemo['periodos'][0]['idPeriodo'],'id_instrumento'=>$contextoDemo['instrumentos'][0]['idInstrumento'],
    'id_autor'=>$ids['B'],'temaEvaluacion'=>'Evaluación Demo','fechaEvaluacion'=>'2026-09-20']);
verificar($evaluacionDemo>0 && ModeloCalificaciones::mdlEvaluacionPorId($evaluacionDemo)['temaEvaluacion']==='Evaluación Demo', 'Evaluación propia puede crearse y consultarse');
verificar(ModeloCalificaciones::mdlGuardarCalificacionesEvaluacion($evaluacionDemo,[['id_estudiante'=>$ids['A'],'calificacion'=>9,'estadoAsistencia'=>'PRESENTE','devolucion'=>'Muy bien']])==='ok', 'Calificación de evaluación valida inscripción y tenant');
verificar(count(ModeloCalificaciones::mdlCalificacionesEvaluacion($evaluacionDemo))===1, 'Planilla de evaluación propia devuelve su calificación');
$periodoDemo=(int)$contextoDemo['periodos'][0]['idPeriodo'];
verificar(ModeloCalificaciones::mdlCalcularCierresPeriodo($periodoDemo,$materiaDemo,$ids['B'])===1, 'Cierre calcula promedios solo para estudiantes válidos del tenant');
$cierresDemo=ModeloCalificaciones::mdlCierresPeriodo($periodoDemo,$materiaDemo);
verificar(count($cierresDemo)===1&&(int)$cierresDemo[0]['id_estudiante']===$ids['A'], 'Listado de cierres conserva únicamente estudiantes institucionales');
verificar(ModeloCalificaciones::mdlGuardarCierresPeriodo($periodoDemo,$materiaDemo,[$ids['A']=>9],$ids['B'])==='ok', 'Cierre institucional puede confirmarse');
verificar(ModeloCalificaciones::mdlCambiarEstadoPeriodo($periodoDemo,$materiaDemo,'CERRADO',$ids['B'])==='ok'
    && ModeloCalificaciones::mdlPeriodoPorId($periodoDemo,$materiaDemo)['estado']==='CERRADO', 'Estado del período se guarda por materia e institución');
verificar(count(ModeloCalificaciones::mdlResumenCierresGenerales())===1, 'Resumen general de cierres queda limitado al tenant activo');
verificar(count(ModeloCalificaciones::mdlCalificacionesGenerales())===2, 'Historial general combina solo nota de evaluación y cierre propios antes de las tareas');
verificar(array_column(ModeloCalificaciones::mdlSeccionesParaCalificaciones(),'idSeccion')===[$materiaDemo], 'Tarjetas de calificaciones muestran solo materias del tenant activo');
verificar(ModeloCalificaciones::mdlEstudiantesIntensificacion($cursoDemo,$materiaDemo)===[], 'Intensificación consulta cierres sin salir del tenant');
denegado(function() use($contextoMM,$materiaDemo,$ids) { ModeloCalificaciones::mdlCambiarEstadoPeriodo($contextoMM['periodos'][0]['idPeriodo'],$materiaDemo,'CERRADO',$ids['B']); }, 'No se puede cerrar un período de otra institución');
$notaEvaluacionAjena=[['id_estudiante'=>$ids['C'],'calificacion'=>10,'estadoAsistencia'=>'PRESENTE','devolucion'=>'']];
denegado(function() use($evaluacionDemo,$notaEvaluacionAjena) { ModeloCalificaciones::mdlGuardarCalificacionesEvaluacion($evaluacionDemo,$notaEvaluacionAjena); }, 'Evaluación no admite calificar un usuario ajeno a la institución');
verificar(ModeloCalificaciones::mdlEvaluacionPorId($evaluacionMM)===null, 'Evaluación de otra institución no se revela por ID');
denegado(function() use($evaluacionMM,$ids) { ModeloCalificaciones::mdlGuardarCalificacionesEvaluacion($evaluacionMM,[['id_estudiante'=>$ids['A'],'calificacion'=>10]]); }, 'No se puede calificar una evaluación de otra institución');
denegado(function() use($evaluacionMM) { ModeloCalificaciones::mdlEliminarEvaluacion($evaluacionMM); }, 'Evaluación ajena no puede eliminarse por ID manipulado');
$evaluacionPeriodoCruzado=['id_seccion'=>$materiaDemo,'id_curso'=>$cursoDemo,
    'id_periodo'=>$contextoMM['periodos'][0]['idPeriodo'],'id_instrumento'=>$contextoDemo['instrumentos'][0]['idInstrumento'],
    'id_autor'=>$ids['B'],'temaEvaluacion'=>'Período cruzado','fechaEvaluacion'=>'2026-09-21'];
denegado(function() use($evaluacionPeriodoCruzado) { ModeloCalificaciones::mdlCrearEvaluacion($evaluacionPeriodoCruzado); }, 'Evaluación rechaza período de otra institución');
$fechaClaseDemo=date('Y-m-d');
$fechaClaseCruzada=date('Y-m-d',strtotime('+1 day'));
$fechaClaseIncoherente=date('Y-m-d',strtotime('+2 days'));
$claseDemo=ModeloAsistencias::mdlCrearClase($materiaDemo,$cursoDemo,$fechaClaseDemo,'Clase Demo',$ids['B']);
verificar($claseDemo>0, 'Asistencia crea una clase dentro de la institución activa');
$registrosDemo=ModeloAsistencias::mdlRegistrosClase($claseDemo);
verificar(array_column($registrosDemo,'id_estudiante')==[$ids['A']], 'Asistencia incorpora solo estudiantes activos de la institución y del curso');
verificar(ModeloAsistencias::mdlGuardar($claseDemo,[$ids['A']=>'AUSENTE'],[$ids['A']=>'Ensayo'],$ids['B'])==='ok', 'Asistencia propia se actualiza con contexto institucional');
verificar((string)$pdo->query('SELECT estado FROM asistencia_registros WHERE id_clase='.(int)$claseDemo)->fetchColumn()==='AUSENTE', 'Estado de asistencia queda persistido');
denegado(function() use($materiaDemo,$cursoMM,$ids,$fechaClaseCruzada) {
    ModeloAsistencias::mdlCrearClase($materiaDemo,$cursoMM,$fechaClaseCruzada,'Curso cruzado',$ids['B']);
}, 'Asistencia rechaza combinación de materia y curso de instituciones diferentes');
$pdo->prepare('INSERT INTO asistencia_clases(id_seccion,id_curso,fechaClase,tema,creadaPor) VALUES(?,?,?,?,?)')
    ->execute([$materiaDemo,$cursoMM,$fechaClaseIncoherente,'Dato incoherente',$ids['B']]);
$claseIncoherente=(int)$pdo->lastInsertId();
denegado(function() use($claseIncoherente) { ModeloAsistencias::mdlClase($claseIncoherente); }, 'Asistencia histórica incoherente no se revela por ID');
verificar(array_column(ModeloAsistencias::mdlClasesSeccion($materiaDemo),'idClase')==[$claseDemo], 'Listado de asistencia excluye clases con curso cruzado');
$leccion=['nombreLeccion'=>'Lección Demo','tipoLeccion'=>'MATERIAL','contenidoLeccion'=>'Contenido privado',
    'estadoLeccion'=>'PUBLICADA','fechaPublicacionLeccion'=>null,'id_modulo'=>$materiaDemo];
verificar(ModeloLecciones::mdlGuardarLeccion('lecciones',$leccion)==='ok', 'Lección hereda institución desde materia');
$leccionDemo=(int)$pdo->lastInsertId();
verificar(ModeloLecciones::mdlBuscarLeccionPorId($leccionDemo)['nombreLeccion']==='Lección Demo', 'Lectura de lección propia permitida');
verificar(ModeloLecciones::mdlGuardarPostLeccion([
    'id_autor'=>$ids['B'],'contenidoPosteo'=>'Aporte Demo','fechaPosteo'=>'2026-09-13 11:00:00',
    'id_curso'=>$cursoDemo,'id_leccion'=>$leccionDemo
])==='ok'&&count(ModeloLecciones::mdlBuscarPostsPorLeccion($leccionDemo))===1, 'Posteo se crea y lista dentro de la lección institucional');
denegado(function() use($ids,$cursoMM,$leccionDemo) { ModeloLecciones::mdlGuardarPostLeccion([
    'id_autor'=>$ids['B'],'contenidoPosteo'=>'Cruce','fechaPosteo'=>'2026-09-13 11:01:00',
    'id_curso'=>$cursoMM,'id_leccion'=>$leccionDemo
]); }, 'Posteo rechaza combinación de lección y curso de otra institución');
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
verificar(count(ModeloCalificaciones::mdlCalificacionesGenerales())===3, 'Historial general incorpora la tarea sin mezclar instituciones');
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
$actividadIncoherente=(int)$pdo->lastInsertId();
verificar(ModeloActividades::mdlBuscarPorId($actividadDemo)['tituloActividad']==='Actividad Demo', 'Actividad propia se consulta por ID dentro del tenant');
verificar(ModeloActividades::mdlBuscarPorId($actividadIncoherente)===null, 'Actividad con institución cruzada queda oculta por ID');
verificar(array_column(ModeloActividades::mdlListarParaUsuario($ids['B'],'ADMINISTRADOR'),'idActividad')===[$actividadDemo], 'Listado de actividades excluye otros tenants y datos incoherentes');
verificar(count(ModeloActividades::mdlPreguntasConOpciones($actividadDemo))===1, 'Preguntas heredan el aislamiento de su actividad');
$datosActividad=['tituloActividad'=>'Actividad creada por modelo','slug'=>'actividad-modelo-demo','descripcionActividad'=>'Ensayo',
    'tipoActividad'=>'multiple_choice','visibilidad'=>'privada','estadoActividad'=>'BORRADOR','id_curso'=>$cursoDemo,
    'id_seccion'=>$materiaDemo,'id_autor'=>$ids['B'],'puntajeMaximo'=>1,'intentosPermitidos'=>1,
    'permiteVisitantes'=>0,'esPlantilla'=>0,'alcancePlantilla'=>'personal','destacadaPublica'=>0,
    'id_actividad_origen'=>0,'recursoExternoUrl'=>'','recursoExternoEmbed'=>''];
$preguntasActividad=[['tipoPregunta'=>'multiple_choice','textoPregunta'=>'Pregunta segura','codigoBase'=>null,
    'lenguajeCodigo'=>'plaintext','variantesCodigo'=>null,'respuestaCorrecta'=>'','puntaje'=>1,'pista'=>'',
    'explicacionError'=>'','opciones'=>[['textoOpcion'=>'Sí','esCorrecta'=>1]]]];
$actividadModelo=ModeloActividades::mdlGuardarActividad($datosActividad,$preguntasActividad);
verificar($actividadModelo>0&&(int)ModeloActividades::mdlBuscarPorId($actividadModelo)['id_institucion']===$demo, 'Alta de actividad obtiene institución exclusivamente desde la sesión');
$datosActividad['tituloActividad']='Actividad actualizada';
verificar(ModeloActividades::mdlActualizarActividad($actividadModelo,$datosActividad,$preguntasActividad)==='ok'
    && ModeloActividades::mdlBuscarPorId($actividadModelo)['tituloActividad']==='Actividad actualizada', 'Edición de actividad revalida tenant y relaciones');
verificar(ModeloActividades::mdlActualizarMetadatosActividad($actividadModelo,['estadoActividad'=>'PUBLICADA'])==='ok', 'Metadatos de actividad se actualizan dentro del tenant');
$preguntaModelo=ModeloActividades::mdlPreguntasConOpciones($actividadModelo)[0];
sesionPara($ids['A']); ControladorInstitucion::seleccionar($demo,ControladorInstitucion::csrf(),ControladorInstitucion::version());
$intentoModelo=ModeloActividades::mdlRegistrarIntento(['id_actividad'=>$actividadModelo,'id_usuario'=>$ids['A'],
    'nombreVisitante'=>'','emailVisitante'=>'','puntaje'=>1,'estadoIntento'=>'ENTREGADO','ipVisitante'=>'127.0.0.1'],
    [['id_pregunta'=>$preguntaModelo['idPregunta'],'id_opcion'=>$preguntaModelo['opciones'][0]['idOpcion'],
      'textoRespuesta'=>'Sí','esCorrecta'=>1,'puntajeObtenido'=>1]]);
verificar($intentoModelo>0, 'Estudiante inscripto registra intento en actividad de su institución');
sesionPara($ids['B']); ControladorInstitucion::seleccionar($demo,ControladorInstitucion::csrf(),ControladorInstitucion::version());
verificar(count(ModeloActividades::mdlIntentosActividad($actividadModelo))===1
    && count(ModeloActividades::mdlDetalleIntentosActividad($actividadModelo))===1, 'Resultados e intentos heredan el tenant de la actividad');
verificar(count(ModeloActividades::mdlMetricasPreguntasActividad($actividadModelo))===1, 'Métricas de preguntas permanecen dentro de la actividad institucional');
$datosActividadPublica=array_merge($datosActividad,[
    'tituloActividad'=>'Actividad pública Demo','slug'=>'actividad-publica-demo','visibilidad'=>'publica',
    'estadoActividad'=>'PUBLICADA','permiteVisitantes'=>1
]);
$actividadPublica=ModeloActividades::mdlGuardarActividad($datosActividadPublica,$preguntasActividad);
verificar($actividadPublica>0, 'Actividad pública se crea vinculada a la institución activa');
$preguntaPublica=ModeloActividades::mdlPreguntasConOpciones($actividadPublica)[0];
$destinatariosAdmin=ModeloMensajes::mdlUsuariosPermitidosParaMensajes($ids['B'],'ADMINISTRADOR');
verificar(in_array($ids['A'],array_map('intval',array_column($destinatariosAdmin,'idUsuario')),true)
    && !in_array($ids['C'],array_map('intval',array_column($destinatariosAdmin,'idUsuario')),true), 'Mensajería administrativa ofrece solo miembros de la institución activa');
verificar(array_column(ModeloMensajes::mdlSeccionesParaMensajes($ids['B'],'ADMINISTRADOR'),'idSeccion')===[$materiaDemo], 'Selector de materias para mensajes queda limitado al tenant');
verificar(ModeloMensajes::mdlDestinatariosDeSeccion($materiaDemo)===[$ids['A']], 'Envío por materia incorpora solo estudiantes institucionales inscriptos');
$mensajeDemo=ModeloMensajes::mdlGuardarMensaje([
    'id_remitente'=>$ids['B'],'destinatarios'=>[$ids['A']],'contenidoMensaje'=>'Mensaje Demo',
    'fechaMensaje'=>'2026-09-13 12:30:00','adjuntos'=>[]
]);
verificar(is_int($mensajeDemo)&&$mensajeDemo>0&&(int)$pdo->query('SELECT id_institucion FROM mensajes WHERE idMensaje='.(int)$mensajeDemo)->fetchColumn()===$demo, 'Mensaje nuevo obtiene la institución exclusivamente desde la sesión');
denegado(function() use($ids) { ModeloMensajes::mdlGuardarMensaje([
    'id_remitente'=>$ids['B'],'destinatarios'=>[$ids['C']],'contenidoMensaje'=>'Cruce','adjuntos'=>[]
]); }, 'Mensaje rechaza destinatario sin membresía en la institución');
$detalleMensajeB=ModeloMensajes::mdlMensajeDetalle($mensajeDemo,$ids['B']);
verificar($detalleMensajeB!==null&&count($detalleMensajeB['destinatarios'])===1, 'Remitente consulta detalle y destinatarios dentro del tenant');
$marcaDescarga='descarga_mt_'.bin2hex(random_bytes(6));
$rutaMensajeDescarga='uploads/mensajes/'.$marcaDescarga.'.txt';
$rutaEntregaDescarga='uploads/lecciones/'.$marcaDescarga.'.txt';
$absolutosDescarga=[dirname(__DIR__).'/'.$rutaMensajeDescarga,dirname(__DIR__).'/'.$rutaEntregaDescarga];
foreach($absolutosDescarga as $archivoDescarga){file_put_contents($archivoDescarga,'contenido protegido');}
$absolutosDescarga=array_map('realpath',$absolutosDescarga);
register_shutdown_function(static function() use($absolutosDescarga){foreach($absolutosDescarga as $archivo){if(is_file($archivo)){unlink($archivo);}}});
$pdo->prepare('INSERT INTO mensajes_adjuntos(id_mensaje,nombreOriginal,nombreGuardado,rutaArchivo,mimeType,tamanoArchivo) VALUES(?,?,?,?,?,?)')
    ->execute([$mensajeDemo,'mensaje.txt',basename($rutaMensajeDescarga),$rutaMensajeDescarga,'text/plain',19]);
$adjuntoMensaje=(int)$pdo->lastInsertId();
$pdo->prepare('INSERT INTO entregaslecciones_adjuntos(id_entrega,nombreOriginal,rutaArchivo,mimeType,tamanoArchivo) VALUES(?,?,?,?,?)')
    ->execute([$entregaId,'entrega.txt',$rutaEntregaDescarga,'text/plain',19]);
$adjuntoEntrega=(int)$pdo->lastInsertId();
$sesionDescarga=$_SESSION;
$_SESSION=[];
denegado(function() use($adjuntoMensaje) { ControladorDescargas::crtResolverArchivo('mensaje',$adjuntoMensaje); }, 'Descarga protegida exige una sesión autenticada');
$_SESSION=$sesionDescarga;
verificar(ControladorDescargas::crtResolverArchivo('mensaje',$adjuntoMensaje)['ruta']===$absolutosDescarga[0]
    && ControladorDescargas::crtResolverArchivo('entrega',$adjuntoEntrega)['ruta']===$absolutosDescarga[1], 'Descarga protegida resuelve adjuntos propios después de autorizar tenant y participación');
$pdo->prepare('INSERT INTO mensajes_adjuntos(id_mensaje,nombreOriginal,nombreGuardado,rutaArchivo,mimeType,tamanoArchivo) VALUES(?,?,?,?,?,?)')
    ->execute([$mensajeDemo,'fuera.txt','fuera.txt','config.php','text/plain',1]);
$adjuntoFuera=(int)$pdo->lastInsertId();
try {
    ControladorDescargas::crtResolverArchivo('mensaje',$adjuntoFuera);
    throw new RuntimeException('No se rechazó la ruta fuera del directorio autorizado.');
} catch (RuntimeException $e) {
    verificar(strpos($e->getMessage(),'disponible')!==false, 'Descarga protegida rechaza rutas fuera de los directorios autorizados');
}
$notificacionDemo=ModeloNotificaciones::mdlRegistrarNotificacion([
    'id_usuario'=>$ids['A'],'tipoNotificacion'=>'ACTIVIDAD_PUBLICADA','referenciaTipo'=>'ACTIVIDAD',
    'referenciaId'=>$actividadPublica,'tituloNotificacion'=>'Actividad Demo','detalleNotificacion'=>'Nueva actividad',
    'urlNotificacion'=>'index.php?r=ver-actividad&idActividad='.$actividadPublica
]);
verificar($notificacionDemo==='ok'&&(int)$pdo->query('SELECT id_institucion FROM notificaciones ORDER BY idNotificacion DESC LIMIT 1')->fetchColumn()===$demo, 'Notificación obtiene institución desde el contexto y valida su recurso');
denegado(function() use($ids,$actividadPublica) { ModeloNotificaciones::mdlRegistrarNotificacion([
    'id_usuario'=>$ids['C'],'tipoNotificacion'=>'ACTIVIDAD_PUBLICADA','referenciaTipo'=>'ACTIVIDAD',
    'referenciaId'=>$actividadPublica,'tituloNotificacion'=>'Cruce','detalleNotificacion'=>'','urlNotificacion'=>''
]); }, 'Notificación rechaza destinatario de otra institución');
sesionPara($ids['A']); ControladorInstitucion::seleccionar($demo,ControladorInstitucion::csrf(),ControladorInstitucion::version());
$destinatariosEstudiante=ModeloMensajes::mdlUsuariosPermitidosParaMensajes($ids['A'],'ESTUDIANTE');
verificar(in_array($ids['B'],array_map('intval',array_column($destinatariosEstudiante,'idUsuario')),true)
    && !in_array($ids['C'],array_map('intval',array_column($destinatariosEstudiante,'idUsuario')),true), 'Estudiante sólo encuentra compañeros y responsables de cursos del tenant');
verificar(ModeloMensajes::mdlContarMensajesNoLeidos($ids['A'])===1&&ModeloMensajes::mdlMensajeDetalle($mensajeDemo,$ids['A'])!==null, 'Destinatario ve el mensaje únicamente dentro de su contexto institucional');
verificar(ModeloMensajes::mdlMarcarLeido($mensajeDemo,$ids['A'])==='ok'&&ModeloMensajes::mdlContarMensajesNoLeidos($ids['A'])===0, 'Lectura del mensaje se modifica dentro del tenant');
verificar(ControladorDescargas::crtResolverArchivo('mensaje',$adjuntoMensaje)['nombre']==='mensaje.txt'
    && ControladorDescargas::crtResolverArchivo('entrega',$adjuntoEntrega)['nombre']==='entrega.txt', 'Destinatario descarga el mensaje y el estudiante sólo su propia entrega');
$notificacionesDemo=ModeloNotificaciones::mdlListarNotificacionesUsuario($ids['A']);
verificar(count($notificacionesDemo)===1, 'Destinatario lista únicamente notificaciones de la institución activa');
$claveNotificacion='notificacion:'.(int)$notificacionesDemo[0]['idNotificacion'];
verificar(ModeloPanel::mdlMarcarNotificacionLeida($ids['A'],$claveNotificacion)==='ok'
    && (int)$pdo->query('SELECT id_institucion FROM notificaciones_lecturas ORDER BY idNotificacionLectura DESC LIMIT 1')->fetchColumn()===$demo, 'Marca de lectura se registra dentro del contexto institucional');
sesionPara($ids['B']); ControladorInstitucion::seleccionar($demo,ControladorInstitucion::csrf(),ControladorInstitucion::version());
$actividadEliminar=ModeloActividades::mdlGuardarActividad(array_merge($datosActividad,['slug'=>'actividad-eliminar-demo']),$preguntasActividad);
verificar(ModeloActividades::mdlEliminarActividad($actividadEliminar)==='ok'&&ModeloActividades::mdlBuscarPorId($actividadEliminar)===null, 'Eliminación de actividad propia limpia sus dependencias');
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
verificar(count($actividadesCopia)===3
    && count(array_filter($actividadesCopia,static function($actividad) use($demo){return (int)$actividad['id_institucion']===$demo&&$actividad['estadoActividad']==='BORRADOR';}))===3,
    'Duplicación conserva solo actividades coherentes en borrador e institución actual');
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
$pdo->prepare('INSERT INTO recursoslecciones(id_leccion,tipoRecurso,tituloRecurso,urlRecurso,creadoPor) VALUES(?,?,?,?,?)')
    ->execute([$leccionDemo,'ARCHIVO','Material protegido',$rutaEntregaDescarga,$ids['B']]);
$recursoLocal=(int)$pdo->lastInsertId();
verificar(ControladorDescargas::crtResolverArchivo('recurso',$recursoLocal)['ruta']===$absolutosDescarga[1], 'Administrador descarga un recurso local del tenant activo');
sesionPara($ids['A']); ControladorInstitucion::seleccionar($demo,ControladorInstitucion::csrf(),ControladorInstitucion::version());
verificar(ControladorDescargas::crtResolverArchivo('recurso',$recursoLocal)['nombre']==='Material protegido', 'Estudiante inscripto descarga un recurso local de su curso');
sesionPara($ids['B']); ControladorInstitucion::seleccionar($demo,ControladorInstitucion::csrf(),ControladorInstitucion::version());
$panelDemo=ModeloPanel::mdlResumenDashboard($ids['B'],'ADMINISTRADOR');
$tarjetasDemo=array_column($panelDemo['tarjetas'],'value','label');
verificar((int)$tarjetasDemo['Usuarios activos']===(int)$pdo->query('SELECT COUNT(*) FROM usuarios_instituciones ui INNER JOIN usuarios u ON u.idUsuario=ui.id_usuario WHERE ui.id_institucion='.(int)$demo.' AND ui.activo=1 AND u.activo=1')->fetchColumn()
    && (int)$tarjetasDemo['Cursos']===(int)$pdo->query('SELECT COUNT(*) FROM cursos WHERE id_institucion='.$demo)->fetchColumn()
    && (int)$tarjetasDemo['Secciones']===(int)$pdo->query('SELECT COUNT(*) FROM secciones s INNER JOIN cursos c ON c.idCurso=s.id_curso WHERE c.id_institucion='.$demo)->fetchColumn(),
    'Panel administrador calcula usuarios, cursos y materias sólo para su institución');
// Simula revocación entre preparación y ejecución de SQL.
$preparada=$pdo->prepare('SELECT idCurso FROM cursos c WHERE ' . ModeloTenant::cursos());
$pdo->prepare('UPDATE usuarios_instituciones SET activo=0 WHERE id_usuario=? AND id_institucion=?')->execute([$ids['B'],$demo]);
$preparada->execute();
verificar($preparada->fetchAll(PDO::FETCH_ASSOC)===[], 'SQL ya preparado respeta revocación posterior de membresía');
$pdo->prepare('UPDATE usuarios_instituciones SET activo=1 WHERE id_usuario=? AND id_institucion=?')->execute([$ids['B'],$demo]);
sesionPara($ids['A']);
ControladorInstitucion::seleccionar($mm,ControladorInstitucion::csrf(),ControladorInstitucion::version());
$panelMM=ModeloPanel::mdlResumenDashboard($ids['A'],'DOCENTE');
$tarjetasMM=array_column($panelMM['tarjetas'],'value','label');
verificar((int)$tarjetasMM['Secciones a cargo']===1&&(int)$tarjetasMM['Lecciones']===0, 'Panel docente se recalcula al cambiar a MenteMotion');
verificar(ModeloMaterias::mdlBuscarMateriaPorId($materiaDemo)===false, 'Docente no puede leer materia ajena por ID');
verificar(array_column(ModeloMaterias::mdlListarMateriasGestion(),'idSeccion')===[$materiaMM], 'Listado de materias conserva solo las de la institución activa');
denegado(function() use($leccionDemo) { ModeloLecciones::mdlBuscarLeccionPorId($leccionDemo); }, 'Lección ajena denegada por ID');
denegado(function() use($leccionDemo) { ModeloLecciones::mdlBuscarPostsPorLeccion($leccionDemo); }, 'Posteos de una lección ajena no se revelan por ID');
denegado(function() use($recursoDemo) { ModeloLecciones::mdlBuscarRecursoPorId($recursoDemo); }, 'Recurso ajeno denegado por ID');
denegado(function() use($leccionDemo) { ModeloLecciones::mdlEliminarLeccion($leccionDemo); }, 'Borrado de lección ajena rechazado antes de eliminar dependencias');
denegado(function() use($claseDemo) { ModeloAsistencias::mdlClase($claseDemo); }, 'Clase de asistencia ajena no se revela por ID');
denegado(function() use($materiaDemo) { ModeloAsistencias::mdlClasesSeccion($materiaDemo); }, 'Listado de asistencia ajeno se rechaza por materia');
denegado(function() use($materiaDemo) { ModeloCalificaciones::mdlCalificacionesPorSeccion($materiaDemo); }, 'Calificaciones de otra institución no se revelan por materia');
denegado(function() use($evaluacionDemo) { ModeloCalificaciones::mdlCalificacionesEvaluacion($evaluacionDemo); }, 'Planilla de evaluación ajena no se revela por ID');
verificar(ModeloActividades::mdlBuscarPorId($actividadDemo)===null, 'Actividad de otra institución no se revela por ID');
denegado(function() use($actividadDemo) { ModeloActividades::mdlEliminarActividad($actividadDemo); }, 'Actividad ajena no puede eliminarse por ID manipulado');
denegado(function() use($actividadModelo,$ids) { ModeloActividades::mdlContarIntentosUsuario($actividadModelo,$ids['A']); }, 'Intentos de otra institución no se cuentan por ID');
verificar(ModeloMensajes::mdlMensajeDetalle($mensajeDemo,$ids['A'])===null&&ModeloMensajes::mdlContarMensajesNoLeidos($ids['A'])===0, 'Cambio de institución oculta mensajes y contadores del tenant anterior');
denegado(function() use($mensajeDemo,$ids) { ModeloMensajes::mdlMarcarLeido($mensajeDemo,$ids['A']); }, 'Acción sobre mensaje de otra institución se rechaza por ID');
denegado(function() use($adjuntoMensaje) { ControladorDescargas::crtResolverArchivo('mensaje',$adjuntoMensaje); }, 'Adjunto de mensaje del tenant anterior no se descarga por ID');
denegado(function() use($adjuntoEntrega) { ControladorDescargas::crtResolverArchivo('entrega',$adjuntoEntrega); }, 'Adjunto de entrega del tenant anterior no se descarga por ID');
denegado(function() use($recursoLocal) { ControladorDescargas::crtResolverArchivo('recurso',$recursoLocal); }, 'Recurso local del tenant anterior no se descarga por ID');
verificar(ModeloNotificaciones::mdlListarNotificacionesUsuario($ids['A'])===[], 'Cambio de institución oculta notificaciones del tenant anterior');
verificar(ModeloCalificaciones::mdlResumenCierresGenerales()===[], 'Resumen general no filtra cierres desde otra institución');
verificar(ModeloCalificaciones::mdlCalificacionesGenerales()===[], 'Historial general no mezcla notas ni cierres de otra institución');
verificar(array_column(ModeloCalificaciones::mdlSeccionesParaCalificaciones(),'idSeccion')===[$materiaMM], 'Tarjetas agregadas cambian junto con la institución activa');
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
$_SESSION=[];
$publicas=ModeloActividades::mdlListarPublicas();
verificar(in_array($actividadPublica,array_map('intval',array_column($publicas,'idActividad')),true), 'Catálogo público conserva actividades de instituciones activas sin exigir membresía');
$publicaPorSlug=ModeloActividades::mdlBuscarPorSlug('actividad-publica-demo');
verificar((int)($publicaPorSlug['idActividad']??0)===$actividadPublica, 'Ruta pública resuelve el slug sin aceptar un tenant enviado por el visitante');
verificar(ModeloActividades::mdlBuscarPorSlug('actividad-modelo-demo')===null, 'Ruta pública no revela una actividad privada aunque esté publicada');
verificar(count(ModeloActividades::mdlPreguntasConOpciones($actividadPublica))===1, 'Preguntas públicas heredan la institución de la actividad publicada');
$intentoVisitante=ModeloActividades::mdlRegistrarIntento([
    'id_actividad'=>$actividadPublica,'id_usuario'=>0,'nombreVisitante'=>'Visitante Ensayo',
    'emailVisitante'=>'visitante@campus.example','puntaje'=>1,'estadoIntento'=>'ENTREGADO','ipVisitante'=>'127.0.0.1'
],[[
    'id_pregunta'=>$preguntaPublica['idPregunta'],'id_opcion'=>$preguntaPublica['opciones'][0]['idOpcion'],
    'textoRespuesta'=>'Sí','esCorrecta'=>1,'puntajeObtenido'=>1
]]);
verificar($intentoVisitante>0, 'Visitante registra intento únicamente en una actividad pública habilitada');
denegado(function() use($actividadModelo,$preguntaModelo) {
    ModeloActividades::mdlRegistrarIntento([
        'id_actividad'=>$actividadModelo,'id_usuario'=>0,'nombreVisitante'=>'Visitante Ensayo',
        'emailVisitante'=>'visitante@campus.example','puntaje'=>0,'estadoIntento'=>'ENTREGADO','ipVisitante'=>'127.0.0.1'
    ],[[
        'id_pregunta'=>$preguntaModelo['idPregunta'],'id_opcion'=>$preguntaModelo['opciones'][0]['idOpcion'],
        'textoRespuesta'=>'Sí','esCorrecta'=>0,'puntajeObtenido'=>0
    ]]);
}, 'Visitante no puede forzar un intento sobre una actividad privada');

[$servidorHttp,$urlHttp,$sesionesHttp]=levantarServidor('LOCAL');
$curlHttp=curl_init();
try {
    $respuestaHttp=peticion($curlHttp,$urlHttp.'?r=actividad-publica&slug=actividad-publica-demo');
    verificar($respuestaHttp['codigo']===200 && str_contains($respuestaHttp['body'],'Actividad pública Demo'),
        'HTTP público: actividad publicada se resuelve sin iniciar sesión ni elegir tenant');
    $respuestaHttp=peticion($curlHttp,$urlHttp.'?r=actividad-publica&slug=actividad-modelo-demo');
    verificar($respuestaHttp['codigo']===200 && !str_contains($respuestaHttp['body'],'Actividad actualizada'),
        'HTTP público: actividad privada no se revela por slug');
    $respuestaHttp=peticion($curlHttp,$urlHttp.'?r=login',[
        'login_email'=>'b@campus.example',
        'login_pass'=>$password,
    ]);
    verificar($respuestaHttp['codigo']===303 && $respuestaHttp['destino']==='index.php',
        'HTTP académico: administrador con una membresía ingresa al Campus');
    $respuestaHttp=peticion($curlHttp,$urlHttp);
    verificar($respuestaHttp['codigo']===200 && !str_contains($respuestaHttp['body'],'Fatal error')
        && str_contains($respuestaHttp['headers'],'no-store'),
        'HTTP académico: panel principal renderiza con el tenant revalidado');
    $respuestaHttp=peticion($curlHttp,$urlHttp.'?r=listado-cursos');
    verificar($respuestaHttp['codigo']===200 && str_contains($respuestaHttp['body'],'Curso Demo')
        && !str_contains($respuestaHttp['body'],'Curso MenteMotion'),
        'HTTP académico: listado de cursos no mezcla instituciones');
    $respuestaHttp=peticion($curlHttp,$urlHttp.'?r=detalle-curso&idCurso='.$cursoDemo);
    verificar($respuestaHttp['codigo']===200 && str_contains($respuestaHttp['body'],'Curso Demo'),
        'HTTP académico: recurso propio continúa disponible');
    $respuestaHttp=peticion($curlHttp,$urlHttp.'?r=detalle-curso&idCurso='.$cursoMM);
    verificar($respuestaHttp['codigo']===403 && str_contains($respuestaHttp['body'],'Acceso denegado')
        && !str_contains($respuestaHttp['body'],'Curso MenteMotion'),
        'HTTP académico: curso de otra institución devuelve acceso denegado');
    $respuestaHttp=peticion($curlHttp,$urlHttp.'?r=detalle-curso&idCurso='.$cursoMM,[
        'accion_curso'=>'eliminar_curso',
        'idCurso'=>$cursoMM,
    ]);
    verificar($respuestaHttp['codigo']===403
        && (int)$pdo->query('SELECT COUNT(*) FROM cursos WHERE idCurso='.(int)$cursoMM)->fetchColumn()===1,
        'HTTP académico: POST manipulado no elimina un curso ajeno');
    $respuestaHttp=peticion($curlHttp,$urlHttp.'?r=editar-materia&idSeccion='.$materiaMM,[
        'idSeccion'=>$materiaMM,
        'tituloSeccion'=>'Intento HTTP cruzado',
        'id_curso'=>$cursoMM,
        'docente'=>$ids['A'],
    ]);
    verificar($respuestaHttp['codigo']===403
        && (string)$pdo->query('SELECT tituloSeccion FROM secciones WHERE idSeccion='.(int)$materiaMM)->fetchColumn()==='Materia MenteMotion',
        'HTTP académico: docente o administrador no modifica una materia ajena');
    $respuestaHttp=peticion($curlHttp,$urlHttp.'?r=ver-actividad&idActividad='.$actividadIncoherente);
    verificar($respuestaHttp['codigo']===403 && !str_contains($respuestaHttp['body'],'Actividad incoherente'),
        'HTTP académico: actividad de otro tenant no se revela por ID');
    peticion($curlHttp,$urlHttp.'?r=logout');

    $respuestaHttp=peticion($curlHttp,$urlHttp.'?r=login',[
        'login_email'=>'a@campus.example',
        'login_pass'=>$password,
    ]);
    verificar($respuestaHttp['destino']==='index.php?r=seleccionar-institucion',
        'HTTP académico: usuario multiinstitución vuelve a elegir contexto');
    $respuestaHttp=peticion($curlHttp,$urlHttp.'?r=seleccionar-institucion');
    $formularioHttp=formularioInstitucion($respuestaHttp['body']);
    $respuestaHttp=peticion($curlHttp,$urlHttp.'?r=seleccionar-institucion',$formularioHttp+['id_institucion'=>$mm]);
    verificar($respuestaHttp['codigo']===303 && $respuestaHttp['destino']==='index.php',
        'HTTP académico: cambio a MenteMotion habilita el Campus');
    $respuestaHttp=peticion($curlHttp,$urlHttp.'?r=detalle-mensaje&idMensaje='.$mensajeDemo);
    verificar($respuestaHttp['codigo']===403 && !str_contains($respuestaHttp['body'],'Mensaje Demo'),
        'HTTP académico: mensaje de la institución anterior queda denegado');
} finally {
    curl_close($curlHttp);
    proc_terminate($servidorHttp);
    proc_close($servidorHttp);
}
echo "Ensayo de recursos conservado: $base\n";
ob_end_flush();
