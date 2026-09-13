<?php
$idSeccion = (int) ($_GET['idSeccion'] ?? 0);
$seccion = ControladorLecciones::crtBuscarSeccionPorId($idSeccion);
$idUsuarioActual = (int) ($_SESSION['usuario']['id'] ?? 0);
$rolReal = ControladorPermisos::rolReal();
$vistaEstudianteSimulada = ControladorPermisos::vistaEstudianteActiva()
    && in_array($rolReal, ['ADMINISTRADOR', 'DOCENTE'], true);
$esEstudiante = ControladorPermisos::esEstudiante();
$esDocente = ControladorPermisos::esDocente();
$esAdmin = ControladorPermisos::esAdministrador();
$puedeGestionar = $esAdmin || $esDocente;
$idEstudianteContexto = $esEstudiante ? ControladorPermisos::idEstudianteContexto() : 0;
$estudianteContexto = $vistaEstudianteSimulada && $idEstudianteContexto > 0
    ? ControladorUsuarios::crtUsuarioCompleto($idEstudianteContexto)
    : null;

ControladorCalificaciones::crtProcesarAcciones();

if (!$seccion) {
    $seccion = [
        'tituloSeccion' => 'Seccion no encontrada',
        'nombreCurso' => '',
        'nombreUsuario' => '',
        'apellidoUsuario' => '',
        'contenidoSeccion' => '',
        'bannerSeccion' => '',
        'colorInicioBanner' => '#0f172a',
        'colorFinBanner' => '#1d4ed8',
        'id_curso' => 0,
    ];
}

$tieneAcceso = true;
if ($esEstudiante) {
    if ($vistaEstudianteSimulada) {
        $tieneAcceso = $rolReal === 'ADMINISTRADOR'
            || ($rolReal === 'DOCENTE' && ControladorLecciones::crtSeccionAsignadaDocente($idSeccion, $idUsuarioActual));
    } else {
        $tieneAcceso = !empty($seccion['id_curso'])
            && ControladorCursos::crtEstudianteInscriptoCurso($idEstudianteContexto, (int) $seccion['id_curso']);
    }
} elseif ($esDocente && !$esAdmin) {
    $tieneAcceso = ControladorLecciones::crtSeccionAsignadaDocente($idSeccion, $idUsuarioActual);
}

if (!$tieneAcceso) {
    ?>
    <section class="content page-fade">
      <div class="container-fluid">
        <div class="empty-state">
          <i class="fas fa-lock"></i>
          <h4>No tenes acceso a estas calificaciones</h4>
          <p class="mb-3">Solo podes ver notas de tus materias asignadas.</p>
          <a href="index.php?r=listado-cursos" class="btn btn-primary">Volver</a>
        </div>
      </div>
    </section>
    <?php
    return;
}

$calificaciones = $esEstudiante
    ? ($idEstudianteContexto > 0
        ? ControladorCalificaciones::crtCalificacionesPorEstudiante($idSeccion, $idEstudianteContexto)
        : [])
    : ControladorCalificaciones::crtCalificacionesPorSeccion($idSeccion);
$contextoAcademico = ControladorCalificaciones::crtContextoAcademicoSeccion($idSeccion);
$periodos = $contextoAcademico['periodos'] ?? [];
$instrumentos = $contextoAcademico['instrumentos'] ?? [];
$idPeriodoActual = (int) ($_GET['idPeriodo'] ?? ($periodos[0]['idPeriodo'] ?? 0));
$periodoActual = null;
foreach ($periodos as $periodoDisponible) {
    if ((int) $periodoDisponible['idPeriodo'] === $idPeriodoActual) { $periodoActual = $periodoDisponible; break; }
}
if (!$periodoActual && $periodos) { $periodoActual = $periodos[0]; $idPeriodoActual = (int) $periodoActual['idPeriodo']; }
$periodoCerrado = strtoupper((string) ($periodoActual['estado'] ?? 'ABIERTO')) === 'CERRADO';
$evaluacionesTodas = $puedeGestionar ? ControladorCalificaciones::crtEvaluacionesPorSeccion($idSeccion) : [];
$evaluaciones = array_values(array_filter($evaluacionesTodas, static function ($evaluacion) use ($idPeriodoActual) {
    return (int) ($evaluacion['id_periodo'] ?? 0) === $idPeriodoActual;
}));
$cierresPeriodo = $puedeGestionar ? ControladorCalificaciones::crtCierresPeriodo($idPeriodoActual,$idSeccion) : [];
$cierresPorEstudiante = [];
foreach($cierresPeriodo as $cierreIndice){$cierresPorEstudiante[(int)$cierreIndice['id_estudiante']]=$cierreIndice;}
$estadisticasPeriodo = ['aprobados'=>0,'desaprobados'=>0,'sinCierre'=>0,'cierres'=>count($cierresPeriodo)];
foreach($cierresPeriodo as $cierreEstadistica){
    if($cierreEstadistica['calificacionCierre']===null||$cierreEstadistica['calificacionCierre']===''){$estadisticasPeriodo['sinCierre']++;}
    elseif((float)$cierreEstadistica['calificacionCierre']>=7){$estadisticasPeriodo['aprobados']++;}
    else{$estadisticasPeriodo['desaprobados']++;}
}
$evaluacionesEstudiante = $esEstudiante && $idEstudianteContexto > 0
    ? ControladorCalificaciones::crtEvaluacionesPorEstudiante($idSeccion, $idEstudianteContexto)
    : [];
