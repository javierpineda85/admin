<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$base = getenv('CAMPUS_TEST_BASE') ?: '';
if (!preg_match('/^campus_mt_fase2_[0-9]{8}_[0-9]{6}_[a-f0-9]{6}$/D', $base)) {
    throw new RuntimeException('Indicá una base sintética de multi_institucion_recursos.php en CAMPUS_TEST_BASE.');
}
define('DB_NAME', $base);
define('INSTITUCIONES_CONTEXTO_ACTIVO', false);
require __DIR__ . '/../config.php';
require __DIR__ . '/../modelos/cursos.modelo.php';
require __DIR__ . '/../modelos/materias.modelo.php';
require __DIR__ . '/../modelos/lecciones.modelo.php';
require __DIR__ . '/../modelos/asistencias.modelo.php';
require __DIR__ . '/../modelos/calificaciones.modelo.php';
require __DIR__ . '/../modelos/actividades.modelo.php';
require __DIR__ . '/../modelos/mensajes.modelo.php';
require __DIR__ . '/../modelos/notificaciones.modelo.php';
require __DIR__ . '/../modelos/panel.modelo.php';
$pdo = Conexion::conectar();
function comprobarLegacy($condicion, $mensaje) {
    if (!$condicion) { throw new RuntimeException($mensaje); }
    echo 'OK legacy: ', $mensaje, PHP_EOL;
}
comprobarLegacy(count(ModeloCursos::mdlListarCursos()) === (int)$pdo->query('SELECT COUNT(*) FROM cursos')->fetchColumn(), 'Listado conserva todos los cursos con contexto desactivado');
$idCurso=(int)$pdo->query('SELECT MIN(idCurso) FROM cursos')->fetchColumn();
comprobarLegacy(ModeloCursos::mdlBuscarCursoPorId($idCurso)!==null, 'Consulta de curso habitual funciona sin contexto');
$idSeccion=(int)$pdo->query('SELECT MIN(idSeccion) FROM secciones')->fetchColumn();
comprobarLegacy(ModeloMaterias::mdlBuscarMateriaPorId($idSeccion)!==false, 'Consulta de materia habitual funciona sin contexto');
$idLeccion=(int)$pdo->query('SELECT MIN(idLeccion) FROM lecciones')->fetchColumn();
$leccion=ModeloLecciones::mdlBuscarLeccionPorId($idLeccion);
comprobarLegacy($leccion!==null, 'Consulta de lección habitual funciona sin contexto');
comprobarLegacy(ModeloLecciones::mdlActualizarLeccion('lecciones',$leccion)==='ok', 'Escritura de lección habitual conserva compatibilidad');
$seccionesAsistencia=ModeloAsistencias::mdlSecciones();
comprobarLegacy(count($seccionesAsistencia)>0, 'Listado habitual de asistencia conserva materias existentes');
$idSeccionAsistencia=(int)$pdo->query('SELECT MIN(id_seccion) FROM asistencia_clases')->fetchColumn();
comprobarLegacy(count(ModeloAsistencias::mdlClasesSeccion($idSeccionAsistencia))>0, 'Consulta habitual de clases funciona sin contexto');
$idSeccionCalificacion=(int)$pdo->query('SELECT MIN(id_seccion) FROM calificaciones')->fetchColumn();
comprobarLegacy(count(ModeloCalificaciones::mdlCalificacionesPorSeccion($idSeccionCalificacion))===(int)$pdo->query('SELECT COUNT(*) FROM calificaciones WHERE id_seccion='.(int)$idSeccionCalificacion)->fetchColumn(), 'Listado habitual de calificaciones conserva registros existentes');
$notaLegacy=$pdo->query('SELECT * FROM calificaciones ORDER BY idCalificacion LIMIT 1')->fetch(PDO::FETCH_ASSOC);
comprobarLegacy(ModeloCalificaciones::mdlGuardarCalificacion($notaLegacy)==='ok', 'Escritura habitual de calificación conserva compatibilidad');
$cantidadHistorial=(int)$pdo->query('SELECT (SELECT COUNT(*) FROM calificaciones)+(SELECT COUNT(*) FROM evaluaciones_calificaciones)+(SELECT COUNT(*) FROM cierres_periodo_calificaciones)')->fetchColumn();
comprobarLegacy(count(ModeloCalificaciones::mdlCalificacionesGenerales())===$cantidadHistorial, 'Historial general habitual conserva calificaciones y cierres existentes');
comprobarLegacy(count(ModeloCalificaciones::mdlResumenCierresGenerales())===(int)$pdo->query('SELECT COUNT(*) FROM cierres_periodo_calificaciones')->fetchColumn(), 'Resumen habitual conserva cierres existentes');
$cantidadActividades=(int)$pdo->query('SELECT COUNT(*) FROM actividades WHERE COALESCE(esPlantilla,0)=0')->fetchColumn();
comprobarLegacy(count(ModeloActividades::mdlListarParaUsuario(0,'ADMINISTRADOR'))===$cantidadActividades, 'Listado habitual de actividades conserva registros existentes');
$idActividad=(int)$pdo->query('SELECT MIN(idActividad) FROM actividades')->fetchColumn();
comprobarLegacy(ModeloActividades::mdlBuscarPorId($idActividad)!==null, 'Consulta habitual de actividad por ID funciona sin contexto');
$cantidadPublicas=(int)$pdo->query("SELECT COUNT(*) FROM actividades WHERE estadoActividad='PUBLICADA' AND COALESCE(esPlantilla,0)=0 AND visibilidad='publica'")->fetchColumn();
comprobarLegacy(count(ModeloActividades::mdlListarPublicas())===$cantidadPublicas, 'Catálogo público conserva compatibilidad con contexto desactivado');
$idUsuarioMensaje=(int)$pdo->query("SELECT id_usuario FROM mensajes_participantes WHERE rolParticipante='DESTINATARIO' ORDER BY idMensajeParticipante LIMIT 1")->fetchColumn();
comprobarLegacy(ModeloMensajes::mdlContarMensajesRecibidos($idUsuarioMensaje)===(int)$pdo->query("SELECT COUNT(*) FROM mensajes_participantes WHERE id_usuario=".$idUsuarioMensaje." AND rolParticipante='DESTINATARIO' AND enPapelera=0 AND eliminado=0")->fetchColumn(), 'Contador habitual de mensajes conserva compatibilidad sin contexto');
$idUsuarioNotificacion=(int)$pdo->query('SELECT id_usuario FROM notificaciones ORDER BY idNotificacion LIMIT 1')->fetchColumn();
comprobarLegacy(count(ModeloNotificaciones::mdlListarNotificacionesUsuario($idUsuarioNotificacion))===(int)$pdo->query('SELECT COUNT(*) FROM notificaciones WHERE id_usuario='.$idUsuarioNotificacion)->fetchColumn(), 'Listado habitual de notificaciones conserva compatibilidad sin contexto');
$panelLegacy=ModeloPanel::mdlResumenDashboard($idUsuarioMensaje,'ADMINISTRADOR');
$tarjetasLegacy=array_column($panelLegacy['tarjetas'],'value','label');
comprobarLegacy((int)$tarjetasLegacy['Cursos']===(int)$pdo->query('SELECT COUNT(*) FROM cursos')->fetchColumn(), 'Panel administrador habitual conserva cursos de la instalación sin contexto');
