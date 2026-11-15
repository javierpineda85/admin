<?php
require_once __DIR__ . '/../modelos/calificaciones.modelo.php';
require_once __DIR__ . '/notificaciones.controller.php';

class ControladorCalificaciones
{
    public static function crtCalificacionesGenerales($idEstudiante = 0, $idDocente = 0)
    {
        return ModeloCalificaciones::mdlCalificacionesGenerales((int) $idEstudiante, (int) $idDocente);
    }

    public static function crtResumenCierresGenerales($idEstudiante = 0, $idDocente = 0)
    {
        return ModeloCalificaciones::mdlResumenCierresGenerales((int)$idEstudiante,(int)$idDocente);
    }

    public static function crtSeccionesParaCalificaciones($idDocente = 0)
    {
        return ModeloCalificaciones::mdlSeccionesParaCalificaciones((int)$idDocente);
    }

    public static function crtCalificacionesTrabajosPorPeriodo($idSeccion,$idPeriodo)
    {
        return ModeloCalificaciones::mdlCalificacionesTrabajosPorPeriodo((int)$idSeccion,(int)$idPeriodo);
    }

    public static function crtEstudiantesIntensificacion($idCurso,$idSeccion)
    {
        return ModeloCalificaciones::mdlEstudiantesIntensificacion((int)$idCurso,(int)$idSeccion);
    }
    public static function crtProcesarAcciones()
    {
        $accion = trim((string) ($_POST['accion'] ?? ''));
        if ($accion === 'guardar_calificacion') {
            return self::crtGuardarCalificacion();
        }

        if ($accion === 'guardar_calificaciones_entregas') {
            return self::crtGuardarCalificacionesEntregas();
        }

        if ($accion === 'crear_evaluacion') {
            return self::crtCrearEvaluacion();
        }

        if ($accion === 'editar_evaluacion') {
            return self::crtEditarEvaluacion();
        }

        if ($accion === 'eliminar_evaluacion') {
            return self::crtEliminarEvaluacion();
        }

        if ($accion === 'guardar_calificaciones_evaluacion') {
            return self::crtGuardarCalificacionesEvaluacion();
        }
        if ($accion === 'cambiar_estado_periodo') {
            return self::crtCambiarEstadoPeriodo();
        }
        if ($accion === 'calcular_cierre_periodo') { return self::crtCalcularCierrePeriodo(); }
        if ($accion === 'guardar_cierre_periodo') { return self::crtGuardarCierrePeriodo(); }

        return null;
    }

    private static function puedeGestionarSeccion($idSeccion)
    {
        ModeloTenant::exigirSeccion((int)$idSeccion);
        if (ControladorPermisos::esAdministrador()) {
            return true;
        }

        return ControladorPermisos::esDocente()
            && ControladorLecciones::crtSeccionAsignadaDocente((int) $idSeccion, (int) ($_SESSION['usuario']['id'] ?? 0));
    }

    private static function cambioCalificacion($anterior, $calificacion, $devolucion, $estadoAsistencia = 'PRESENTE')
    {
        if (!$anterior) {
            return true;
        }

        $anteriorAusente = strtoupper((string) ($anterior['estadoAsistencia'] ?? 'PRESENTE')) === 'AUSENTE';
        $nuevoAusente = strtoupper((string) $estadoAsistencia) === 'AUSENTE';
        if ($anteriorAusente !== $nuevoAusente) {
            return true;
        }

        $notaAnterior = $anteriorAusente || !isset($anterior['calificacion']) ? null : round((float) $anterior['calificacion'], 2);
        $notaNueva = $nuevoAusente || $calificacion === null ? null : round((float) $calificacion, 2);
        return $notaAnterior !== $notaNueva
            || trim((string) ($anterior['devolucion'] ?? '')) !== trim((string) $devolucion);
    }

