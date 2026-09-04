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
      <dt class="col-5 text-muted">Creado por</dt>
      <dd class="col-7"><?php echo $escSidebar(trim((string) ($seccion['creadorNombre'] ?? '')) ?: 'No registrado'); ?></dd>
      <dt class="col-5 text-muted">Recursos</dt>
      <dd class="col-7"><?php echo (int) ($resumen['totalRecursos'] ?? 0); ?></dd>
      <dt class="col-5 text-muted">Promedio</dt>
      <dd class="col-7"><?php echo $escSidebar($resumen['promedioNotas'] ?? 0); ?></dd>
    </dl>
  </div>
</div>

<?php if (ControladorPermisos::esAdministrador()): ?>
  <?php $docentesAsignables = ControladorUsuarios::crtUsuariosDocentesAsignables(); ?>
  <div class="card card-outline card-danger shadow-sm lesson-side-card">
    <div class="card-header section-header-soft">
      <h3 class="card-title">Administrar materia</h3>
    </div>
    <div class="card-body">
      <form method="post" class="mb-3">
        <input type="hidden" name="accion_materia" value="reasignar_docentes_materia">
        <input type="hidden" name="idSeccion" value="<?php echo (int) ($seccion['idSeccion'] ?? 0); ?>">
        <div class="form-group">
          <label for="docenteTitularMateria">Docente titular</label>
          <select id="docenteTitularMateria" name="idDocente" class="custom-select custom-select-sm" required>
            <?php foreach ($docentesAsignables as $docenteAsignable): ?>
              <option value="<?php echo (int) $docenteAsignable['idUsuario']; ?>" <?php echo (int) ($seccion['docente'] ?? 0) === (int) $docenteAsignable['idUsuario'] ? 'selected' : ''; ?>>
                <?php echo $escSidebar(trim($docenteAsignable['nombreUsuario'] . ' ' . $docenteAsignable['apellidoUsuario'])); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="docenteAdjuntoMateria">Docente adjunto</label>
          <select id="docenteAdjuntoMateria" name="idAdjunto" class="custom-select custom-select-sm">
            <option value="">Sin docente adjunto</option>
            <?php foreach ($docentesAsignables as $docenteAsignable): ?>
              <option value="<?php echo (int) $docenteAsignable['idUsuario']; ?>" <?php echo (int) ($seccion['tutor'] ?? 0) === (int) $docenteAsignable['idUsuario'] ? 'selected' : ''; ?>>
                <?php echo $escSidebar(trim($docenteAsignable['nombreUsuario'] . ' ' . $docenteAsignable['apellidoUsuario'])); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm btn-block">
          <i class="fas fa-users-cog mr-1"></i>Actualizar equipo docente
        </button>
        <small class="form-text text-muted">El titular es obligatorio; el adjunto puede dejarse vacío.</small>
      </form>
      <hr>
      <?php if ((int) ($seccion['activo'] ?? 1) === 1): ?>
        <form method="post" class="mb-3">
          <input type="hidden" name="accion_materia" value="baja_materia">
          <input type="hidden" name="idSeccion" value="<?php echo (int) ($seccion['idSeccion'] ?? 0); ?>">
          <div class="form-group">
            <label for="motivoBajaMateria">Motivo de baja</label>
            <input id="motivoBajaMateria" type="text" name="motivoBaja" class="form-control form-control-sm" required>
          </div>
          <button type="submit" class="btn btn-warning btn-sm btn-block" onclick="return confirm('¿Dar de baja esta materia?');">
            <i class="fas fa-ban mr-1"></i>Dar de baja
          </button>
        </form>
      <?php else: ?>
        <div class="text-danger font-weight-bold">Materia dada de baja</div>
        <p class="small text-muted"><?php echo $escSidebar($seccion['motivoBaja'] ?? ''); ?></p>
        <form method="post" class="mb-3">
          <input type="hidden" name="accion_materia" value="reactivar_materia">
          <input type="hidden" name="idSeccion" value="<?php echo (int) ($seccion['idSeccion'] ?? 0); ?>">
          <button type="submit" class="btn btn-success btn-sm btn-block"><i class="fas fa-undo mr-1"></i>Reactivar materia</button>
        </form>
      <?php endif; ?>

      <form method="post">
        <input type="hidden" name="accion_materia" value="eliminar_materia">
        <input type="hidden" name="idSeccion" value="<?php echo (int) ($seccion['idSeccion'] ?? 0); ?>">
        <button type="submit" class="btn btn-outline-danger btn-sm btn-block" onclick="return confirm('¿Eliminar definitivamente esta materia? Esta acción no se puede deshacer.');">
          <i class="fas fa-trash mr-1"></i>Eliminar definitivamente
        </button>
      </form>
    </div>
  </div>
<?php endif; ?>

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
