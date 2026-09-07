<?php
$escSidebar = static function ($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
};
$panelDetalleSeccion = $panelDetalleSeccion ?? 'todos';
?>

<?php if (in_array($panelDetalleSeccion, ['todos', 'informacion'], true)): ?>
<div class="row">
  <div class="col-12 col-lg-6 d-flex">
    <div class="card card-outline card-warning shadow-sm lesson-side-card w-100">
      <div class="card-header section-header-soft">
        <h3 class="card-title">Datos de la materia</h3>
      </div>
      <div class="card-body">
        <dl class="row mb-0">
          <dt class="col-5 text-muted">Curso</dt>
          <dd class="col-7"><?php echo $escSidebar($seccion['nombreCurso']); ?></dd>
          <dt class="col-5 text-muted">Estado</dt>
          <dd class="col-7"><span class="badge badge-success px-2 py-1"><?php echo $escSidebar($seccion['estado']); ?></span></dd>
          <dt class="col-5 text-muted">Contenido</dt>
          <dd class="col-7"><?php echo $escSidebar(trim((string) ($seccion['contenidoSeccion'] ?? '')) ?: 'Sin descripción'); ?></dd>
        </dl>
      </div>
    </div>
  </div>
  <div class="col-12 col-lg-6 d-flex">
    <div class="card card-outline card-primary shadow-sm lesson-side-card w-100">
      <div class="card-header section-header-soft">
        <h3 class="card-title">Equipo docente y autoría</h3>
      </div>
      <div class="card-body">
        <dl class="row mb-0">
          <dt class="col-5 text-muted">Docente titular</dt>
          <dd class="col-7"><?php echo $escSidebar(trim(($seccion['nombreUsuario'] ?? '') . ' ' . ($seccion['apellidoUsuario'] ?? ''))); ?></dd>
          <dt class="col-5 text-muted">Docente adjunto</dt>
          <dd class="col-7"><?php echo $escSidebar(trim(($seccion['nombreTutor'] ?? '') . ' ' . ($seccion['apellidoTutor'] ?? '')) ?: 'Sin docente adjunto'); ?></dd>
          <dt class="col-5 text-muted">Creado por</dt>
          <dd class="col-7"><?php echo $escSidebar(trim((string) ($seccion['creadorNombre'] ?? '')) ?: 'No registrado'); ?></dd>
        </dl>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if (in_array($panelDetalleSeccion, ['todos', 'administracion'], true) && ControladorPermisos::esAdministrador()): ?>
  <?php $docentesAsignables = ControladorUsuarios::crtUsuariosDocentesAsignables(); ?>
  <div class="row">
    <div class="col-12 col-lg-6 d-flex">
      <div class="card card-outline card-primary shadow-sm lesson-side-card w-100">
        <div class="card-header section-header-soft"><h3 class="card-title">Equipo docente</h3></div>
        <div class="card-body">
          <form method="post">
            <input type="hidden" name="accion_materia" value="reasignar_docentes_materia">
            <input type="hidden" name="idSeccion" value="<?php echo (int) ($seccion['idSeccion'] ?? 0); ?>">
            <div class="form-group">
              <label for="docenteTitularMateria">Docente titular</label>
              <select id="docenteTitularMateria" name="idDocente" class="custom-select custom-select-sm" required>
                <?php foreach ($docentesAsignables as $docenteAsignable): ?>
                  <option value="<?php echo (int) $docenteAsignable['idUsuario']; ?>" <?php echo (int) ($seccion['docente'] ?? 0) === (int) $docenteAsignable['idUsuario'] ? 'selected' : ''; ?>><?php echo $escSidebar(trim($docenteAsignable['nombreUsuario'] . ' ' . $docenteAsignable['apellidoUsuario'])); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="docenteAdjuntoMateria">Docente adjunto</label>
              <select id="docenteAdjuntoMateria" name="idAdjunto" class="custom-select custom-select-sm">
                <option value="">Sin docente adjunto</option>
                <?php foreach ($docentesAsignables as $docenteAsignable): ?>
                  <option value="<?php echo (int) $docenteAsignable['idUsuario']; ?>" <?php echo (int) ($seccion['tutor'] ?? 0) === (int) $docenteAsignable['idUsuario'] ? 'selected' : ''; ?>><?php echo $escSidebar(trim($docenteAsignable['nombreUsuario'] . ' ' . $docenteAsignable['apellidoUsuario'])); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-users-cog mr-1"></i>Actualizar equipo docente</button>
            <small class="form-text text-muted">El titular es obligatorio; el adjunto puede dejarse vacío.</small>
          </form>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-6 d-flex">
      <div class="card card-outline card-warning shadow-sm lesson-side-card w-100">
        <div class="card-header section-header-soft"><h3 class="card-title">Estado de la materia</h3></div>
        <div class="card-body d-flex flex-column">
          <div class="d-flex align-items-center justify-content-between border rounded p-3 mb-3">
            <strong>Estado actual</strong>
            <span class="badge badge-<?php echo (int) ($seccion['activo'] ?? 1) === 1 ? 'success' : 'danger'; ?> px-2 py-1"><?php echo (int) ($seccion['activo'] ?? 1) === 1 ? 'Activa' : 'Dada de baja'; ?></span>
          </div>
          <?php if ((int) ($seccion['activo'] ?? 1) === 1): ?>
            <form method="post" class="border rounded bg-light p-3 mb-3">
              <input type="hidden" name="accion_materia" value="baja_materia">
              <input type="hidden" name="idSeccion" value="<?php echo (int) ($seccion['idSeccion'] ?? 0); ?>">
              <div class="form-group"><label for="motivoBajaMateria">Motivo de baja</label><input id="motivoBajaMateria" type="text" name="motivoBaja" class="form-control form-control-sm" required></div>
              <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('¿Dar de baja esta materia?');"><i class="fas fa-ban mr-1"></i>Dar de baja</button>
            </form>
          <?php else: ?>
            <p class="small text-muted"><?php echo $escSidebar($seccion['motivoBaja'] ?? ''); ?></p>
            <form method="post" class="mb-3"><input type="hidden" name="accion_materia" value="reactivar_materia"><input type="hidden" name="idSeccion" value="<?php echo (int) ($seccion['idSeccion'] ?? 0); ?>"><button type="submit" class="btn btn-success btn-sm"><i class="fas fa-undo mr-1"></i>Reactivar materia</button></form>
          <?php endif; ?>
          <div class="border-top pt-3 mt-auto">
            <form method="post"><input type="hidden" name="accion_materia" value="eliminar_materia"><input type="hidden" name="idSeccion" value="<?php echo (int) ($seccion['idSeccion'] ?? 0); ?>"><button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('¿Eliminar definitivamente esta materia? Esta acción no se puede deshacer.');"><i class="fas fa-trash mr-1"></i>Eliminar definitivamente</button><small class="form-text text-muted">Esta acción no se puede deshacer.</small></form>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php if (in_array($panelDetalleSeccion, ['todos', 'seguimiento'], true) && ControladorPermisos::esEstudiante()): ?>
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