    private static function notificarCalificacionesActualizadas(array $estudiantes, array $idsEstudiantes, array $datos, array $referenciasPorEstudiante = [])
    {
        $estudiantesPorId = [];
        foreach ($estudiantes as $estudiante) {
            $estudiantesPorId[(int) ($estudiante['idUsuario'] ?? 0)] = $estudiante;
        }

        $notificados = 0;
        foreach (array_unique(array_map('intval', $idsEstudiantes)) as $idEstudiante) {
            if ($idEstudiante <= 0 || empty($estudiantesPorId[$idEstudiante])) {
                continue;
            }
            $datosEstudiante = $datos;
            if (isset($referenciasPorEstudiante[$idEstudiante])) {
                $datosEstudiante['referenciaId'] = (int) $referenciasPorEstudiante[$idEstudiante];
            }
            if (in_array(ControladorNotificaciones::crtNotificarCalificacion($estudiantesPorId[$idEstudiante], $datosEstudiante), ['ok', 'actualizada'], true)) {
                $notificados++;
            }
        }

        return $notificados;
    }

    public static function crtCrearEvaluacion()
    {
        $idSeccion = (int) ($_POST['id_seccion'] ?? 0);
        $tema = trim((string) ($_POST['temaEvaluacion'] ?? ''));
        $fecha = trim((string) ($_POST['fechaEvaluacion'] ?? ''));
        $idPeriodo = (int) ($_POST['id_periodo'] ?? 0);
        $idInstrumento = (int) ($_POST['id_instrumento'] ?? 0);
        $seccion = $idSeccion > 0 ? ControladorLecciones::crtBuscarSeccionPorId($idSeccion) : null;

        if (!$seccion || !self::puedeGestionarSeccion($idSeccion)) {
            $_SESSION['error_message'] = 'No tenes permisos para crear evaluaciones en esta materia.';
            return 'denied';
        }

        $fechaObjeto = DateTime::createFromFormat('Y-m-d', $fecha);
        $fechaValida = $fechaObjeto instanceof DateTime
            && $fechaObjeto->format('Y-m-d') === $fecha;

        $contexto = ModeloCalificaciones::mdlContextoAcademicoSeccion($idSeccion);
        $periodosPermitidos = array_fill_keys(array_map('intval', array_column($contexto['periodos'], 'idPeriodo')), true);
        $instrumentosPermitidos = array_fill_keys(array_map('intval', array_column($contexto['instrumentos'], 'idInstrumento')), true);
        $periodo = ModeloCalificaciones::mdlPeriodoPorId($idPeriodo,$idSeccion);
        if ($tema === '' || !$fechaValida || !isset($periodosPermitidos[$idPeriodo], $instrumentosPermitidos[$idInstrumento]) || strtoupper((string)($periodo['estado'] ?? '')) !== 'ABIERTO') {
            $_SESSION['error_message'] = 'Completa período, instrumento, tema y una fecha válida. El período debe estar abierto.';
            return 'error';
        }

        $respuesta = ModeloCalificaciones::mdlCrearEvaluacion([
            'id_seccion' => $idSeccion,
            'id_curso' => (int) ($seccion['id_curso'] ?? 0),
            'id_periodo' => $idPeriodo,
            'id_instrumento' => $idInstrumento,
            'id_autor' => (int) ($_SESSION['usuario']['id'] ?? 0),
            'temaEvaluacion' => $tema,
            'fechaEvaluacion' => $fecha,
        ]);

        if (is_int($respuesta) && $respuesta > 0) {
            $_SESSION['success_message'] = 'Evaluacion creada. Ya podes cargar las calificaciones.';
            return $respuesta;
        }

        $_SESSION['error_message'] = 'No se pudo crear la evaluacion.';
        return 'error';
    }

    public static function crtEditarEvaluacion()
    {
        $idEvaluacion = (int) ($_POST['id_evaluacion'] ?? 0);
        $tema = trim((string) ($_POST['temaEvaluacion'] ?? ''));
        $evaluacion = $idEvaluacion > 0 ? ModeloCalificaciones::mdlEvaluacionPorId($idEvaluacion) : null;

        if (!$evaluacion || !self::puedeGestionarSeccion((int) $evaluacion['id_seccion'])) {
            $_SESSION['error_message'] = 'No tenes permisos para editar esta evaluacion.';
            return 'denied';
        }
        if (strtoupper((string)($evaluacion['estadoPeriodo'] ?? '')) === 'CERRADO') {
            $_SESSION['error_message'] = 'El período está cerrado y no admite modificaciones.';
            return 'locked';
        }
        if ($tema === '' || strlen($tema) > 180) {
            $_SESSION['error_message'] = 'Completa un tema de hasta 180 caracteres.';
            return 'error';
        }

        $respuesta = ModeloCalificaciones::mdlActualizarEvaluacion([
            'idEvaluacion' => $idEvaluacion,
            'temaEvaluacion' => $tema,
        ]);

        if ($respuesta === 'ok') {
            $_SESSION['success_message'] = 'Evaluacion actualizada correctamente.';
        } else {
            $_SESSION['error_message'] = 'No se pudo actualizar la evaluacion.';
        }

        return $respuesta;
    }

