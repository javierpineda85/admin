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
$evaluaciones = $puedeGestionar ? ControladorCalificaciones::crtEvaluacionesPorSeccion($idSeccion) : [];
$evaluacionesEstudiante = $esEstudiante && $idEstudianteContexto > 0
    ? ControladorCalificaciones::crtEvaluacionesPorEstudiante($idSeccion, $idEstudianteContexto)
    : [];
$estudiantesEvaluacion = $puedeGestionar
    ? ControladorCalificaciones::crtEstudiantesPorCurso((int) ($seccion['id_curso'] ?? 0))
    : [];

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
        <div class="card-header section-header-soft">
          <h3 class="card-title mb-0">Nueva evaluacion</h3>
        </div>
        <div class="card-body">
          <form method="post">
            <input type="hidden" name="accion" value="crear_evaluacion">
            <input type="hidden" name="id_seccion" value="<?php echo (int) $idSeccion; ?>">
            <div class="row align-items-end">
              <div class="col-md-7">
                <div class="form-group mb-md-0">
                  <label for="temaEvaluacion">Tema</label>
                  <input type="text" id="temaEvaluacion" name="temaEvaluacion" class="form-control" maxlength="180" placeholder="Ejemplo: Introduccion a bases de datos" required>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group mb-md-0">
                  <label for="fechaEvaluacion">Fecha</label>
                  <input type="date" id="fechaEvaluacion" name="fechaEvaluacion" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
              </div>
              <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-block">Crear</button>
              </div>
            </div>
          </form>
        </div>
      </div>

      <div class="card glass-card mb-4">
        <div class="card-header section-header-soft d-flex justify-content-between align-items-center">
          <h3 class="card-title mb-0">Evaluaciones independientes</h3>
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
                $notasActuales = ControladorCalificaciones::crtCalificacionesEvaluacion((int) $evaluacion['idEvaluacion']);
                $mapaNotas = [];
                foreach ($notasActuales as $notaActual) {
                    $mapaNotas[(int) $notaActual['id_estudiante']] = $notaActual;
                }
              ?>
              <section class="evaluation-management-card mb-4">
                <div class="evaluation-management-header">
                  <div>
                    <h4 class="mb-1"><?php echo $e($evaluacion['temaEvaluacion']); ?></h4>
                    <small class="text-muted"><i class="far fa-calendar mr-1"></i><?php echo $e($evaluacion['fechaEvaluacion']); ?></small>
                  </div>
                  <div class="evaluation-management-actions">
                    <div class="text-right">
                      <span class="badge badge-primary"><?php echo (int) ($evaluacion['totalCalificados'] ?? 0); ?> calificados</span>
                      <?php if ($evaluacion['promedio'] !== null): ?>
                        <span class="badge badge-light border">Promedio: <?php echo $e($evaluacion['promedio']); ?></span>
                      <?php endif; ?>
                    </div>
                    <div class="dropdown">
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
                    </div>
                  </div>
                </div>

                <div class="collapse evaluation-edit-panel" id="editar-evaluacion-<?php echo $idEvaluacionActual; ?>">
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
                      <div class="col-md-3">
                        <div class="form-group mb-md-0">
                          <label for="fechaEvaluacion<?php echo $idEvaluacionActual; ?>">Fecha</label>
                          <input
                            type="date"
                            id="fechaEvaluacion<?php echo $idEvaluacionActual; ?>"
                            name="fechaEvaluacion"
                            class="form-control"
                            value="<?php echo $e($evaluacion['fechaEvaluacion']); ?>"
                            required
                          >
                        </div>
                      </div>
                      <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-block">Guardar cambios</button>
                      </div>
                    </div>
                  </form>
                </div>

                <?php if (empty($estudiantesEvaluacion)): ?>
                  <div class="p-3 text-muted">Este curso no tiene estudiantes inscriptos.</div>
                <?php else: ?>
                  <form method="post">
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
                            <th>Devolucion opcional</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($estudiantesEvaluacion as $estudiante): ?>
                            <?php $nota = $mapaNotas[(int) $estudiante['idUsuario']] ?? []; ?>
                            <tr>
                              <td><?php echo $e(trim(($estudiante['apellidoUsuario'] ?? '') . ' ' . ($estudiante['nombreUsuario'] ?? ''))); ?></td>
                              <td>
                                <input type="number" name="calificaciones[<?php echo (int) $estudiante['idUsuario']; ?>]" class="form-control form-control-sm" min="0" max="100" step="0.01" value="<?php echo $e($nota['calificacion'] ?? ''); ?>">
                              </td>
                              <td>
                                <input type="text" name="devoluciones[<?php echo (int) $estudiante['idUsuario']; ?>]" class="form-control form-control-sm" value="<?php echo $e($nota['devolucion'] ?? ''); ?>" placeholder="Comentario para el estudiante">
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                    <div class="evaluation-grades-footer">
                      <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-save mr-1"></i>Guardar cambios de notas
                      </button>
                    </div>
                  </form>
                <?php endif; ?>
              </section>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    <?php else: ?>
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
