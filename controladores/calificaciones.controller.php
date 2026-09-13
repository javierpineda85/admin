<?php
require_once('modelos/calificaciones.modelo.php');

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
        if (ControladorPermisos::esAdministrador()) {
            return true;
        }

        return ControladorPermisos::esDocente()
            && ControladorLecciones::crtSeccionAsignadaDocente((int) $idSeccion, (int) ($_SESSION['usuario']['id'] ?? 0));
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
        $periodo = ModeloCalificaciones::mdlPeriodoPorId($idPeriodo);
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
        $calificaciones = [];

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
        }

        if (empty($calificaciones)) {
            $_SESSION['error_message'] = 'Carga al menos una calificacion.';
            return 'error';
        }

        $respuesta = ModeloCalificaciones::mdlGuardarCalificacionesEvaluacion($idEvaluacion, $calificaciones);
        if ($respuesta === 'ok') {
            $_SESSION['success_message'] = 'Calificaciones guardadas correctamente.';
        } else {
            $_SESSION['error_message'] = 'No se pudieron guardar las calificaciones.';
        }

        return $respuesta;
    }

    public static function crtCambiarEstadoPeriodo()
    {
        $idPeriodo=(int)($_POST['id_periodo']??0); $idSeccion=(int)($_POST['id_seccion']??0);
        $estado=strtoupper(trim((string)($_POST['estado_periodo']??''))); $periodo=ModeloCalificaciones::mdlPeriodoPorId($idPeriodo);
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
        $respuesta=ModeloCalificaciones::mdlCambiarEstadoPeriodo($idPeriodo,$estado,(int)($_SESSION['usuario']['id']??0),$motivo);
        $_SESSION[$respuesta==='ok'?'success_message':'error_message']=$respuesta==='ok'?($estado==='CERRADO'?'Período cerrado y bloqueado.':'Período reabierto correctamente.'):'No se pudo actualizar el período.';
        return $respuesta;
    }

    public static function crtCalcularCierrePeriodo()
    {
        $idPeriodo=(int)($_POST['id_periodo']??0); $idSeccion=(int)($_POST['id_seccion']??0);
        $periodo=ModeloCalificaciones::mdlPeriodoPorId($idPeriodo);
        if(!$periodo || strtoupper((string)$periodo['estado'])!=='ABIERTO' || !self::puedeGestionarSeccion($idSeccion)){
            $_SESSION['error_message']='El período no está abierto o no tenés permisos.'; return 'denied';
        }
        $cantidad=ModeloCalificaciones::mdlCalcularCierresPeriodo($idPeriodo,$idSeccion,(int)($_SESSION['usuario']['id']??0));
        $_SESSION[$cantidad>0?'success_message':'error_message']=$cantidad>0?'Se calcularon '.$cantidad.' promedios sugeridos. Podés revisarlos antes de confirmar.':'No hay notas presentes para calcular el cierre.';
        return $cantidad>0?'ok':'error';
    }

    public static function crtGuardarCierrePeriodo()
    {
        $idPeriodo=(int)($_POST['id_periodo']??0); $idSeccion=(int)($_POST['id_seccion']??0); $periodo=ModeloCalificaciones::mdlPeriodoPorId($idPeriodo);
        if(!$periodo || strtoupper((string)$periodo['estado'])!=='ABIERTO' || !self::puedeGestionarSeccion($idSeccion)){
            $_SESSION['error_message']='El período está cerrado o no tenés permisos.'; return 'denied';
        }
        $permitidos=array_fill_keys(array_map('intval',array_column(ModeloCalificaciones::mdlCierresPeriodo($idPeriodo,$idSeccion),'id_estudiante')),true); $notas=[];
        foreach((array)($_POST['cierres']??[]) as $idEstudiante=>$valor){$normalizada=str_replace(',','.',trim((string)$valor));$idEstudiante=(int)$idEstudiante;if(!isset($permitidos[$idEstudiante])||!is_numeric($normalizada)||(float)$normalizada<0||(float)$normalizada>10){$_SESSION['error_message']='Revisá las notas de cierre. Deben estar entre 0 y 10.';return 'error';}$notas[$idEstudiante]=(float)$normalizada;}
        if(!$notas){$_SESSION['error_message']='No hay notas de cierre para guardar.';return 'error';}
        $respuesta=ModeloCalificaciones::mdlGuardarCierresPeriodo($idPeriodo,$idSeccion,$notas,(int)($_SESSION['usuario']['id']??0));
        $_SESSION[$respuesta==='ok'?'success_message':'error_message']=$respuesta==='ok'?'Calificaciones de cierre confirmadas.':'No se pudieron guardar los cierres.'; return $respuesta;
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
                'calificacion' => $notaNumerica,
                'devolucion' => trim((string) ($devoluciones[$idEstudiante] ?? '')),
            ];
        }

        if (empty($calificaciones)) {
            $_SESSION['error_message'] = 'Carga al menos una calificacion antes de guardar.';
            return 'error';
        }

        $respuesta = ModeloCalificaciones::mdlGuardarCalificaciones($calificaciones);
        if ($respuesta === 'ok') {
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

        $calificacion = (int) $_POST['calificacion'];
        if ($calificacion < 0 || $calificacion > 10) {
            $_SESSION['error_message'] = 'La calificacion debe estar entre 0 y 10.';
            return 'error';
        }

        $respuesta = ModeloCalificaciones::mdlGuardarCalificacion([
            'id_estudiante' => (int) $_POST['id_estudiante'],
            'id_seccion' => (int) $_POST['id_seccion'],
            'id_modulo' => (int) $_POST['id_modulo'],
            'id_curso' => (int) $_POST['id_curso'],
            'calificacion' => $calificacion,
            'devolucion' => trim((string) ($_POST['devolucion'] ?? '')),
        ]);

        if ($respuesta === 'ok') {
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