    public static function crtEliminarEvaluacion()
    {
        $idEvaluacion = (int) ($_POST['id_evaluacion'] ?? 0);
        $evaluacion = $idEvaluacion > 0 ? ModeloCalificaciones::mdlEvaluacionPorId($idEvaluacion) : null;

        if (!$evaluacion || !self::puedeGestionarSeccion((int) $evaluacion['id_seccion'])) {
            $_SESSION['error_message'] = 'No tenes permisos para eliminar esta evaluacion.';
            return 'denied';
        }
        if (strtoupper((string)($evaluacion['estadoPeriodo'] ?? '')) === 'CERRADO') {
            $_SESSION['error_message'] = 'El período está cerrado y no permite eliminar evaluaciones.';
            return 'locked';
        }

        $respuesta = ModeloCalificaciones::mdlEliminarEvaluacion($idEvaluacion);
        if ($respuesta === 'ok') {
            $_SESSION['success_message'] = 'Evaluacion y calificaciones eliminadas correctamente.';
        } else {
            $_SESSION['error_message'] = 'No se pudo eliminar la evaluacion.';
        }

        return $respuesta;
    }

    public static function crtGuardarCalificacionesEvaluacion()
    {
        $idEvaluacion = (int) ($_POST['id_evaluacion'] ?? 0);
        $evaluacion = $idEvaluacion > 0 ? ModeloCalificaciones::mdlEvaluacionPorId($idEvaluacion) : null;

        if (!$evaluacion || !self::puedeGestionarSeccion((int) $evaluacion['id_seccion'])) {
            $_SESSION['error_message'] = 'No tenes permisos para calificar esta evaluacion.';
            return 'denied';
        }
        if (strtoupper((string)($evaluacion['estadoPeriodo'] ?? '')) === 'CERRADO') {
            $_SESSION['error_message'] = 'El período está cerrado y no permite modificar notas.';
            return 'locked';
        }

        $notas = (array) ($_POST['calificaciones'] ?? []);
        $devoluciones = (array) ($_POST['devoluciones'] ?? []);
        $ausentes = array_fill_keys(array_map('intval', (array) ($_POST['ausentes'] ?? [])), true);
        $estudiantes = ModeloCalificaciones::mdlEstudiantesPorCurso((int) $evaluacion['id_curso']);
        $permitidos = array_fill_keys(array_map('intval', array_column($estudiantes, 'idUsuario')), true);
        $calificacionesPrevias = [];
        foreach (ModeloCalificaciones::mdlCalificacionesEvaluacion($idEvaluacion) as $calificacionPrevia) {
            $calificacionesPrevias[(int) $calificacionPrevia['id_estudiante']] = $calificacionPrevia;
        }
        $calificaciones = [];
        $estudiantesANotificar = [];

        foreach ($notas as $idEstudiante => $nota) {
            $idEstudiante = (int) $idEstudiante;
            $nota = trim((string) $nota);

            $esAusente = isset($ausentes[$idEstudiante]);
            if (($nota === '' && !$esAusente) || !isset($permitidos[$idEstudiante])) {
                continue;
            }

            $notaNormalizada = str_replace(',', '.', $nota);
            if (!$esAusente && !is_numeric($notaNormalizada)) {
                $_SESSION['error_message'] = 'Revisa las calificaciones ingresadas.';
                return 'error';
            }

            $notaNumerica = $esAusente ? null : (float) $notaNormalizada;
            if (!$esAusente && ($notaNumerica < 0 || $notaNumerica > 10)) {
                $_SESSION['error_message'] = 'Todas las calificaciones deben estar entre 0 y 10.';
                return 'error';
            }

            $calificaciones[] = [
                'id_estudiante' => $idEstudiante,
                'calificacion' => $notaNumerica,
                'estadoAsistencia' => $esAusente ? 'AUSENTE' : 'PRESENTE',
                'devolucion' => trim((string) ($devoluciones[$idEstudiante] ?? '')),
            ];
            if (self::cambioCalificacion($calificacionesPrevias[$idEstudiante] ?? null, $notaNumerica, (string) ($devoluciones[$idEstudiante] ?? ''), $esAusente ? 'AUSENTE' : 'PRESENTE')) {
                $estudiantesANotificar[] = $idEstudiante;
            }
        }

        if (empty($calificaciones)) {
            $_SESSION['error_message'] = 'Carga al menos una calificacion.';
            return 'error';
        }

        $respuesta = ModeloCalificaciones::mdlGuardarCalificacionesEvaluacion($idEvaluacion, $calificaciones);
        if ($respuesta === 'ok') {
            self::notificarCalificacionesActualizadas($estudiantes, $estudiantesANotificar, [
                'tipoNotificacion' => 'CALIFICACION_ACTUALIZADA',
                'referenciaTipo' => 'EVALUACION',
                'referenciaId' => $idEvaluacion,
                'tituloNotificacion' => 'Nueva calificación disponible',
                'detalleNotificacion' => (string) $evaluacion['temaEvaluacion'] . ' · ' . (string) $evaluacion['tituloSeccion'],
                'urlNotificacion' => 'index.php?r=calificaciones-seccion&idSeccion=' . (int) $evaluacion['id_seccion'] . '&idPeriodo=' . (int) $evaluacion['id_periodo'],
                'asuntoEmail' => 'Nueva calificación disponible en el Campus',
                'tituloEmail' => (string) $evaluacion['temaEvaluacion'],
                'contextoEmail' => (string) $evaluacion['nombreCurso'] . ' - ' . (string) $evaluacion['tituloSeccion'],
                'urlEmail' => APP_BASE_URL . '/index.php?r=calificaciones-seccion&idSeccion=' . (int) $evaluacion['id_seccion'] . '&idPeriodo=' . (int) $evaluacion['id_periodo'],
            ]);
            $_SESSION['success_message'] = 'Calificaciones guardadas correctamente.';
        } else {
            $_SESSION['error_message'] = 'No se pudieron guardar las calificaciones.';
        }

        return $respuesta;
    }

