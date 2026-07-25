<?php
$esc = static function ($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
};
$esEstudianteCalificaciones = ControladorPermisos::esEstudiante();
?>

<div class="people-panel">
  <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
    <div>
      <h3 class="mb-1">Calificaciones</h3>
      <p class="text-muted mb-0">Notas, devoluciones y seguimiento académico de esta materia.</p>
    </div>
    <a href="index.php?r=calificaciones-seccion&idSeccion=<?php echo (int) $idSeccion; ?>" class="btn btn-outline-primary btn-sm">
      <i class="fas fa-external-link-alt mr-1"></i>Vista completa
    </a>
  </div>

  <?php if (empty($misCalificaciones) && empty($misEvaluaciones) && $esEstudianteCalificaciones): ?>
    <div class="empty-state">
      <i class="fas fa-star"></i>
      <h4>Todavía no tenés notas cargadas</h4>
      <p class="mb-0">Cuando el docente cargue una calificación, la vas a ver acá.</p>
    </div>
  <?php elseif (empty($calificacionesSeccion) && !$esEstudianteCalificaciones): ?>
    <div class="empty-state">
      <i class="fas fa-star"></i>
      <h4>No hay calificaciones para mostrar</h4>
      <p class="mb-0">Cuando haya entregas corregidas, se van a ver acá.</p>
    </div>
  <?php elseif (!$esEstudianteCalificaciones): ?>
    <div class="table-responsive">
      <table class="table table-hover table-bordered mb-0">
        <thead>
          <tr>
            <th>Estudiante</th>
            <th>Actividad</th>
            <th>Tipo</th>
            <th>Nota</th>
            <th>Devolución</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($calificacionesSeccion as $calificacion): ?>
            <tr>
              <td><?php echo $esc(trim(($calificacion['apellidoUsuario'] ?? '') . ' ' . ($calificacion['nombreUsuario'] ?? ''))); ?></td>
              <td><?php echo $esc($calificacion['nombreLeccion'] ?? 'Actividad'); ?></td>
              <td><?php echo $esc($calificacion['tipoLeccion'] ?? ''); ?></td>
              <td><strong><?php echo (int) ($calificacion['calificacion'] ?? 0); ?></strong></td>
              <td><?php echo $esc($calificacion['devolucion'] ?? 'Sin devolución'); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <?php if (!empty($misCalificaciones)): ?>
      <div class="student-grades-group">
        <h4><i class="fas fa-clipboard-check mr-2"></i>Trabajos y actividades</h4>
        <div class="table-responsive">
          <table class="table table-hover table-bordered mb-0">
            <thead>
              <tr>
                <th>Actividad</th>
                <th>Tipo</th>
                <th>Nota</th>
                <th>Devolución</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($misCalificaciones as $calificacion): ?>
                <tr>
                  <td><?php echo $esc($calificacion['nombreLeccion'] ?? 'Actividad'); ?></td>
                  <td><?php echo $esc($calificacion['tipoLeccion'] ?? ''); ?></td>
                  <td><strong class="student-grade-value"><?php echo (int) ($calificacion['calificacion'] ?? 0); ?></strong></td>
                  <td><?php echo $esc(($calificacion['devolucion'] ?? '') !== '' ? $calificacion['devolucion'] : 'Sin devolución'); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($misEvaluaciones)): ?>
      <div class="student-grades-group <?php echo !empty($misCalificaciones) ? 'mt-4' : ''; ?>">
        <h4><i class="fas fa-file-signature mr-2"></i>Evaluaciones</h4>
        <div class="table-responsive">
          <table class="table table-hover table-bordered mb-0">
            <thead>
              <tr>
                <th>Fecha</th>
                <th>Tema</th>
                <th>Nota</th>
                <th>Devolución</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($misEvaluaciones as $evaluacion): ?>
                <tr>
                  <td><?php echo $esc($evaluacion['fechaEvaluacion'] ?? ''); ?></td>
                  <td><?php echo $esc($evaluacion['temaEvaluacion'] ?? 'Evaluación'); ?></td>
                  <td><strong class="student-grade-value"><?php echo $esc($evaluacion['calificacion'] ?? 0); ?></strong></td>
                  <td><?php echo $esc(($evaluacion['devolucion'] ?? '') !== '' ? $evaluacion['devolucion'] : 'Sin devolución'); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
