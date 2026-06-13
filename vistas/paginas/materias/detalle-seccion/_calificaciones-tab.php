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

  <?php if (empty($misCalificaciones) && $esEstudianteCalificaciones): ?>
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
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover table-bordered mb-0">
        <thead>
          <tr>
            <?php if (!$esEstudianteCalificaciones): ?>
              <th>Estudiante</th>
            <?php endif; ?>
            <th>Actividad</th>
            <th>Tipo</th>
            <th>Nota</th>
            <th>Devolución</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (($esEstudianteCalificaciones ? $misCalificaciones : $calificacionesSeccion) as $calificacion): ?>
            <tr>
              <?php if (!$esEstudianteCalificaciones): ?>
                <td><?php echo $esc(trim(($calificacion['apellidoUsuario'] ?? '') . ' ' . ($calificacion['nombreUsuario'] ?? ''))); ?></td>
              <?php endif; ?>
              <td><?php echo $esc($calificacion['nombreLeccion'] ?? 'Actividad'); ?></td>
              <td><?php echo $esc($calificacion['tipoLeccion'] ?? ''); ?></td>
              <td><strong><?php echo (int) ($calificacion['calificacion'] ?? 0); ?></strong></td>
              <td><?php echo $esc($calificacion['devolucion'] ?? 'Sin devolución'); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