    public static function crtCambiarEstadoPeriodo()
    {
        $idPeriodo=(int)($_POST['id_periodo']??0); $idSeccion=(int)($_POST['id_seccion']??0);
        $estado=strtoupper(trim((string)($_POST['estado_periodo']??''))); $periodo=ModeloCalificaciones::mdlPeriodoPorId($idPeriodo,$idSeccion);
        $contexto=ModeloCalificaciones::mdlContextoAcademicoSeccion($idSeccion);
        $periodosPermitidos=array_fill_keys(array_map('intval',array_column($contexto['periodos']??[],'idPeriodo')),true);
        if(!$periodo || !isset($periodosPermitidos[$idPeriodo]) || !self::puedeGestionarSeccion($idSeccion) || !in_array($estado,['ABIERTO','CERRADO'],true)){
            $_SESSION['error_message']='No se pudo cambiar el estado del período.'; return 'denied';
        }
        if($estado==='ABIERTO' && !ControladorPermisos::esAdministrador()){
            $_SESSION['error_message']='Solo el administrador puede reabrir un período cerrado.'; return 'denied';
        }
        if($estado==='CERRADO'){
            $cierres=ModeloCalificaciones::mdlCierresPeriodo($idPeriodo,$idSeccion);
            if(!$cierres || count(array_filter($cierres,static function($cierre){return (int)($cierre['confirmada']??0)!==1;}))>0){
                $_SESSION['error_message']='Calculá y confirmá las calificaciones de cierre antes de cerrar el período.'; return 'error';
            }
        }
        $motivo=trim((string)($_POST['motivoReapertura']??''));
        if($estado==='ABIERTO' && $motivo===''){$_SESSION['error_message']='Indica el motivo de la reapertura.';return 'error';}
        $respuesta=ModeloCalificaciones::mdlCambiarEstadoPeriodo($idPeriodo,$idSeccion,$estado,(int)($_SESSION['usuario']['id']??0),$motivo);
        $_SESSION[$respuesta==='ok'?'success_message':'error_message']=$respuesta==='ok'?($estado==='CERRADO'?'Período cerrado y bloqueado.':'Período reabierto correctamente.'):'No se pudo actualizar el período.';
        return $respuesta;
    }

