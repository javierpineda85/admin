<?php
$idUsuarioActual = (int) ($_SESSION['usuario']['id'] ?? 0);
$esAdmin = ControladorPermisos::esAdministrador();
$esDocente = ControladorPermisos::esDocente();
$materias = $esDocente && !$esAdmin
    ? ControladorMaterias::crtBuscarMateriasPorDocente($idUsuarioActual)
    : ControladorMaterias::crtListarMateriasGestion();
$e = static function ($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
};
$colorSeguro = static function ($valor, $alternativa) {
    $valor = trim((string) $valor);
    return preg_match('/^#[0-9a-fA-F]{6}$/', $valor) ? $valor : $alternativa;
};
?>

<section class="content page-fade">
  <div class="container-fluid" data-management-list>
    <div class="entity-hero mb-4">
      <div class="entity-hero__content">
        <span class="entity-kicker mb-3"><?php echo $esDocente && !$esAdmin ? 'Mis secciones' : 'Secciones'; ?></span>
        <h1 class="entity-title mb-2"><?php echo $esDocente && !$esAdmin ? 'Tus aulas asignadas' : 'Gestión de secciones'; ?></h1>
        <p class="entity-lead mb-0">
          <?php echo $esDocente && !$esAdmin
            ? 'Encontrá rápidamente cada aula donde participás como docente o tutor.'
            : 'Visualizá las aulas por curso, docente y actividad sin perder las opciones administrativas.'; ?>
        </p>
      </div>
    </div>

    <div class="management-list-header mb-3">
      <div>
        <div class="section-title"><?php echo $esDocente && !$esAdmin ? 'Secciones a tu cargo' : 'Secciones registradas'; ?></div>
        <div class="section-subtitle"><span data-management-count><?php echo count($materias); ?></span> secciones disponibles</div>
      </div>
      <div class="management-list-actions">
        <label class="management-search mb-0">
          <span class="sr-only">Buscar secciones</span>
          <i class="fas fa-search"></i>
          <input type="search" class="form-control form-control-sm" placeholder="Buscar sección..." data-management-search>
        </label>
        <?php if ($esAdmin): ?>
          <a href="index.php?r=crear-materia" class="btn btn-primary btn-sm">
            <i class="fas fa-plus mr-1"></i>Nueva sección
          </a>
        <?php endif; ?>
      </div>
    </div>

    <?php if (empty($materias)): ?>
      <div class="empty-state">
        <i class="fas fa-book-open"></i>
        <h4><?php echo $esDocente && !$esAdmin ? 'Todavía no tenés secciones asignadas' : 'Todavía no hay secciones registradas'; ?></h4>
        <p class="mb-0"><?php echo $esDocente && !$esAdmin ? 'Cuando te asignen un aula, aparecerá en este espacio.' : 'Creá la primera sección para comenzar a publicar clases.'; ?></p>
      </div>
    <?php else: ?>
      <div class="management-card-grid" data-management-grid>
        <?php foreach ($materias as $index => $materia): ?>
          <?php
          $docente = trim((string) (($materia['nombreUsuario'] ?? '') . ' ' . ($materia['apellidoUsuario'] ?? '')));
          $tutor = trim((string) (($materia['nombreTutor'] ?? '') . ' ' . ($materia['apellidoTutor'] ?? '')));
          $colorInicio = $colorSeguro($materia['colorInicioBanner'] ?? '', '#1e1b4b');
          $colorFin = $colorSeguro($materia['colorFinBanner'] ?? '', '#59249b');
          $textoBusqueda = implode(' ', [
            $materia['tituloSeccion'] ?? '',
            $materia['contenidoSeccion'] ?? '',
            $materia['nombreCurso'] ?? '',
            $docente,
            $tutor,
          ]);
          ?>
          <article class="management-entity-card management-entity-card--custom"
            style="--management-card-start: <?php echo $e($colorInicio); ?>; --management-card-end: <?php echo $e($colorFin); ?>;"
            data-management-card
            data-search="<?php echo $e($textoBusqueda); ?>">
            <div class="management-entity-card__top">
              <div class="management-entity-card__heading">
                <span class="management-entity-card__eyebrow">Sección</span>
                <h2><?php echo $e($materia['tituloSeccion'] ?? 'Sección'); ?></h2>
                <p><i class="fas fa-layer-group mr-1"></i><?php echo $e($materia['nombreCurso'] ?? 'Sin curso'); ?></p>
              </div>
            </div>

            <div class="management-entity-card__body">
              <p class="management-entity-card__description"><?php echo $e($materia['contenidoSeccion'] ?? 'Sin descripción cargada.'); ?></p>
              <div class="management-detail-list">
                <div>
                  <span><i class="fas fa-chalkboard-teacher"></i> Docente</span>
                  <strong><?php echo $e($docente !== '' ? $docente : 'Sin asignar'); ?></strong>
                </div>
                <?php if ($tutor !== ''): ?>
                  <div>
                    <span><i class="fas fa-user-friends"></i> Tutor</span>
                    <strong><?php echo $e($tutor); ?></strong>
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <div class="management-entity-card__footer">
              <div class="management-card-metrics">
                <span><i class="fas fa-list-ul"></i><?php echo (int) ($materia['totalLecciones'] ?? 0); ?> clases</span>
                <span><i class="fas fa-clipboard-check"></i><?php echo (int) ($materia['totalTareas'] ?? 0); ?> tareas</span>
              </div>
              <div class="management-card-actions">
                <a href="index.php?r=detalle-seccion&idSeccion=<?php echo (int) $materia['idSeccion']; ?>" class="btn btn-info btn-sm">
                  <i class="far fa-eye mr-1"></i>Abrir aula
                </a>
                <?php if ($esAdmin || $esDocente): ?>
                  <div class="dropdown">
                    <button type="button" class="btn btn-outline-secondary btn-sm management-menu-button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Más opciones">
                      <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right shadow-sm">
                      <a class="dropdown-item" href="index.php?r=editar-materia&idSeccion=<?php echo (int) $materia['idSeccion']; ?>">
                        <i class="fas fa-edit mr-2"></i>Editar sección
                      </a>
                      <a class="dropdown-item" href="index.php?r=calificaciones-seccion&idSeccion=<?php echo (int) $materia['idSeccion']; ?>">
                        <i class="fas fa-chart-line mr-2"></i>Ver calificaciones
                      </a>
                    </div>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

      <div class="empty-state d-none" data-management-empty>
        <i class="fas fa-search"></i>
        <h4>No encontramos secciones</h4>
        <p class="mb-0">Probá con otro nombre de sección, curso o docente.</p>
      </div>
    <?php endif; ?>
  </div>
</section>