$estudiantesEvaluacion = $puedeGestionar
    ? ControladorCalificaciones::crtEstudiantesPorCurso((int) ($seccion['id_curso'] ?? 0))
    : [];
$cierresEstudiante = $esEstudiante && $idEstudianteContexto > 0
    ? ControladorCalificaciones::crtCierresEstudianteSeccion($idSeccion,$idEstudianteContexto)
    : [];
$notasPorEvaluacion = [];
if ($puedeGestionar) {
    foreach ($evaluaciones as $evaluacionPlanilla) {
        $idEvaluacionPlanilla = (int) $evaluacionPlanilla['idEvaluacion'];
        foreach (ControladorCalificaciones::crtCalificacionesEvaluacion($idEvaluacionPlanilla) as $notaPlanilla) {
            $notasPorEvaluacion[$idEvaluacionPlanilla][(int) $notaPlanilla['id_estudiante']] = $notaPlanilla;
        }
    }
}

$e = static function ($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
};
$heroStyle = 'background: linear-gradient(135deg, ' . $e($seccion['colorInicioBanner'] ?? '#0f172a') . ', ' . $e($seccion['colorFinBanner'] ?? '#1d4ed8') . ');';
if (!empty($seccion['bannerSeccion'])) {
    $heroStyle = 'background-image: linear-gradient(135deg, rgba(15, 23, 42, 0.88), rgba(29, 78, 216, 0.74)), url(\'img/' . $e($seccion['bannerSeccion']) . '\'); background-size: cover; background-position: center;';
}
?>