    public static function crtCalcularCierrePeriodo()
    {
        $idPeriodo=(int)($_POST['id_periodo']??0); $idSeccion=(int)($_POST['id_seccion']??0);
        $periodo=ModeloCalificaciones::mdlPeriodoPorId($idPeriodo,$idSeccion);
        if(!$periodo || strtoupper((string)$periodo['estado'])!=='ABIERTO' || !self::puedeGestionarSeccion($idSeccion)){
            $_SESSION['error_message']='El período no está abierto o no tenés permisos.'; return 'denied';
        }
        $cantidad=ModeloCalificaciones::mdlCalcularCierresPeriodo($idPeriodo,$idSeccion,(int)($_SESSION['usuario']['id']??0));
        $_SESSION[$cantidad>0?'success_message':'error_message']=$cantidad>0?'Se calcularon '.$cantidad.' promedios sugeridos. Podés revisarlos antes de confirmar.':'No hay notas presentes para calcular el cierre.';
        return $cantidad>0?'ok':'error';
    }

    public static function crtGuardarCierrePeriodo()
    {
        $idPeriodo=(int)($_POST['id_periodo']??0); $idSeccion=(int)($_POST['id_seccion']??0); $periodo=ModeloCalificaciones::mdlPeriodoPorId($idPeriodo,$idSeccion); $seccion=ControladorLecciones::crtBuscarSeccionPorId($idSeccion);
        if(!$periodo || !$seccion || strtoupper((string)$periodo['estado'])!=='ABIERTO' || !self::puedeGestionarSeccion($idSeccion)){
            $_SESSION['error_message']='El período está cerrado o no tenés permisos.'; return 'denied';
        }
        $cierresPrevios=ModeloCalificaciones::mdlCierresPeriodo($idPeriodo,$idSeccion); $permitidos=array_fill_keys(array_map('intval',array_column($cierresPrevios,'id_estudiante')),true); $cierresPorEstudiante=[]; foreach($cierresPrevios as $cierre){$cierresPorEstudiante[(int)$cierre['id_estudiante']]=$cierre;} $notas=[]; $estudiantesANotificar=[]; $referencias=[];
        foreach((array)($_POST['cierres']??[]) as $idEstudiante=>$valor){$normalizada=str_replace(',','.',trim((string)$valor));$idEstudiante=(int)$idEstudiante;if(!isset($permitidos[$idEstudiante])||!is_numeric($normalizada)||(float)$normalizada<0||(float)$normalizada>10){$_SESSION['error_message']='Revisá las notas de cierre. Deben estar entre 0 y 10.';return 'error';}$notas[$idEstudiante]=(float)$normalizada;$anterior=$cierresPorEstudiante[$idEstudiante]??null;if(!$anterior || (int)($anterior['confirmada']??0)!==1 || round((float)($anterior['calificacionCierre']??-1),2)!==round((float)$notas[$idEstudiante],2)){$estudiantesANotificar[]=$idEstudiante;$referencias[$idEstudiante]=(int)($anterior['idCierrePeriodo']??0);}}
        if(!$notas){$_SESSION['error_message']='No hay notas de cierre para guardar.';return 'error';}
        $respuesta=ModeloCalificaciones::mdlGuardarCierresPeriodo($idPeriodo,$idSeccion,$notas,(int)($_SESSION['usuario']['id']??0));
        if($respuesta==='ok'){
            $cierresActuales=ModeloCalificaciones::mdlCierresPeriodo($idPeriodo,$idSeccion);
            foreach($cierresActuales as $cierreActual){if(in_array((int)$cierreActual['id_estudiante'],$estudiantesANotificar,true)){$referencias[(int)$cierreActual['id_estudiante']]=(int)$cierreActual['idCierrePeriodo'];}}
            self::notificarCalificacionesActualizadas(ModeloCalificaciones::mdlEstudiantesPorCurso((int)$seccion['id_curso']),$estudiantesANotificar,[
                'tipoNotificacion'=>'CIERRE_PERIODO_ACTUALIZADO','referenciaTipo'=>'CIERRE_PERIODO','tituloNotificacion'=>'Calificación de cierre disponible','detalleNotificacion'=>(string)$periodo['nombre'].' · '.(string)$seccion['tituloSeccion'],'urlNotificacion'=>'index.php?r=calificaciones-seccion&idSeccion='.$idSeccion.'&idPeriodo='.$idPeriodo,'asuntoEmail'=>'Nueva calificación disponible en el Campus','tituloEmail'=>'Cierre: '.(string)$periodo['nombre'],'contextoEmail'=>(string)$seccion['nombreCurso'].' - '.(string)$seccion['tituloSeccion'],'urlEmail'=>APP_BASE_URL.'/index.php?r=calificaciones-seccion&idSeccion='.$idSeccion.'&idPeriodo='.$idPeriodo,
            ],$referencias);
            $_SESSION['success_message']='Calificaciones de cierre confirmadas.';
        } else { $_SESSION['error_message']='No se pudieron guardar los cierres.'; }
        return $respuesta;
    }

