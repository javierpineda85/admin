<?php
$idSeccion = (int) ($_GET['idSeccion'] ?? 0);
$seccion = ControladorLecciones::crtBuscarSeccionPorId($idSeccion);
$idUsuarioActual = (int) ($_SESSION['usuario']['id'] ?? 0);
$esEstudiante = ControladorPermisos::esEstudiante();
$esDocente = ControladorPermisos::esDocente();
$esAdmin = ControladorPermisos::esAdministrador();
$puedeGestionar = $esAdmin || $esDocente;

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
    $tieneAcceso = !empty($seccion['id_curso'])
        && ControladorCursos::crtEstudianteInscriptoCurso($idUsuarioActual, (int) $seccion['id_curso']);
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
    ? ControladorCalificaciones::crtCalificacionesPorEstudiante($idSeccion, $idUsuarioActual)
    : ControladorCalificaciones::crtCalificacionesPorSeccion($idSeccion);
$evaluaciones = $puedeGestionar ? ControladorCalificaciones::crtEvaluacionesPorSeccion($idSeccion) : [];
$evaluacionesEstudiante = $esEstudiante
    ? ControladorCalificaciones::crtEvaluacionesPorEstudiante($idSeccion, $idUsuarioActual)
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
                $notasActuales = ControladorCalificaciones::crtCalificacionesEvaluacion((int) $evaluacion['idEvaluacion']);
                $mapaNotas = [];
                foreach ($notasActuales as $notaActual) {
                    $mapaNotas[(int) $notaActual['id_estudiante']] = $notaActual;
                }
              ?>
              <section class="border rounded mb-4 overflow-hidden">
                <div class="bg-light border-bottom px-3 py-3 d-flex flex-wrap justify-content-between align-items-center">
                  <div>
                    <h4 class="mb-1"><?php echo $e($evaluacion['temaEvaluacion']); ?></h4>
                    <small class="text-muted"><i class="far fa-calendar mr-1"></i><?php echo $e($evaluacion['fechaEvaluacion']); ?></small>
                  </div>
                  <div class="text-right">
                    <span class="badge badge-primary"><?php echo (int) ($evaluacion['totalCalificados'] ?? 0); ?> calificados</span>
                    <?php if ($evaluacion['promedio'] !== null): ?>
                      <span class="badge badge-light border">Promedio: <?php echo $e($evaluacion['promedio']); ?></span>
                    <?php endif; ?>
                  </div>
                </div>

                <?php if (empty($estudiantesEvaluacion)): ?>
                  <div class="p-3 text-muted">Este curso no tiene estudiantes inscriptos.</div>
                <?php else: ?>
                  <form method="post">
                    <input type="hidden" name="accion" value="guardar_calificaciones_evaluacion">
                    <input type="hidden" name="id_evaluacion" value="<?php echo (int) $evaluacion['idEvaluacion']; ?>">
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
                    <div class="border-top p-3 text-right">
                      <button type="submit" class="btn btn-primary btn-sm">Guardar calificaciones</button>
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