<section class="content page-fade">
  <div class="container-fluid">
    <div class="entity-hero mb-4" style="<?php echo $heroStyle; ?>">
      <div class="entity-hero__content">
        <span class="entity-kicker mb-3"><?php echo $e($seccion['nombreCurso'] ?? 'Curso'); ?></span>
        <h1 class="entity-title mb-2">Calificaciones de <?php echo $e($seccion['tituloSeccion'] ?? 'Materia'); ?></h1>
        <p class="entity-lead mb-0">Notas de actividades y evaluaciones independientes en un solo lugar.</p>
      </div>
    </div>

    <?php if ($vistaEstudianteSimulada): ?>
      <div class="student-preview-context mb-4">
        <i class="fas fa-eye"></i>
        <div>
          <?php if ($estudianteContexto): ?>
            <strong>Calificaciones de <?php echo $e(trim(($estudianteContexto['nombreUsuario'] ?? '') . ' ' . ($estudianteContexto['apellidoUsuario'] ?? ''))); ?></strong>
            <span>Esta previsualización usa la información académica real del estudiante seleccionado.</span>
          <?php else: ?>
            <strong>No hay un estudiante seleccionado</strong>
            <span>Elegí un estudiante para visualizar sus notas y devoluciones.</span>
          <?php endif; ?>
        </div>
        <a href="index.php?r=listado-cursos" class="btn btn-light border btn-sm">Cambiar estudiante</a>
      </div>
    <?php endif; ?>

    <?php if ($puedeGestionar): ?>
      <div class="card glass-card mb-4">
        <div class="card-body">
          <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <div><strong><?php echo $e($contextoAcademico['ciclo']['nombre'] ?? 'Ciclo lectivo'); ?></strong><small class="text-muted d-block">Seleccioná un período para administrar sus evaluaciones.</small></div>
            <span class="badge badge-<?php echo $periodoCerrado ? 'secondary' : 'success'; ?> px-3 py-2"><i class="fas fa-<?php echo $periodoCerrado ? 'lock' : 'lock-open'; ?> mr-1"></i><?php echo $periodoCerrado ? 'Período cerrado' : 'Período abierto'; ?></span>
          </div>
          <div class="nav nav-tabs mb-3">
            <?php foreach ($periodos as $periodo): ?>
              <a class="nav-item nav-link <?php echo (int)$periodo['idPeriodo']===$idPeriodoActual?'active':''; ?>" href="index.php?r=calificaciones-seccion&idSeccion=<?php echo (int)$idSeccion; ?>&idPeriodo=<?php echo (int)$periodo['idPeriodo']; ?>">
                <i class="fas fa-<?php echo strtoupper((string)$periodo['estado'])==='CERRADO'?'lock':'lock-open'; ?> mr-1"></i><?php echo $e($periodo['nombre']); ?>
              </a>
            <?php endforeach; ?>
          </div>
          <div class="d-flex flex-wrap">
            <?php if (!$periodoCerrado): ?>
              <button type="button" class="btn btn-primary btn-sm mr-2 mb-2" data-toggle="modal" data-target="#nuevaEvaluacionModal"><i class="fas fa-plus mr-1"></i>Agregar evaluación</button>
              <form method="post" class="mr-2 mb-2"><input type="hidden" name="accion" value="calcular_cierre_periodo"><input type="hidden" name="id_seccion" value="<?php echo (int)$idSeccion; ?>"><input type="hidden" name="id_periodo" value="<?php echo $idPeriodoActual; ?>"><button class="btn btn-success btn-sm"><i class="fas fa-calculator mr-1"></i>Calcular cierre</button></form>
              <form method="post" class="mb-2" onsubmit="return confirm('Al cerrar el período ya no se podrán modificar evaluaciones ni notas. ¿Continuar?');">
                <input type="hidden" name="accion" value="cambiar_estado_periodo"><input type="hidden" name="id_seccion" value="<?php echo (int)$idSeccion; ?>"><input type="hidden" name="id_periodo" value="<?php echo $idPeriodoActual; ?>"><input type="hidden" name="estado_periodo" value="CERRADO">
                <button class="btn btn-outline-secondary btn-sm"><i class="fas fa-lock mr-1"></i>Cerrar período</button>
              </form>
            <?php elseif ($esAdmin): ?>
              <form method="post" class="form-inline mb-2"><input type="hidden" name="accion" value="cambiar_estado_periodo"><input type="hidden" name="id_seccion" value="<?php echo (int)$idSeccion; ?>"><input type="hidden" name="id_periodo" value="<?php echo $idPeriodoActual; ?>"><input type="hidden" name="estado_periodo" value="ABIERTO"><input class="form-control form-control-sm mr-2" name="motivoReapertura" placeholder="Motivo obligatorio" required><button class="btn btn-warning btn-sm"><i class="fas fa-unlock mr-1"></i>Reabrir</button></form>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="row mb-4">
        <?php foreach ([['Aprobados',$estadisticasPeriodo['aprobados'],'success'],['Desaprobados',$estadisticasPeriodo['desaprobados'],'danger'],['Sin cierre',$estadisticasPeriodo['sinCierre'],'warning'],['Cierres',$estadisticasPeriodo['cierres'],'info']] as $dato): ?>
          <div class="col-6 col-lg-3 mb-2"><div class="card border-left border-<?php echo $dato[2]; ?> mb-0"><div class="card-body py-3"><small class="text-muted text-uppercase"><?php echo $dato[0]; ?></small><strong class="d-block h4 mb-0"><?php echo (int)$dato[1]; ?></strong></div></div></div>
        <?php endforeach; ?>
      </div>

      <?php if (!empty($cierresPeriodo)): ?>
        <div class="card glass-card mb-4">
          <div class="card-header section-header-soft"><h3 class="card-title mb-0">Cierre del período</h3><small class="text-muted">El promedio es una sugerencia. La calificación de cierre queda a criterio del docente.</small></div>
          <form method="post"><input type="hidden" name="accion" value="guardar_cierre_periodo"><input type="hidden" name="id_seccion" value="<?php echo (int)$idSeccion; ?>"><input type="hidden" name="id_periodo" value="<?php echo $idPeriodoActual; ?>">
            <div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Estudiante</th><th>Promedio calculado</th><th style="width:190px;">Calificación de cierre</th><th>Estado</th></tr></thead><tbody>
              <?php foreach($cierresPeriodo as $cierre): $notaCierre=$cierre['calificacionCierre']; ?>
                <tr><td><?php echo $e(trim(($cierre['apellidoUsuario']??'').' '.($cierre['nombreUsuario']??''))); ?></td><td><?php echo $e(number_format((float)$cierre['promedioCalculado'],2,',','.')); ?></td><td><input type="number" class="form-control form-control-sm" min="0" max="10" step="0.01" name="cierres[<?php echo (int)$cierre['id_estudiante']; ?>]" value="<?php echo $e($notaCierre); ?>" <?php echo $periodoCerrado?'disabled':''; ?> required></td><td><span class="badge badge-<?php echo (float)$notaCierre>=7?'success':'danger'; ?>"><?php echo (float)$notaCierre>=7?'Aprobado':'Desaprobado'; ?></span><?php if((int)($cierre['confirmada']??0)!==1): ?><small class="text-muted d-block">Sugerida</small><?php endif; ?></td></tr>
              <?php endforeach; ?>
            </tbody></table></div>
            <?php if(!$periodoCerrado): ?><div class="card-footer text-right"><button class="btn btn-primary btn-sm"><i class="fas fa-check mr-1"></i>Confirmar cierres</button></div><?php endif; ?>
          </form>
        </div>
      <?php endif; ?>

      <div class="card glass-card mb-4">
        <div class="card-header section-header-soft d-flex flex-wrap justify-content-between align-items-center">
          <div><h3 class="card-title mb-0">Planilla de notas</h3><small class="text-muted">Buscá un estudiante y compará todas sus evaluaciones del período.</small></div>
          <label class="management-search mb-0"><span class="sr-only">Buscar estudiante</span><i class="fas fa-search"></i><input type="search" class="form-control form-control-sm" placeholder="Buscar estudiante..." data-gradebook-search></label>
        </div>
        <div class="card-body p-0">
          <?php if (empty($evaluaciones) || empty($estudiantesEvaluacion)): ?>
            <div class="empty-state py-4"><i class="fas fa-table"></i><p class="mb-0">La planilla aparecerá cuando existan evaluaciones y estudiantes.</p></div>
          <?php else: ?>
            <div class="table-responsive" style="max-height: 65vh;">
              <table class="table table-bordered table-sm mb-0" data-gradebook-table>
                <thead class="thead-light"><tr>
                  <th style="position:sticky;left:0;top:0;z-index:4;min-width:230px;background:#f4f6f9;">Estudiante</th>
                  <?php foreach($evaluaciones as $evaluacionColumna): ?>
                    <th class="text-center" style="top:0;position:sticky;z-index:3;min-width:125px;background:#f4f6f9;" title="<?php echo $e($evaluacionColumna['temaEvaluacion']); ?>">
                      <a href="#evaluacion-<?php echo (int)$evaluacionColumna['idEvaluacion']; ?>" class="text-dark d-block"><?php echo $e(mb_strimwidth((string)$evaluacionColumna['temaEvaluacion'],0,20,'…','UTF-8')); ?></a>
                      <small class="text-muted"><?php echo $e(date('d/m',strtotime($evaluacionColumna['fechaEvaluacion']))); ?></small>
                    </th>
                  <?php endforeach; ?>
                  <th class="text-center gradebook-closing-header" style="top:0;position:sticky;z-index:3;min-width:150px;"><i class="fas fa-flag-checkered mr-1"></i>Nota de cierre<br><small><?php echo $e($periodoActual['nombre'] ?? 'Período'); ?></small></th>
                </tr></thead>
                <tbody>
                  <?php foreach($estudiantesEvaluacion as $estudianteFila): $idEstudianteFila=(int)$estudianteFila['idUsuario']; $nombreFila=trim(($estudianteFila['apellidoUsuario']??'').' '.($estudianteFila['nombreUsuario']??'')); ?>
                    <tr data-gradebook-row data-student="<?php echo $e($nombreFila); ?>">
                      <th style="position:sticky;left:0;z-index:2;background:#fff;"><?php echo $e($nombreFila); ?></th>
                      <?php foreach($evaluaciones as $evaluacionColumna): $notaCelda=$notasPorEvaluacion[(int)$evaluacionColumna['idEvaluacion']][$idEstudianteFila]??null; ?>
                        <td class="text-center align-middle <?php echo $notaCelda && ($notaCelda['estadoAsistencia']??'')!=='AUSENTE' ? ((float)$notaCelda['calificacion']>=7?'table-success':'table-danger') : ''; ?>">
                          <?php if(($notaCelda['estadoAsistencia']??'')==='AUSENTE'): ?><span class="badge badge-warning">Ausente</span><?php elseif($notaCelda && $notaCelda['calificacion']!==''): ?><strong><?php echo $e(number_format((float)$notaCelda['calificacion'],2,',','.')); ?></strong><?php else: ?><span class="text-muted">-</span><?php endif; ?>
                        </td>
                      <?php endforeach; ?>
                      <?php
                        $cierreFila = $cierresPorEstudiante[$idEstudianteFila] ?? null;
                        $notaFinalFila = $cierreFila['calificacionCierre'] ?? null;
                      ?>
                      <td class="text-center align-middle gradebook-closing-cell">
                        <?php if($notaFinalFila!==null&&$notaFinalFila!==''): ?><strong class="gradebook-closing-grade is-<?php echo (float)$notaFinalFila>=7?'approved':'failed'; ?>"><?php echo $e(number_format((float)$notaFinalFila,2,',','.')); ?></strong><?php else: ?><span class="gradebook-closing-pending">Sin cierre</span><?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="card glass-card mb-4">
        <div class="card-header section-header-soft d-flex justify-content-between align-items-center">
          <h3 class="card-title mb-0"><?php echo $e($periodoActual['nombre'] ?? 'Evaluaciones'); ?></h3>
          <span class="badge badge-light border"><?php echo count($evaluaciones); ?> evaluaciones</span>
        </div>
        <div class="card-body">
          <?php if (empty($evaluaciones)): ?>
            <div class="empty-state py-4">
              <i class="fas fa-clipboard-list"></i>
              <h4>Todavia no hay evaluaciones</h4>
              <p class="mb-0">Crea la primera para cargar notas sin asociarla a un trabajo practico.</p>
            </div>
          <?php else: ?>
            <?php foreach ($evaluaciones as $evaluacion): ?>
              <?php
                $idEvaluacionActual = (int) $evaluacion['idEvaluacion'];
                $mapaNotas = $notasPorEvaluacion[$idEvaluacionActual] ?? [];
              ?>
              <section class="evaluation-management-card mb-4" id="evaluacion-<?php echo $idEvaluacionActual; ?>">
                <div class="evaluation-management-header">
                  <div>
                    <h4 class="mb-1"><?php echo $e($evaluacion['temaEvaluacion']); ?></h4>
                    <small class="text-muted"><i class="fas fa-clipboard-check mr-1"></i><?php echo $e($evaluacion['nombreInstrumento'] ?? 'Evaluación'); ?> · <i class="far fa-calendar ml-1 mr-1"></i><?php echo $e($evaluacion['fechaEvaluacion']); ?></small>
                  </div>
                  <div class="evaluation-management-actions">
                    <div class="text-right">
                      <span class="badge badge-primary"><?php echo (int) ($evaluacion['totalCalificados'] ?? 0); ?> calificados</span>
                      <?php if ($evaluacion['promedio'] !== null): ?>
                        <span class="badge badge-light border">Promedio: <?php echo $e($evaluacion['promedio']); ?></span>
                      <?php endif; ?>
                      <button type="button" class="btn btn-outline-primary btn-sm ml-2" data-toggle="collapse" data-target="#notas-evaluacion-<?php echo $idEvaluacionActual; ?>" aria-expanded="false"><i class="fas fa-pen mr-1"></i><?php echo $periodoCerrado?'Ver detalle':'Cargar notas'; ?></button>
                    </div>
                    <?php if (!$periodoCerrado): ?><div class="dropdown">
                      <button type="button" class="btn btn-light border btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Más opciones para <?php echo $e($evaluacion['temaEvaluacion']); ?>">
                        <i class="fas fa-ellipsis-v"></i>
                      </button>
                      <div class="dropdown-menu dropdown-menu-right shadow-sm">
                        <button
                          type="button"
                          class="dropdown-item"
                          data-toggle="collapse"
                          data-target="#editar-evaluacion-<?php echo $idEvaluacionActual; ?>"
                          aria-controls="editar-evaluacion-<?php echo $idEvaluacionActual; ?>"
                        >
                          <i class="fas fa-edit mr-2"></i>Editar evaluación
                        </button>
                        <div class="dropdown-divider"></div>
                        <button
                          type="button"
                          class="dropdown-item text-danger"
                          data-toggle="modal"
                          data-target="#eliminarEvaluacionModal"
                          data-id-evaluacion="<?php echo $idEvaluacionActual; ?>"
                          data-tema-evaluacion="<?php echo $e($evaluacion['temaEvaluacion']); ?>"
                          data-total-calificados="<?php echo (int) ($evaluacion['totalCalificados'] ?? 0); ?>"
                        >
                          <i class="fas fa-trash mr-2"></i>Eliminar evaluación
                        </button>
                      </div>
                    </div><?php endif; ?>
                  </div>
                </div>

                <?php if (!$periodoCerrado): ?><div class="collapse evaluation-edit-panel" id="editar-evaluacion-<?php echo $idEvaluacionActual; ?>">
                  <form method="post">
                    <input type="hidden" name="accion" value="editar_evaluacion">
                    <input type="hidden" name="id_evaluacion" value="<?php echo $idEvaluacionActual; ?>">
                    <div class="row align-items-end">
                      <div class="col-md-7">
                        <div class="form-group mb-md-0">
                          <label for="temaEvaluacion<?php echo $idEvaluacionActual; ?>">Tema</label>
                          <input
                            type="text"
                            id="temaEvaluacion<?php echo $idEvaluacionActual; ?>"
                            name="temaEvaluacion"
                            class="form-control"
                            maxlength="180"
                            value="<?php echo $e($evaluacion['temaEvaluacion']); ?>"
                            required
                          >
                        </div>
                      </div>
                      <div class="col-md-5">
                        <button type="submit" class="btn btn-primary btn-block">Guardar cambios</button>
                      </div>
                    </div>
                  </form>
                </div><?php endif; ?>

                <?php if (empty($estudiantesEvaluacion)): ?>
                  <div class="p-3 text-muted">Este curso no tiene estudiantes inscriptos.</div>
                <?php else: ?>
                  <form method="post" class="collapse" id="notas-evaluacion-<?php echo $idEvaluacionActual; ?>">
                    <input type="hidden" name="accion" value="guardar_calificaciones_evaluacion">
                    <input type="hidden" name="id_evaluacion" value="<?php echo (int) $evaluacion['idEvaluacion']; ?>">
                    <div class="evaluation-grades-hint">
                      <i class="fas fa-pen"></i>
                      <span>Podés cargar nuevas notas o corregir las existentes. Los campos vacíos no se modifican.</span>
                    </div>
                    <div class="table-responsive">
                      <table class="table table-hover mb-0">
                        <thead>
                          <tr>
                            <th>Estudiante</th>
                            <th style="width: 150px;">Nota</th>
                            <th style="width: 120px;">Asistencia</th><th>Devolucion opcional</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($estudiantesEvaluacion as $estudiante): ?>
                            <?php $nota = $mapaNotas[(int) $estudiante['idUsuario']] ?? []; ?>
                            <tr>
                              <td><?php echo $e(trim(($estudiante['apellidoUsuario'] ?? '') . ' ' . ($estudiante['nombreUsuario'] ?? ''))); ?></td>
                              <td>
                                <input type="number" name="calificaciones[<?php echo (int) $estudiante['idUsuario']; ?>]" class="form-control form-control-sm" min="0" max="10" step="0.01" value="<?php echo $e($nota['calificacion'] ?? ''); ?>" <?php echo $periodoCerrado?'disabled':''; ?>>
                              </td>
                              <td><label class="mb-0"><input type="checkbox" name="ausentes[]" value="<?php echo (int)$estudiante['idUsuario']; ?>" <?php echo ($nota['estadoAsistencia']??'')==='AUSENTE'?'checked':''; ?> <?php echo $periodoCerrado?'disabled':''; ?>> Ausente</label></td>
                              <td>
                                <input type="text" name="devoluciones[<?php echo (int) $estudiante['idUsuario']; ?>]" class="form-control form-control-sm" value="<?php echo $e($nota['devolucion'] ?? ''); ?>" placeholder="Comentario para el estudiante" <?php echo $periodoCerrado?'disabled':''; ?>>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                    <?php if (!$periodoCerrado): ?><div class="evaluation-grades-footer">
                      <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-save mr-1"></i>Guardar cambios de notas
                      </button>
                    </div><?php endif; ?>
                  </form>
                <?php endif; ?>
              </section>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    <?php else: ?>
      <?php if(!empty($cierresEstudiante)): ?>
        <div class="card glass-card mb-4"><div class="card-header section-header-soft"><h3 class="card-title mb-0">Cierres de período</h3></div><div class="card-body"><div class="row">
          <?php foreach($cierresEstudiante as $cierreAlumno): ?><div class="col-md-6 col-lg-3 mb-3"><div class="card border-<?php echo (float)$cierreAlumno['calificacionCierre']>=7?'success':'danger'; ?> h-100 mb-0"><div class="card-body"><small class="text-muted"><?php echo $e($cierreAlumno['nombrePeriodo']); ?></small><strong class="d-block h3 mb-0 text-<?php echo (float)$cierreAlumno['calificacionCierre']>=7?'success':'danger'; ?>"><?php echo $e(number_format((float)$cierreAlumno['calificacionCierre'],2,',','.')); ?></strong></div></div></div><?php endforeach; ?>
        </div></div></div>
      <?php endif; ?>
      <div class="card glass-card mb-4">
        <div class="card-header section-header-soft">
          <h3 class="card-title mb-0">Evaluaciones</h3>
        </div>
        <div class="card-body">
          <?php if (empty($evaluacionesEstudiante)): ?>
            <div class="empty-state py-4">
              <i class="fas fa-clipboard-check"></i>
              <h4>No hay evaluaciones calificadas</h4>
              <p class="mb-0">Tus notas apareceran aca cuando el docente las publique.</p>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover table-bordered mb-0">
                <thead><tr><th>Fecha</th><th>Tema</th><th>Nota</th><th>Devolucion</th></tr></thead>
                <tbody>
                  <?php foreach ($evaluacionesEstudiante as $evaluacion): ?>
                    <tr>
                      <td><?php echo $e($evaluacion['fechaEvaluacion']); ?></td>
                      <td><?php echo $e($evaluacion['temaEvaluacion']); ?></td>
                      <td><strong><?php echo $e($evaluacion['calificacion']); ?></strong></td>
                      <td><?php echo $e($evaluacion['devolucion'] ?: 'Sin devolucion'); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <div class="card glass-card">
      <div class="card-header section-header-soft d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Calificaciones ligadas a actividades</h3>
        <a href="index.php?r=detalle-seccion&idSeccion=<?php echo (int) $idSeccion; ?>" class="btn btn-light border btn-sm">Volver a la materia</a>
      </div>
      <div class="card-body">
        <?php if (empty($calificaciones)): ?>
          <div class="empty-state py-4">
            <i class="fas fa-star"></i>
            <h4>No hay calificaciones de actividades</h4>
            <p class="mb-0">Cuando haya trabajos calificados, se mostraran en esta seccion.</p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover table-bordered mb-0">
              <thead>
                <tr>
                  <?php if (!$esEstudiante): ?><th>Estudiante</th><?php endif; ?>
                  <th>Actividad</th><th>Tipo</th><th>Nota</th><th>Devolucion</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($calificaciones as $calificacion): ?>
                  <tr>
                    <?php if (!$esEstudiante): ?>
                      <td><?php echo $e(trim(($calificacion['apellidoUsuario'] ?? '') . ' ' . ($calificacion['nombreUsuario'] ?? ''))); ?></td>
                    <?php endif; ?>
                    <td><?php echo $e($calificacion['nombreLeccion'] ?? 'Actividad'); ?></td>
                    <td><?php echo $e($calificacion['tipoLeccion'] ?? ''); ?></td>
                    <td><strong><?php echo (int) ($calificacion['calificacion'] ?? 0); ?></strong></td>
                    <td><?php echo $e($calificacion['devolucion'] ?: 'Sin devolucion'); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php if ($puedeGestionar): ?>
  <div class="modal fade" id="nuevaEvaluacionModal" tabindex="-1" role="dialog" aria-labelledby="nuevaEvaluacionTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content"><form method="post">
      <input type="hidden" name="accion" value="crear_evaluacion"><input type="hidden" name="id_seccion" value="<?php echo (int)$idSeccion; ?>"><input type="hidden" name="id_periodo" value="<?php echo $idPeriodoActual; ?>">
      <div class="modal-header"><h5 class="modal-title" id="nuevaEvaluacionTitulo">Agregar evaluación - <?php echo $e($periodoActual['nombre'] ?? 'Período'); ?></h5><button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span>&times;</span></button></div>
      <div class="modal-body">
        <div class="form-row"><div class="form-group col-md-6"><label>Ciclo lectivo</label><input class="form-control" value="<?php echo $e($contextoAcademico['ciclo']['anio'] ?? ''); ?>" readonly></div><div class="form-group col-md-6"><label>Período</label><input class="form-control" value="<?php echo $e($periodoActual['nombre'] ?? ''); ?>" readonly></div></div>
        <div class="form-group"><label for="instrumentoEvaluacion">Instrumento de evaluación</label><select id="instrumentoEvaluacion" name="id_instrumento" class="form-control" required><option value="">Seleccionar...</option><?php foreach($instrumentos as $instrumento): ?><option value="<?php echo (int)$instrumento['idInstrumento']; ?>"><?php echo $e($instrumento['nombre']); ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label for="fechaEvaluacion">Fecha</label><input type="date" id="fechaEvaluacion" name="fechaEvaluacion" class="form-control" value="<?php echo date('Y-m-d'); ?>" required></div>
        <div class="form-group mb-0"><label for="temaEvaluacion">Tema</label><input type="text" id="temaEvaluacion" name="temaEvaluacion" class="form-control" maxlength="180" required></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-light border" data-dismiss="modal">Cancelar</button><button class="btn btn-primary">Agregar</button></div>
    </form></div></div>
  </div>
  <div class="modal fade" id="eliminarEvaluacionModal" tabindex="-1" role="dialog" aria-labelledby="eliminarEvaluacionTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <form method="post">
          <input type="hidden" name="accion" value="eliminar_evaluacion">
          <input type="hidden" name="id_evaluacion" value="" data-delete-evaluation-id>
          <div class="modal-header">
            <h5 class="modal-title" id="eliminarEvaluacionTitulo">Eliminar evaluación</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <p>Vas a eliminar <strong data-delete-evaluation-title>esta evaluación</strong>.</p>
            <div class="alert alert-warning mb-0">
              <i class="fas fa-exclamation-triangle mr-1"></i>
              También se eliminarán <strong data-delete-evaluation-count>0</strong> calificaciones asociadas. Esta acción no se puede deshacer.
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light border" data-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-danger">
              <i class="fas fa-trash mr-1"></i>Eliminar definitivamente
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    var buscadorPlanilla = document.querySelector('[data-gradebook-search]');
    if (buscadorPlanilla) {
      buscadorPlanilla.addEventListener('input', function () {
        var termino = this.value.toLocaleLowerCase().trim();
        document.querySelectorAll('[data-gradebook-row]').forEach(function (fila) {
          fila.style.display = (fila.getAttribute('data-student') || '').toLocaleLowerCase().indexOf(termino) !== -1 ? '' : 'none';
        });
      });
    }

    document.addEventListener('click', function(event) {
      var trigger = event.target.closest('[data-target="#eliminarEvaluacionModal"]');
      if (!trigger) {
        return;
      }

      var modal = document.getElementById('eliminarEvaluacionModal');
      if (!modal) {
        return;
      }

      var idInput = modal.querySelector('[data-delete-evaluation-id]');
      var title = modal.querySelector('[data-delete-evaluation-title]');
      var count = modal.querySelector('[data-delete-evaluation-count]');

      idInput.value = trigger.getAttribute('data-id-evaluacion') || '';
      title.textContent = trigger.getAttribute('data-tema-evaluacion') || 'esta evaluación';
      count.textContent = trigger.getAttribute('data-total-calificados') || '0';
    });
  </script>
<?php endif; ?>