    public static function crtGuardarCalificacionesEntregas()
    {
        $idSeccion = (int) ($_POST['id_seccion'] ?? 0);
        $idLeccion = (int) ($_POST['id_modulo'] ?? 0);
        $idCurso = (int) ($_POST['id_curso'] ?? 0);
        $seccion = $idSeccion > 0 ? ControladorLecciones::crtBuscarSeccionPorId($idSeccion) : null;
        $leccion = $idLeccion > 0 ? ControladorLecciones::crtBuscarLeccionPorId($idLeccion) : null;

        if (
            !$seccion
            || !$leccion
            || !self::puedeGestionarSeccion($idSeccion)
            || (int) ($seccion['id_curso'] ?? 0) !== $idCurso
            || (int) ($leccion['id_modulo'] ?? 0) !== $idSeccion
            || strtoupper((string) ($leccion['tipoLeccion'] ?? '')) !== 'TAREA'
        ) {
            $_SESSION['error_message'] = 'No tenes permisos para corregir estas entregas.';
            return 'denied';
        }

        $idPeriodo = (int) ($leccion['id_periodo'] ?? 0);
        $periodo = $idPeriodo > 0 ? ModeloCalificaciones::mdlPeriodoPorId($idPeriodo,$idSeccion) : null;
        if (!$periodo || strtoupper((string)($periodo['estado'] ?? '')) !== 'ABIERTO') {
            $_SESSION['error_message'] = 'La tarea debe tener un período abierto asignado antes de cargar sus notas.';
            return 'error';
        }

        $notas = (array) ($_POST['calificaciones'] ?? []);
        $devoluciones = (array) ($_POST['devoluciones'] ?? []);
        $entregas = ControladorLecciones::crtBuscarEntregasPorLeccion($idLeccion);
        $estudiantesPermitidos = [];

        foreach ($entregas as $entrega) {
            if (
                (int) ($entrega['id_seccion'] ?? 0) === $idSeccion
                && (int) ($entrega['id_curso'] ?? 0) === $idCurso
            ) {
                $estudiantesPermitidos[(int) $entrega['id_estudiante']] = true;
            }
        }

        $calificaciones = [];
        $estudiantesANotificar = [];
        foreach ($notas as $idEstudiante => $nota) {
            $idEstudiante = (int) $idEstudiante;
            $nota = trim((string) $nota);

            if ($nota === '') {
                continue;
            }

            if (!isset($estudiantesPermitidos[$idEstudiante]) || !preg_match('/^\d{1,3}$/', $nota)) {
                $_SESSION['error_message'] = 'Revisa las calificaciones ingresadas.';
                return 'error';
            }

            $notaNumerica = (int) $nota;
            if ($notaNumerica < 0 || $notaNumerica > 10) {
                $_SESSION['error_message'] = 'Todas las calificaciones deben estar entre 0 y 10.';
                return 'error';
            }

            $calificaciones[] = [
                'id_estudiante' => $idEstudiante,
                'id_seccion' => $idSeccion,
                'id_modulo' => $idLeccion,
                'id_curso' => $idCurso,
                'id_periodo' => $idPeriodo,
                'calificacion' => $notaNumerica,
                'devolucion' => trim((string) ($devoluciones[$idEstudiante] ?? '')),
            ];
            $calificacionPrevia = ModeloCalificaciones::mdlCalificacionPorLeccionYEstudiante($idSeccion, $idLeccion, $idEstudiante);
            if (self::cambioCalificacion($calificacionPrevia, $notaNumerica, (string) ($devoluciones[$idEstudiante] ?? ''))) {
                $estudiantesANotificar[] = $idEstudiante;
            }
        }

        if (empty($calificaciones)) {
            $_SESSION['error_message'] = 'Carga al menos una calificacion antes de guardar.';
            return 'error';
        }

        $respuesta = ModeloCalificaciones::mdlGuardarCalificaciones($calificaciones);
        if ($respuesta === 'ok') {
            self::notificarCalificacionesActualizadas(ModeloCalificaciones::mdlEstudiantesPorCurso($idCurso), $estudiantesANotificar, [
                'tipoNotificacion' => 'CALIFICACION_ACTUALIZADA',
                'referenciaTipo' => 'LECCION',
                'referenciaId' => $idLeccion,
                'tituloNotificacion' => 'Nueva calificación disponible',
                'detalleNotificacion' => (string) ($leccion['nombreLeccion'] ?? 'Trabajo') . ' · ' . (string) ($seccion['tituloSeccion'] ?? ''),
                'urlNotificacion' => 'index.php?r=calificaciones-seccion&idSeccion=' . $idSeccion . '&idPeriodo=' . $idPeriodo,
                'asuntoEmail' => 'Nueva calificación disponible en el Campus',
                'tituloEmail' => (string) ($leccion['nombreLeccion'] ?? 'Trabajo'),
                'contextoEmail' => (string) ($seccion['nombreCurso'] ?? 'Curso') . ' - ' . (string) ($seccion['tituloSeccion'] ?? 'Materia'),
                'urlEmail' => APP_BASE_URL . '/index.php?r=calificaciones-seccion&idSeccion=' . $idSeccion . '&idPeriodo=' . $idPeriodo,
            ]);
            $cantidad = count($calificaciones);
            $_SESSION['success_message'] = $cantidad === 1
                ? 'La correccion se guardo correctamente.'
                : 'Se guardaron ' . $cantidad . ' correcciones correctamente.';
        } else {
            $_SESSION['error_message'] = 'No se pudieron guardar las correcciones.';
        }

        return $respuesta;
    }

