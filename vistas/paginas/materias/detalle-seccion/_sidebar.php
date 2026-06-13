<?php
$escSidebar = static function ($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
};
?>

<div class="card card-outline card-success shadow-sm lesson-side-card">
  <div class="card-header section-header-soft">
    <h3 class="card-title">Detalle de la sección</h3>
  </div>
  <div class="card-body">
    <dl class="row mb-0">
      <dt class="col-5 text-muted">Curso</dt>
      <dd class="col-7"><?php echo $escSidebar($seccion['nombreCurso']); ?></dd>
      <dt class="col-5 text-muted">Estado</dt>
      <dd class="col-7"><?php echo $escSidebar($seccion['estado']); ?></dd>
      <dt class="col-5 text-muted">Docente</dt>
      <dd class="col-7"><?php echo $escSidebar(trim(($seccion['nombreUsuario'] ?? '') . ' ' . ($seccion['apellidoUsuario'] ?? ''))); ?></dd>
      <dt class="col-5 text-muted">Recursos</dt>
      <dd class="col-7"><?php echo (int) ($resumen['totalRecursos'] ?? 0); ?></dd>
      <dt class="col-5 text-muted">Promedio</dt>
      <dd class="col-7"><?php echo $escSidebar($resumen['promedioNotas'] ?? 0); ?></dd>
    </dl>
  </div>
</div>

<?php if (ControladorPermisos::esEstudiante()): ?>
  <div class="card card-outline card-warning shadow-sm lesson-side-card">
    <div class="card-header section-header-soft">
      <h3 class="card-title">Mi seguimiento</h3>
    </div>
    <div class="card-body">
      <dl class="row mb-0">
        <dt class="col-6 text-muted">Entregadas</dt>
        <dd class="col-6"><?php echo (int) ($seguimientoPersonal['entregadas'] ?? 0); ?></dd>
        <dt class="col-6 text-muted">Pendientes</dt>
        <dd class="col-6"><?php echo (int) ($seguimientoPersonal['pendientes'] ?? 0); ?></dd>
        <dt class="col-6 text-muted">Promedio</dt>
        <dd class="col-6"><?php echo $escSidebar($seguimientoPersonal['promedioNotas'] ?? 0); ?></dd>
        <dt class="col-6 text-muted">Foro</dt>
        <dd class="col-6"><?php echo (int) ($seguimientoPersonal['totalPosts'] ?? 0); ?></dd>
      </dl>
    </div>
  </div>
<?php endif; ?>

<?php if ($puedeGestionar): ?>
  <div class="card card-outline card-info shadow-sm lesson-side-card">
    <div class="card-header section-header-soft">
      <h3 class="card-title">Seguimiento docente</h3>
    </div>
    <div class="card-body">
      <dl class="row mb-0">
        <dt class="col-6 text-muted">Recursos</dt>
        <dd class="col-6"><?php echo (int) ($resumen['totalRecursos'] ?? 0); ?></dd>
        <dt class="col-6 text-muted">Entregas</dt>
        <dd class="col-6"><?php echo (int) ($resumen['totalEntregas'] ?? 0); ?></dd>
        <dt class="col-6 text-muted">Pendientes</dt>
        <dd class="col-6"><?php echo (int) ($resumen['pendientesCalificar'] ?? 0); ?></dd>
        <dt class="col-6 text-muted">Promedio</dt>
        <dd class="col-6"><?php echo $escSidebar($resumen['promedioNotas'] ?? 0); ?></dd>
      </dl>
      <div class="mt-3">
        <a class="btn btn-outline-primary btn-sm btn-block" href="index.php?r=calificaciones-seccion&idSeccion=<?php echo (int) ($seccion['idSeccion'] ?? 0); ?>">
          <i class="fas fa-clipboard-check mr-1"></i>Ver calificaciones
        </a>
      </div>
    </div>
  </div>

  <div class="card card-outline card-info shadow-sm lesson-side-card">
    <div class="card-header section-header-soft">
      <h3 class="card-title">Estudiantes del curso</h3>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0">
          <thead>
            <tr>
              <th>Estudiante</th>
              <th>Email</th>
              <th class="text-center">Mensaje</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($estudiantesCurso as $estudiante): ?>
              <tr>
                <td><?php echo $escSidebar(($estudiante['apellidoUsuario'] ?? '') . ' ' . ($estudiante['nombreUsuario'] ?? '')); ?></td>
                <td><?php echo $escSidebar($estudiante['email'] ?? ''); ?></td>
                <td class="text-center">
                  <a class="btn btn-outline-primary btn-sm" href="index.php?r=nuevo-mensaje&id_destinatario=<?php echo (int) ($estudiante['idUsuario'] ?? 0); ?>" title="Enviar mensaje">
                    <i class="fas fa-paper-plane"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($estudiantesCurso)): ?>
              <tr>
                <td colspan="3" class="text-center text-muted">Todavía no hay estudiantes asignados.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php endif; ?>
