<?php
$idUsuarioActual = (int) ($_SESSION['usuario']['id'] ?? 0);
$esEstudiante = ControladorPermisos::esEstudiante();
$esDocente = ControladorPermisos::esDocente();
$esAdmin = ControladorPermisos::esAdministrador();
if ($esEstudiante) {
    $cursos = ControladorCursos::crtCursosPorEstudiante($idUsuarioActual);
} elseif ($esDocente) {
    $cursos = ControladorCursos::crtCursosPorDocente($idUsuarioActual);
} else {
    $cursos = ControladorCursos::crtListarCursos();
}
$e = static function ($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
};
?>

<section class="content page-fade">
  <div class="container-fluid">
    <?php if ($esEstudiante): ?>
      <div class="entity-hero mb-4">
        <div class="entity-hero__content">
          <span class="entity-kicker mb-3">Mis cursos</span>
          <h1 class="entity-title mb-2">Tus aulas activas</h1>
          <p class="entity-lead mb-0">Entrá al curso y desde ahí elegí la materia. Así mantenemos el tablero limpio aunque un curso tenga muchas materias.</p>
        </div>
      </div>

      <?php if (empty($cursos)): ?>
        <div class="empty-state">
          <i class="fas fa-layer-group"></i>
          <h4>Todavía no tenés cursos asignados</h4>
          <p class="mb-0">Cuando te inscriban a un curso, va a aparecer en este tablero.</p>
        </div>
      <?php else: ?>
        <div class="student-class-grid">
          <?php foreach ($cursos as $index => $curso): ?>
            <a class="student-class-card theme-<?php echo (int) ($index % 4); ?>" href="index.php?r=detalle-curso&idCurso=<?php echo (int) $curso['idCurso']; ?>">
              <div class="student-class-card__cover">
                <div>
                  <h2><?php echo $e($curso['nombreCurso'] ?? 'Curso'); ?></h2>
                  <p><?php echo $e($curso['estado'] ?? 'Activo'); ?> · <?php echo $e($curso['fInicio'] ?? ''); ?></p>
                </div>
              </div>
              <div class="student-class-card__body">
                <p><?php echo $e($curso['contenidoCurso'] ?? 'Sin descripción cargada.'); ?></p>
              </div>
              <div class="student-class-card__footer">
                <span><i class="fas fa-book-open"></i> <?php echo (int) ($curso['totalSecciones'] ?? 0); ?> materias</span>
                <span><i class="fas fa-tasks"></i> <?php echo (int) ($curso['totalLecciones'] ?? 0); ?> clases</span>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php elseif ($esDocente): ?>
      <div class="entity-hero mb-4">
        <div class="entity-hero__content">
          <span class="entity-kicker mb-3">Mis cursos</span>
          <h1 class="entity-title mb-2">Tus aulas asignadas</h1>
          <p class="entity-lead mb-0">Entrá al curso para revisar sólo las materias donde participás como docente o tutor.</p>
        </div>
      </div>

      <?php if (empty($cursos)): ?>
        <div class="empty-state">
          <i class="fas fa-book-open"></i>
          <h4>Todavía no tenés cursos asignados</h4>
          <p class="mb-0">Cuando te asignen una materia, va a aparecer acá.</p>
        </div>
      <?php else: ?>
        <div class="student-class-grid">
          <?php foreach ($cursos as $index => $curso): ?>
            <a class="student-class-card theme-<?php echo (int) ($index % 4); ?>" href="index.php?r=detalle-curso&idCurso=<?php echo (int) $curso['idCurso']; ?>">
              <div class="student-class-card__cover">
                <div>
                  <h2><?php echo $e($curso['nombreCurso'] ?? 'Curso'); ?></h2>
                  <p><?php echo $e($curso['estado'] ?? 'Activo'); ?> · <?php echo $e($curso['fInicio'] ?? ''); ?></p>
                </div>
              </div>
              <div class="student-class-card__body">
                <p><?php echo $e($curso['contenidoCurso'] ?? 'Sin descripción cargada.'); ?></p>
              </div>
              <div class="student-class-card__footer">
                <span><i class="fas fa-book-open"></i> <?php echo (int) ($curso['totalSecciones'] ?? 0); ?> materias</span>
                <span><i class="fas fa-tasks"></i> <?php echo (int) ($curso['totalLecciones'] ?? 0); ?> clases</span>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php else: ?>
      <div class="entity-hero mb-4">
        <div class="entity-hero__content">
          <span class="entity-kicker mb-3">Cursos</span>
          <h1 class="entity-title mb-2">Listado de cursos</h1>
          <p class="entity-lead mb-0">Gestioná cada curso desde una vista más limpia, con acciones claras y el mismo lenguaje visual del sistema.</p>
        </div>
      </div>

      <div class="card glass-card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="card-title mb-0">Cursos registrados</h3>
          <?php if (ControladorPermisos::esAdministrador()): ?>
            <a href="index.php?r=crear-curso" class="btn btn-primary btn-sm">Nuevo curso</a>
          <?php endif; ?>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table id="example1" class="table table-hover table-striped mb-0">
              <thead>
                <tr>
                  <th class="text-center">Curso</th>
                  <th class="text-center">Estado</th>
                  <th class="text-center">Inicio</th>
                  <th class="text-center">Horario</th>
                  <th class="text-center">Finalización</th>
                  <th class="text-center">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($cursos as $curso): ?>
                  <tr>
                    <td><?php echo $e($curso['nombreCurso'] ?? ''); ?></td>
                    <td><?php echo $e($curso['estado'] ?? ''); ?></td>
                    <td><?php echo $e($curso['fInicio'] ?? ''); ?></td>
                    <td><?php echo $e($curso['horarioCurso'] ?? ''); ?></td>
                    <td><?php echo $e($curso['fFin'] ?? ''); ?></td>
                    <td class="text-center">
                      <div class="d-inline-flex align-items-center gap-2">
                        <a href="index.php?r=detalle-curso&idCurso=<?php echo (int) $curso['idCurso']; ?>" class="btn btn-info btn-sm" title="Ver detalle">
                          <i class="far fa-eye"></i>
                        </a>
                        <?php if (ControladorPermisos::esAdministrador()): ?>
                          <a href="index.php?r=editar-curso&idCurso=<?php echo (int) $curso['idCurso']; ?>" class="btn btn-success btn-sm" title="Editar">
                            <i class="fas fa-edit"></i>
                          </a>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>