    public static function crtGuardarCalificacion()
    {
        if (!isset($_POST['id_estudiante'], $_POST['id_seccion'], $_POST['id_modulo'], $_POST['id_curso'], $_POST['calificacion'])) {
            return null;
        }

        if (!(ControladorPermisos::esAdministrador() || ControladorPermisos::esDocente())) {
            $_SESSION['error_message'] = 'No tenes permisos para calificar.';
            return 'denied';
        }

        if (!self::puedeGestionarSeccion((int)$_POST['id_seccion'])) {
            $_SESSION['error_message'] = 'No tenes permisos para calificar esta materia.';
            return 'denied';
        }

        $leccion = ControladorLecciones::crtBuscarLeccionPorId((int) $_POST['id_modulo']);
        $idPeriodo = (int) ($leccion['id_periodo'] ?? 0);
        $periodo = $idPeriodo > 0 ? ModeloCalificaciones::mdlPeriodoPorId($idPeriodo,(int)$_POST['id_seccion']) : null;
        if (!$leccion || strtoupper((string)($leccion['tipoLeccion'] ?? '')) !== 'TAREA' || !$periodo
            || strtoupper((string)($periodo['estado'] ?? '')) !== 'ABIERTO') {
            $_SESSION['error_message'] = 'La tarea debe tener un período abierto asignado antes de cargar una nota.';
            return 'error';
        }

        $calificacion = (int) $_POST['calificacion'];
        if ($calificacion < 0 || $calificacion > 10) {
            $_SESSION['error_message'] = 'La calificacion debe estar entre 0 y 10.';
            return 'error';
        }

        $idEstudiante = (int) $_POST['id_estudiante'];
        $idSeccion = (int) $_POST['id_seccion'];
        $idCurso = (int) $_POST['id_curso'];
        $devolucion = trim((string) ($_POST['devolucion'] ?? ''));
        $calificacionPrevia = ModeloCalificaciones::mdlCalificacionPorLeccionYEstudiante($idSeccion, (int) $_POST['id_modulo'], $idEstudiante);

        $respuesta = ModeloCalificaciones::mdlGuardarCalificacion([
            'id_estudiante' => $idEstudiante,
            'id_seccion' => $idSeccion,
            'id_modulo' => (int) $_POST['id_modulo'],
            'id_curso' => $idCurso,
            'id_periodo' => $idPeriodo,
            'calificacion' => $calificacion,
            'devolucion' => $devolucion,
        ]);

        if ($respuesta === 'ok') {
            if (self::cambioCalificacion($calificacionPrevia, $calificacion, $devolucion)) {
                $seccion = ControladorLecciones::crtBuscarSeccionPorId($idSeccion);
                self::notificarCalificacionesActualizadas(ModeloCalificaciones::mdlEstudiantesPorCurso($idCurso), [$idEstudiante], [
                    'tipoNotificacion' => 'CALIFICACION_ACTUALIZADA',
                    'referenciaTipo' => 'LECCION',
                    'referenciaId' => (int) $_POST['id_modulo'],
                    'tituloNotificacion' => 'Nueva calificación disponible',
                    'detalleNotificacion' => (string) ($leccion['nombreLeccion'] ?? 'Trabajo') . ' · ' . (string) ($seccion['tituloSeccion'] ?? ''),
                    'urlNotificacion' => 'index.php?r=calificaciones-seccion&idSeccion=' . $idSeccion . '&idPeriodo=' . $idPeriodo,
                    'asuntoEmail' => 'Nueva calificación disponible en el Campus',
                    'tituloEmail' => (string) ($leccion['nombreLeccion'] ?? 'Trabajo'),
                    'contextoEmail' => (string) ($seccion['nombreCurso'] ?? 'Curso') . ' - ' . (string) ($seccion['tituloSeccion'] ?? 'Materia'),
                    'urlEmail' => APP_BASE_URL . '/index.php?r=calificaciones-seccion&idSeccion=' . $idSeccion . '&idPeriodo=' . $idPeriodo,
                ]);
            }
            $_SESSION['success_message'] = 'Calificacion guardada correctamente.';
        } else {
            $_SESSION['error_message'] = 'No se pudo guardar la calificacion.';
        }

        return $respuesta;
    }