<?php if (in_array($panelDetalleSeccion, ['todos', 'seguimiento'], true) && $puedeGestionar): ?>
  <div class="row">
    <div class="col-12 col-lg-8 d-flex">
      <div class="card card-outline card-primary shadow-sm lesson-side-card w-100">
        <div class="card-header section-header-soft"><h3 class="card-title">Resumen de seguimiento</h3></div>
        <div class="card-body">
          <div class="row">
            <?php
            $metricasSeguimiento = [
                ['Recursos', 'fa-folder-open', (int) ($resumen['totalRecursos'] ?? 0)],
                ['Entregas', 'fa-clipboard-check', (int) ($resumen['totalEntregas'] ?? 0)],
                ['Pendientes de calificar', 'fa-clock', (int) ($resumen['pendientesCalificar'] ?? 0)],
                ['Promedio general', 'fa-star', $resumen['promedioNotas'] ?? 0],
            ];
            ?>
            <?php foreach ($metricasSeguimiento as $metrica): ?>
              <div class="col-12 col-sm-6 mb-3">
                <div class="border rounded p-3 h-100 d-flex align-items-center">
                  <i class="fas <?php echo $escSidebar($metrica[1]); ?> text-primary fa-lg mr-3"></i>
                  <div><div class="small text-muted"><?php echo $escSidebar($metrica[0]); ?></div><strong class="h4 mb-0"><?php echo $escSidebar($metrica[2]); ?></strong></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-4 d-flex">
      <div class="card card-outline card-primary shadow-sm lesson-side-card w-100">
        <div class="card-header section-header-soft"><h3 class="card-title">Acciones</h3></div>
        <div class="card-body">
          <p class="text-muted">Consultá el detalle de entregas y calificaciones de la materia.</p>
          <a class="btn btn-outline-primary btn-sm" href="index.php?r=calificaciones-seccion&idSeccion=<?php echo (int) ($seccion['idSeccion'] ?? 0); ?>"><i class="fas fa-clipboard-check mr-1"></i>Ver calificaciones</a>
        </div>
      </div>
    </div>
  </div>

  <div class="card card-outline card-primary shadow-sm lesson-side-card">
    <div class="card-header section-header-soft"><h3 class="card-title">Actividad reciente</h3></div>
    <div class="card-body p-0">
      <ul class="list-group list-group-flush">
        <?php foreach (array_slice((array) $leccionesGestion, 0, 3) as $leccionReciente): ?>
          <li class="list-group-item d-flex align-items-center justify-content-between flex-wrap">
            <span><i class="far fa-file-alt text-primary mr-2"></i><strong><?php echo $escSidebar($leccionReciente['nombreLeccion'] ?? 'Actividad'); ?></strong></span>
            <small class="text-muted"><?php echo (int) ($leccionReciente['totalEntregas'] ?? 0); ?> entregas</small>
          </li>
        <?php endforeach; ?>
        <?php if (empty($leccionesGestion)): ?><li class="list-group-item text-muted">Todavía no hay actividad registrada.</li><?php endif; ?>
      </ul>
    </div>
  </div>
<?php endif; ?>

<?php if (in_array($panelDetalleSeccion, ['todos', 'personas'], true) && $puedeGestionar): ?>
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
              <th class="text-center">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($estudiantesCurso as $estudiante): ?>
              <tr>
                <td><?php echo $escSidebar(($estudiante['apellidoUsuario'] ?? '') . ' ' . ($estudiante['nombreUsuario'] ?? '')); ?></td>
                <td><?php echo $escSidebar($estudiante['email'] ?? ''); ?></td>
                <td class="text-center">
                  <a class="btn btn-success btn-sm mr-1" href="index.php?r=perfil-publico&idUsuario=<?php echo (int) ($estudiante['idUsuario'] ?? 0); ?>&idSeccion=<?php echo (int) ($seccion['idSeccion'] ?? 0); ?>" title="Ver perfil" aria-label="Ver perfil de <?php echo $escSidebar(($estudiante['nombreUsuario'] ?? '') . ' ' . ($estudiante['apellidoUsuario'] ?? '')); ?>">
                    <i class="far fa-eye"></i>
                  </a>
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