    public static function crtCalificacionesPorSeccion($idSeccion)
    {
        return ModeloCalificaciones::mdlCalificacionesPorSeccion($idSeccion);
    }

    public static function crtCalificacionesPorEstudiante($idSeccion, $idEstudiante)
    {
        return ModeloCalificaciones::mdlCalificacionesPorEstudiante($idSeccion, $idEstudiante);
    }

    public static function crtCalificacionPorLeccionYEstudiante($idSeccion, $idLeccion, $idEstudiante)
    {
        return ModeloCalificaciones::mdlCalificacionPorLeccionYEstudiante($idSeccion, $idLeccion, $idEstudiante);
    }

    public static function crtCalificacionesPorSeccionYEstudiante($idSeccion, $idEstudiante)
    {
        return ModeloCalificaciones::mdlCalificacionPorSeccionYEstudiante($idSeccion, $idEstudiante);
    }

    public static function crtEvaluacionesPorSeccion($idSeccion)
    {
        return ModeloCalificaciones::mdlEvaluacionesPorSeccion((int) $idSeccion);
    }

    public static function crtContextoAcademicoSeccion($idSeccion)
    {
        return ModeloCalificaciones::mdlContextoAcademicoSeccion((int)$idSeccion);
    }

    public static function crtCierresPeriodo($idPeriodo,$idSeccion)
    {
        return ModeloCalificaciones::mdlCierresPeriodo((int)$idPeriodo,(int)$idSeccion);
    }

    public static function crtCierresEstudianteSeccion($idSeccion,$idEstudiante)
    {
        return ModeloCalificaciones::mdlCierresEstudianteSeccion((int)$idSeccion,(int)$idEstudiante);
    }

    public static function crtEvaluacionesPorEstudiante($idSeccion, $idEstudiante)
    {
        return ModeloCalificaciones::mdlEvaluacionesPorEstudiante((int) $idSeccion, (int) $idEstudiante);
    }

    public static function crtEstudiantesPorCurso($idCurso)
    {
        return ModeloCalificaciones::mdlEstudiantesPorCurso((int) $idCurso);
    }

    public static function crtCalificacionesEvaluacion($idEvaluacion)
    {
        return ModeloCalificaciones::mdlCalificacionesEvaluacion((int) $idEvaluacion);
    }
}
