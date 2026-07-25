<?php
$idSeccion = (int) ($_GET['idSeccion'] ?? 0);
$accion = trim((string) ($_POST['accion'] ?? ''));

if ($accion !== '') {
  ControladorLecciones::crtProcesarAcciones();
  ControladorCalificaciones::crtProcesarAcciones();
}

$seccion = ControladorLecciones::crtBuscarSeccionPorId($idSeccion);
if ($idSeccion > 0) {
  ControladorLecciones::crtProcesarLeccionesProgramadas($idSeccion);
}
$lecciones = $idSeccion > 0 ? ControladorLecciones::crtBuscarLeccionesPorSeccion($idSeccion) : [];
$rolReal = ControladorPermisos::rolReal();
$vistaEstudianteSimulada = ControladorPermisos::vistaEstudianteActiva() && in_array($rolReal, ['ADMINISTRADOR', 'DOCENTE'], true);
$esAdmin = ControladorPermisos::esAdministrador();
$esDocente = ControladorPermisos::esDocente();
$puedeGestionar = $esAdmin || $esDocente;
$idUsuarioActual = (int) ($_SESSION['usuario']['id'] ?? 0);
$idEstudianteContexto = ControladorPermisos::esEstudiante()
  ? ControladorPermisos::idEstudianteContexto()
  : 0;
$estudianteContexto = $vistaEstudianteSimulada && $idEstudianteContexto > 0
  ? ControladorUsuarios::crtUsuarioCompleto($idEstudianteContexto)
  : null;
$lecciones = array_values(array_reverse($lecciones));
$leccionSolicitada = max(0, (int) ($_GET['abrirLeccion'] ?? 0));
$paginaLecciones = max(1, (int) ($_GET['paginaLecciones'] ?? 1));
$leccionesPorPaginaGestion = 8;
$totalLeccionesGestion = count($lecciones);
$totalPaginasLecciones = max(1, (int) ceil($totalLeccionesGestion / $leccionesPorPaginaGestion));

if ($puedeGestionar && $leccionSolicitada > 0) {
  foreach ($lecciones as $indiceLeccion => $leccionPaginada) {
    if ((int) ($leccionPaginada['idLeccion'] ?? 0) === $leccionSolicitada) {
      $paginaLecciones = (int) floor($indiceLeccion / $leccionesPorPaginaGestion) + 1;
      break;
    }
  }
}

if ($paginaLecciones > $totalPaginasLecciones) {
  $paginaLecciones = $totalPaginasLecciones;
}
$leccionesGestion = $puedeGestionar
  ? array_slice($lecciones, ($paginaLecciones - 1) * $leccionesPorPaginaGestion, $leccionesPorPaginaGestion)
  : $lecciones;
$resumen = $idSeccion > 0 ? ControladorLecciones::crtResumenSeccion($idSeccion) : [];
$estudiantesCurso = $seccion ? ControladorLecciones::crtBuscarEstudiantesCurso((int) $seccion['id_curso']) : [];
$calificacionesSeccion = $idSeccion > 0 ? ControladorCalificaciones::crtCalificacionesPorSeccion($idSeccion) : [];
$seguimientoPersonal = ControladorPermisos::esEstudiante() && $idSeccion > 0 && $idEstudianteContexto > 0
  ? ControladorLecciones::crtResumenEstudianteSeccion($idSeccion, $idEstudianteContexto)
  : [];
$misCalificaciones = ControladorPermisos::esEstudiante() && $idSeccion > 0 && $idEstudianteContexto > 0
  ? ControladorCalificaciones::crtCalificacionesPorEstudiante($idSeccion, $idEstudianteContexto)
  : [];
$misEvaluaciones = ControladorPermisos::esEstudiante() && $idSeccion > 0 && $idEstudianteContexto > 0
  ? ControladorCalificaciones::crtEvaluacionesPorEstudiante($idSeccion, $idEstudianteContexto)
  : [];
$puedeAccederDocente = !$esDocente || $esAdmin || $vistaEstudianteSimulada || ($seccion && ControladorLecciones::crtSeccionAsignadaDocente($idSeccion, $idUsuarioActual));
$bannerSeccion = (string) ($seccion['bannerSeccion'] ?? '');
$colorInicioBanner = (string) ($seccion['colorInicioBanner'] ?? '#0f172a');
$colorFinBanner = (string) ($seccion['colorFinBanner'] ?? '#1d4ed8');
$heroStyle = 'background: linear-gradient(135deg, ' . htmlspecialchars($colorInicioBanner, ENT_QUOTES, 'UTF-8') . ', ' . htmlspecialchars($colorFinBanner, ENT_QUOTES, 'UTF-8') . ');';
if ($bannerSeccion !== '') {
  $heroStyle = 'background-image: linear-gradient(135deg, rgba(15, 23, 42, 0.88), rgba(29, 78, 216, 0.74)), url(\'img/' . htmlspecialchars($bannerSeccion, ENT_QUOTES, 'UTF-8') . '\'); background-size: cover; background-position: center;';
}

$buscarCalificacion = function (array $lista, int $idLeccion, int $idEstudiante): ?array {
  foreach ($lista as $item) {
    if ((int) ($item['id_modulo'] ?? 0) === $idLeccion && (int) ($item['id_estudiante'] ?? 0) === $idEstudiante) {
      return $item;
    }
  }

  return null;
};

$renderContenidoLeccion = static function ($valor): string {
  $html = trim((string) $valor);

  if ($html === '') {
    return '<p class="text-muted mb-0">Sin contenido cargado.</p>';
  }

  $html = preg_replace('#<(script|style|iframe|object|embed|form|meta|link)\b[^>]*>.*?</\1>#is', '', $html);
  $html = preg_replace('#<((script|style|iframe|object|embed|form|meta|link)\b[^>]*)/?>#is', '', $html);
  $html = strip_tags($html, '<p><br><strong><b><em><i><u><s><span><font><div><ul><ol><li><blockquote><pre><code><h1><h2><h3><h4><h5><h6><a><img><table><thead><tbody><tr><th><td>');
  $html = preg_replace('/\s+on[a-z]+\s*=\s*(["\']).*?\1/is', '', $html);
  $html = preg_replace('/\s+(href|src)\s*=\s*(["\'])\s*javascript:.*?\2/is', '', $html);

  return $html;
};

$claseBadgeTipoLeccion = static function ($tipo): string {
  $tipo = strtoupper(trim((string) $tipo));

  if ($tipo === 'TAREA') {
    return 'lesson-type-badge lesson-type-badge--task';
  }

  if ($tipo === 'PREGUNTA') {
    return 'lesson-type-badge lesson-type-badge--question';
  }

  return 'lesson-type-badge lesson-type-badge--material';
};

$fechaProgramadaInput = static function ($valor): string {
  $timestamp = strtotime((string) $valor);
  return $timestamp ? date('Y-m-d\TH:i', $timestamp) : '';
};

$leccionProgramada = static function (array $leccion): bool {
  $estado = strtoupper((string) ($leccion['estadoLeccion'] ?? 'PUBLICADA'));
  $fecha = trim((string) ($leccion['fechaPublicacionLeccion'] ?? ''));
  return $estado === 'PUBLICADA' && $fecha !== '' && strtotime($fecha) > time();
};

$fechaProgramadaTexto = static function ($valor): string {
  $timestamp = strtotime((string) $valor);
  return $timestamp ? date('d/m/Y H:i', $timestamp) : '';
};

if (ControladorPermisos::esDocente() && !$puedeAccederDocente) {
?>
  <section class="content page-fade">
    <div class="container-fluid">
      <div class="empty-state">
        <i class="fas fa-lock"></i>
        <h4>No tenés acceso a esta materia</h4>
        <p class="mb-3">Solo podés administrar materias donde figurás como docente o tutor.</p>
        <a href="index.php?r=listado-materias" class="btn btn-primary">Volver a mis materias</a>
      </div>
    </div>
  </section>
<?php
  return;
}

if (!$seccion) {
  $seccion = [
    'tituloSeccion' => 'Seccion no encontrada',
    'contenidoSeccion' => '',
    'nombreCurso' => '',
    'estado' => '',
    'nombreUsuario' => '',
    'apellidoUsuario' => '',
    'id_curso' => 0,
    'idSeccion' => $idSeccion,
  ];
}

if (ControladorPermisos::esEstudiante()) {
  $estaInscripto = $vistaEstudianteSimulada || (!empty($seccion['id_curso'])
    && ControladorCursos::crtEstudianteInscriptoCurso($idUsuarioActual, (int) $seccion['id_curso']));
  $e = static function ($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
  };
  $tareasPendientes = 0;
  $proximasTareas = [];

  foreach ($lecciones as $leccionPendiente) {
    if (strtoupper((string) ($leccionPendiente['tipoLeccion'] ?? '')) !== 'TAREA') {
      continue;
    }

    $entregaPendiente = $idEstudianteContexto > 0
      ? ControladorLecciones::crtBuscarEntregaPorLeccionEstudiante((int) $leccionPendiente['idLeccion'], $idEstudianteContexto)
      : null;
    if (!$entregaPendiente) {
      $tareasPendientes++;
      $proximasTareas[] = $leccionPendiente;
    }
  }
?>

  <section class="content page-fade">
    <div class="container-fluid student-classroom">
      <?php if (!$estaInscripto && !$vistaEstudianteSimulada): ?>
        <div class="empty-state">
          <i class="fas fa-lock"></i>
          <h4>No tenés acceso a esta materia</h4>
          <p class="mb-3">Solo podés entrar a materias de tus cursos asignados.</p>
          <a href="index.php?r=listado-cursos" class="btn btn-primary">Volver a mis cursos</a>
        </div>
      <?php else: ?>
        <div class="student-course-hero student-course-hero--subject mb-3" style="<?php echo $heroStyle; ?>">
          <div class="student-course-hero__content">
            <span class="entity-kicker mb-3"><?php echo $e($seccion['nombreCurso'] ?? 'Curso'); ?></span>
            <h1><?php echo $e($seccion['tituloSeccion'] ?? 'Materia'); ?></h1>
            <p><?php echo $e(trim(($seccion['nombreUsuario'] ?? '') . ' ' . ($seccion['apellidoUsuario'] ?? ''))); ?></p>
          </div>
        </div>

        <?php if ($vistaEstudianteSimulada): ?>
          <div class="student-preview-context mb-3">
            <i class="fas fa-eye"></i>
            <div>
              <?php if ($estudianteContexto): ?>
                <strong>Vista de <?php echo $e(trim(($estudianteContexto['nombreUsuario'] ?? '') . ' ' . ($estudianteContexto['apellidoUsuario'] ?? ''))); ?></strong>
                <span>Las entregas y calificaciones corresponden a este estudiante.</span>
              <?php else: ?>
                <strong>Vista general, sin estudiante seleccionado</strong>
                <span>Seleccioná un estudiante para comprobar sus entregas y calificaciones.</span>
              <?php endif; ?>
            </div>
            <a href="index.php?r=listado-cursos" class="btn btn-light border btn-sm">Cambiar estudiante</a>
          </div>
        <?php endif; ?>

        <ul class="nav nav-tabs classroom-tabs mb-4" role="tablist">
          <li class="nav-item">
            <a class="nav-link active" data-toggle="tab" href="#tablon" role="tab">Novedades</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#trabajo-clase" role="tab">Trabajo de clase</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#personas" role="tab">Personas</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#calificaciones" role="tab">Calificaciones</a>
          </li>
        </ul>

        <div class="tab-content">
          <div class="tab-pane fade show active" id="tablon" role="tabpanel">
            <div class="row">
              <div class="col-lg-3 mb-4">
                <div class="classroom-sidebox">
                  <h3>Próximas entregas</h3>
                  <?php if ($tareasPendientes === 0): ?>
                    <p>No tenés tareas pendientes por ahora.</p>
                  <?php else: ?>
                    <p><?php echo (int) $tareasPendientes; ?> tarea<?php echo $tareasPendientes === 1 ? '' : 's'; ?> pendiente<?php echo $tareasPendientes === 1 ? '' : 's'; ?>.</p>
                  <?php endif; ?>
                  <a href="#trabajo-clase" data-toggle="tab">Ver todo</a>
                </div>
              </div>
              <div class="col-lg-9">
                <div class="classroom-announcement mb-3">
                  <div class="classroom-avatar"><?php echo strtoupper(substr((string) ($seccion['tituloSeccion'] ?? 'M'), 0, 1)); ?></div>
                  <div>
                    <strong><?php echo $e($seccion['tituloSeccion'] ?? 'Materia'); ?></strong>
                    <p class="mb-0"><?php echo nl2br($e($seccion['contenidoSeccion'] ?? 'Todavía no hay novedades publicadas.')); ?></p>
                  </div>
                </div>

                <?php foreach (array_slice($lecciones, 0, 5) as $leccionStream): ?>
                  <a class="classroom-stream-item" href="#trabajo-clase" data-toggle="tab">
                    <span class="classroom-item-icon"><i class="fas fa-file-alt"></i></span>
                    <div>
                      <strong><?php echo $e($leccionStream['nombreLeccion'] ?? 'Clase'); ?></strong>
                      <small><?php echo $e($leccionStream['tipoLeccion'] ?? 'MATERIAL'); ?> · <?php echo (int) ($leccionStream['totalRecursos'] ?? 0); ?> recursos</small>
                    </div>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        <div class="tab-pane fade" id="trabajo-clase" role="tabpanel">
          <?php if (empty($lecciones)): ?>
            <div class="empty-state">
              <i class="fas fa-tasks"></i>
              <h4>Todavía no hay trabajo de clase</h4>
              <p class="mb-0">Cuando el docente publique materiales, tareas o preguntas, aparecerán acá.</p>
            </div>
          <?php endif; ?>

          <div class="classwork-list">
            <?php foreach ($lecciones as $leccion): ?>
              <?php
              $tipoLeccion = strtoupper((string) ($leccion['tipoLeccion'] ?? 'MATERIAL'));
              $recursos = ControladorLecciones::crtBuscarRecursosPorLeccion((int) $leccion['idLeccion']);
              $posts = $tipoLeccion === 'PREGUNTA' ? ControladorLecciones::crtBuscarPostsPorLeccion((int) $leccion['idLeccion']) : [];
              $entrega = $tipoLeccion === 'TAREA'
                ? ($idEstudianteContexto > 0
                  ? ControladorLecciones::crtBuscarEntregaPorLeccionEstudiante((int) $leccion['idLeccion'], $idEstudianteContexto)
                  : null)
                : null;
              $notaEntrega = $tipoLeccion === 'TAREA'
                ? $buscarCalificacion($calificacionesSeccion, (int) $leccion['idLeccion'], $idEstudianteContexto)
                : null;
              $collapseId = 'leccion-estudiante-' . (int) $leccion['idLeccion'];
              ?>
              <div class="classwork-item">
                <button class="classwork-summary" type="button" data-toggle="collapse" data-target="#<?php echo $collapseId; ?>" aria-expanded="false">
                  <span class="classroom-item-icon">
                    <i class="<?php echo $tipoLeccion === 'TAREA' ? 'fas fa-clipboard-list' : ($tipoLeccion === 'PREGUNTA' ? 'fas fa-comments' : 'fas fa-book-open'); ?>"></i>
                  </span>
                  <span class="classwork-summary__main">
                    <strong><?php echo $e($leccion['nombreLeccion'] ?? 'Clase'); ?></strong>
                    <small><span class="<?php echo $claseBadgeTipoLeccion($tipoLeccion); ?>"><?php echo $e($tipoLeccion); ?></span> · <?php echo (int) ($leccion['totalRecursos'] ?? 0); ?> recursos</small>
                  </span>
                  <span class="classwork-status">
                    <?php if ($tipoLeccion === 'TAREA' && $entrega): ?>
                      Entregado
                    <?php elseif ($tipoLeccion === 'TAREA'): ?>
                      Pendiente
                    <?php else: ?>
                      Sin fecha
                    <?php endif; ?>
                  </span>
                </button>
                <div id="<?php echo $collapseId; ?>" class="collapse">
                  <div class="classwork-detail">
                    <div class="classwork-rich-content"><?php echo $renderContenidoLeccion($leccion['contenidoLeccion'] ?? ''); ?></div>

                    <?php if (!empty($recursos)): ?>
                      <div class="resource-grid mb-3">
                        <?php foreach ($recursos as $recurso): ?>
                          <a class="resource-pill" href="<?php echo $e($recurso['urlRecurso'] ?? '#'); ?>" target="_blank" rel="noopener noreferrer">
                            <i class="fas fa-paperclip"></i>
                            <span>
                              <strong><?php echo $e($recurso['tituloRecurso'] ?? 'Recurso'); ?></strong>
                              <small><?php echo $e($recurso['tipoRecurso'] ?? ''); ?></small>
                            </span>
                          </a>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>

                    <?php if ($tipoLeccion === 'TAREA'): ?>
                      <?php if ($entrega): ?>
                        <div class="alert alert-success border-0">
                          <div class="d-flex justify-content-between align-items-start flex-wrap">
                            <div>
                              <strong>Entregaste esta tarea.</strong>
                              <ul class="list-unstyled small mt-1 mb-1">
                                <?php foreach ((array) ($entrega['adjuntos'] ?? []) as $indiceAdjunto => $adjunto): ?>
                                  <li>
                                    <a href="<?php echo $e($adjunto['rutaArchivo'] ?? '#'); ?>" target="_blank" rel="noopener noreferrer">
                                      <i class="fas fa-paperclip mr-1"></i><?php echo $e($adjunto['nombreOriginal'] ?? ('Archivo ' . ($indiceAdjunto + 1))); ?>
                                    </a>
                                  </li>
                                <?php endforeach; ?>
                              </ul>
                              <?php if (!empty($entrega['comentarioEntrega'])): ?>
                                <div class="small"><strong>Comentario:</strong> <?php echo $e($entrega['comentarioEntrega']); ?></div>
                              <?php endif; ?>
                            </div>
                            <?php if ($notaEntrega): ?>
                              <span class="badge badge-primary">Nota: <?php echo (int) ($notaEntrega['calificacion'] ?? 0); ?></span>
                            <?php endif; ?>
                          </div>
                          <?php if (!empty($notaEntrega['devolucion'] ?? '')): ?>
                            <hr class="my-2">
                            <div class="small">
                              <strong>Devolución:</strong> <?php echo $e($notaEntrega['devolucion']); ?>
                            </div>
                          <?php endif; ?>
                        </div>
                      <?php endif; ?>
                      <?php if (!$notaEntrega && !$vistaEstudianteSimulada): ?>
                        <form method="post" enctype="multipart/form-data" class="row">
                          <input type="hidden" name="accion" value="entregar_tarea">
                          <input type="hidden" name="id_leccion" value="<?php echo (int) $leccion['idLeccion']; ?>">
                          <input type="hidden" name="id_seccion" value="<?php echo (int) $seccion['idSeccion']; ?>">
                          <input type="hidden" name="id_curso" value="<?php echo (int) $seccion['id_curso']; ?>">
                          <div class="form-group col-md-5">
                            <label class="small text-muted">Archivos</label>
                            <div class="classroom-file">
                              <input type="file" class="classroom-file__input" id="archivoEntregaLeccion<?php echo (int) $leccion['idLeccion']; ?>" name="archivoEntrega[]" multiple <?php echo $entrega ? '' : 'required'; ?>>
                              <label class="classroom-file__button" for="archivoEntregaLeccion<?php echo (int) $leccion['idLeccion']; ?>">
                                <i class="fas fa-paperclip mr-2"></i>Seleccionar archivos
                              </label>
                              <span class="classroom-file__name">Ningún archivo seleccionado</span>
                            </div>
                            <?php if ($entrega): ?>
                              <small class="form-text text-muted">Si elegís archivos nuevos, reemplazarán los actuales.</small>
                            <?php endif; ?>
                          </div>
                          <div class="form-group col-md-7">
                            <label class="small text-muted">Comentario</label>
                            <input type="text" name="comentarioEntrega" class="form-control form-control-sm" placeholder="Opcional" value="<?php echo $e((string) ($entrega['comentarioEntrega'] ?? '')); ?>">
                          </div>
                          <div class="form-group col-12 mb-0 text-right">
                            <button type="submit" class="btn btn-primary btn-sm"><?php echo $entrega ? 'Actualizar entrega' : 'Enviar entrega'; ?></button>
                          </div>
                        </form>
                      <?php elseif ($notaEntrega): ?>
                        <div class="alert alert-light border mt-3 mb-0">
                          Esta entrega ya fue calificada, por eso queda bloqueada para cambios.
                        </div>
                      <?php elseif ($vistaEstudianteSimulada): ?>
                        <div class="alert alert-light border mt-3 mb-0">
                          Las acciones de entrega están desactivadas durante la previsualización.
                        </div>
                      <?php endif; ?>
                      <?php if ($entrega && empty($notaEntrega) && !$vistaEstudianteSimulada): ?>
                        <form method="post" class="text-right mt-2">
                          <input type="hidden" name="accion" value="cancelar_entrega">
                          <input type="hidden" name="id_leccion" value="<?php echo (int) $leccion['idLeccion']; ?>">
                          <input type="hidden" name="id_seccion" value="<?php echo (int) $seccion['idSeccion']; ?>">
                          <input type="hidden" name="id_curso" value="<?php echo (int) $seccion['id_curso']; ?>">
                          <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Cancelar la entrega?');">Cancelar entrega</button>
                        </form>
                      <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($tipoLeccion === 'PREGUNTA'): ?>
                      <div class="forum-thread mb-3">
                        <?php foreach ($posts as $post): ?>
                          <div class="forum-post">
                            <strong><?php echo $e(($post['apellidoUsuario'] ?? '') . ' ' . ($post['nombreUsuario'] ?? '')); ?></strong>
                            <small><?php echo $e($post['fechaPosteo'] ?? ''); ?></small>
                            <p><?php echo nl2br($e($post['contenidoPosteo'] ?? '')); ?></p>
                          </div>
                        <?php endforeach; ?>
                        <?php if (empty($posts)): ?>
                          <div class="text-muted small">Todavía no hay comentarios.</div>
                        <?php endif; ?>
                      </div>
                      <?php if (!$vistaEstudianteSimulada): ?>
                        <form method="post">
                          <input type="hidden" name="accion" value="crear_post">
                          <input type="hidden" name="id_leccion" value="<?php echo (int) $leccion['idLeccion']; ?>">
                          <input type="hidden" name="id_curso" value="<?php echo (int) $seccion['id_curso']; ?>">
                          <div class="form-group">
                            <label class="small text-muted">Tu respuesta</label>
                            <textarea name="contenidoPosteo" rows="3" class="form-control form-control-sm" required></textarea>
                          </div>
                          <div class="text-right">
                            <button type="submit" class="btn btn-primary btn-sm">Publicar</button>
                          </div>
                        </form>
                      <?php endif; ?>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="tab-pane fade" id="personas" role="tabpanel">
          <?php include __DIR__ . '/detalle-seccion/_personas.php'; ?>

          <div class="people-panel mt-3 d-none">
            <h3>Calificaciones</h3>
            <p class="text-muted mb-2">Podés ver el detalle completo de tus notas y devoluciones en una vista aparte.</p>
            <a href="index.php?r=calificaciones-seccion&idSeccion=<?php echo (int) $idSeccion; ?>" class="btn btn-outline-primary btn-sm">Ver calificaciones</a>
          </div>
        </div>
        <div class="tab-pane fade" id="calificaciones" role="tabpanel">
          <?php include __DIR__ . '/detalle-seccion/_calificaciones-tab.php'; ?>
        </div>
    </div>
  <?php endif; ?>
  </div>
  </section>

<?php
  return;
}
?>

<section class="content page-fade">
  <div class="container-fluid">
    <div class="entity-hero mb-4" style="<?php echo $heroStyle; ?>">
      <div class="entity-hero__content">
        <span class="entity-kicker mb-3">Secciones</span>
        <h1 class="entity-title mb-2"><?php echo htmlspecialchars($seccion['tituloSeccion'], ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="entity-lead mb-0"><?php echo htmlspecialchars($seccion['nombreCurso'], ENT_QUOTES, 'UTF-8'); ?> · Aula, recursos, tareas y discusión en una sola vista.</p>
        <div class="text-muted small">
          <p> Docente: <?php echo htmlspecialchars(trim(($seccion['nombreUsuario'] ?? '') . ' ' . ($seccion['apellidoUsuario'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></p>

        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-12"></div>
    </div>

    <div class="row">
      <div class="col-12 col-lg-8">
        <!-- <div class="card card-outline card-primary shadow-sm">
          <div class="card-header d-flex align-items-center justify-content-between">
            <div>
              <h3 class="card-title mb-1">Aula de <?php echo htmlspecialchars($seccion['tituloSeccion'], ENT_QUOTES, 'UTF-8'); ?></h3>
              <div class="text-muted small">
                <?php echo htmlspecialchars($seccion['nombreCurso'], ENT_QUOTES, 'UTF-8'); ?> ·
                Docente: <?php echo htmlspecialchars(trim(($seccion['nombreUsuario'] ?? '') . ' ' . ($seccion['apellidoUsuario'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>
              </div>
            </div>
            <span class="badge badge-light border px-3 py-2"><?php echo htmlspecialchars(ControladorPermisos::etiquetaRol(), ENT_QUOTES, 'UTF-8'); ?></span>
          </div>
          <div class="card-body">
            <p class="mb-0 text-muted">
              <?php echo nl2br(htmlspecialchars((string) $seccion['contenidoSeccion'], ENT_QUOTES, 'UTF-8')); ?>
            </p>
          </div>
        </div> -->

        <div class="row">
          <div class="col-md-3 col-6">
            <div class="small-box bg-info">
              <div class="inner">
                <h3><?php echo (int) ($resumen['totalLecciones'] ?? 0); ?></h3>
                <p>Lecciones</p>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-6">
            <div class="small-box bg-success">
              <div class="inner">
                <h3><?php echo (int) ($resumen['totalMateriales'] ?? 0); ?></h3>
                <p>Materiales</p>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-6">
            <div class="small-box bg-warning">
              <div class="inner">
                <h3><?php echo (int) ($resumen['totalTareas'] ?? 0); ?></h3>
                <p>Tareas</p>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-6">
            <div class="small-box bg-danger">
              <div class="inner">
                <h3><?php echo (int) ($resumen['totalPreguntas'] ?? 0); ?></h3>
                <p>Preguntas</p>
              </div>
            </div>
          </div>
        </div>

        <?php if ($puedeGestionar): ?>
          <div class="card card-outline card-primary shadow-sm lesson-builder-card mb-3 collapsed-card">
            <div class="card-header section-header-soft lesson-builder-header">
              <h3 class="card-title mb-0">Crear lección</h3>
              <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                  <i class="fas fa-plus"></i>
                </button>
              </div>
            </div>
            <div class="card-body" style="display: none;">
              <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="accion" value="crear_leccion">
                <input type="hidden" name="id_modulo" value="<?php echo (int) $idSeccion; ?>">
                <div class="row">
                  <div class="form-group col-sm-12 col-md-9">
                    <label class="small text-muted">Nombre</label>
                    <input type="text" name="nombreLeccion" class="form-control form-control-sm" placeholder="Ej. Introduccion al tema" required>
                  </div>
	                  <div class="form-group col-sm-12 col-md-3">
	                    <label class="small text-muted">Tipo</label>
	                    <select name="tipoLeccion" class="form-control form-control-sm" required>
	                      <option value="MATERIAL">Material</option>
	                      <option value="TAREA">Tarea</option>
	                      <option value="PREGUNTA">Pregunta</option>
	                    </select>
	                  </div>
	                  <div class="form-group col-sm-12 col-md-4">
	                    <label class="small text-muted">Programar publicacion</label>
	                    <input type="datetime-local" name="fechaPublicacionLeccion" class="form-control form-control-sm">
	                    <small class="form-text text-muted">Si queda vacio, se publica al momento.</small>
	                  </div>
	                </div>

                <div class="form-group">
                  <label class="small text-muted">Contenido</label>
                  <textarea name="contenidoLeccion" rows="5" class="form-control form-control-sm summernote-leccion" placeholder="Resumen, instrucciones o consigna..."></textarea>
                </div>
                <div class="lesson-attachment-box mb-3" data-resource-uploader>
                  <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
                    <div>
                      <div class="text-uppercase small font-weight-bold">Adjuntar recursos</div>
                      <div class="small text-muted">Agrega varios archivos y enlaces antes de guardar la leccion.</div>
                    </div>
                    <input type="text" name="tituloRecursoInicial" class="form-control form-control-sm lesson-resource-title" placeholder="Prefijo opcional, ej. Unidad 1">
                  </div>
                  <div class="resource-attachment-list d-none" data-attachment-list></div>
                  <input type="file" class="resource-file-input d-none" id="archivoRecursoInicialMultiple<?php echo (int) $idSeccion; ?>" name="archivoRecursoInicial[]" multiple data-file-input>
                  <div class="resource-link-list" data-link-list></div>
                  <div class="lesson-attach-actions">
                    <button type="button" class="lesson-attach-button" data-trigger-file>
                      <span><i class="fas fa-upload"></i></span>
                      <strong>Subir archivo</strong>
                    </button>
                    <button type="button" class="lesson-attach-button" data-add-link>
                      <span><i class="fas fa-link"></i></span>
                      <strong>Enlace</strong>
                    </button>
                  </div>
                </div>
                <div class="border rounded p-3 mb-3 bg-light d-none">
                  <fieldset disabled>
                  <div class="text-uppercase small mb-3">Recurso inicial opcional</div>
                  <div class="row">


                    <div class="form-group col-sm-12 col-md-9">
                      <label class="small text-muted">Tí­tulo del recurso</label>
                      <input type="text" name="tituloRecursoInicial" class="form-control form-control-sm" placeholder="Ej. Apunte de la clase">
                    </div>
                    <div class="form-group col-sm-12 col-md-3 d-none">
                      <label class="small text-muted">Tipo</label>
                      <select name="tipoRecursoInicial" class="form-control form-control-sm">
                        <option value="">Sin recurso</option>
                        <option value="ARCHIVO">Archivo adjunto</option>
                        <option value="ENLACE">Enlace externo</option>
                      </select>
                    </div>
                    <div class="form-group col-sm-12 col-md-6">
                      <label class="small text-muted">Archivo adjunto</label>
                      <div class="classroom-file">
                        <input type="file" class="classroom-file__input" id="archivoRecursoInicialSeccion<?php echo (int) $idSeccion; ?>" name="archivoRecursoInicial[]" multiple>
                        <label class="classroom-file__button" for="archivoRecursoInicialSeccion<?php echo (int) $idSeccion; ?>">
                          <i class="fas fa-paperclip mr-2"></i>Seleccionar archivo
                        </label>
                        <span class="classroom-file__name">Ningún archivo seleccionado</span>
                      </div>
                    </div>
                    <div class="form-group col-sm-12 col-md-6 mb-0">
                      <label class="small text-muted">URLs de recursos</label>
                      <textarea name="urlsRecursoInicial" rows="3" class="form-control form-control-sm" placeholder="Pegá una URL por línea..."></textarea>
                    </div>
                  </div>
                  </fieldset>
                </div>
                <button type="submit" name="estadoLeccion" value="PUBLICADA" class="btn btn-primary btn-block btn-sm">
                  <i class="fas fa-plus mr-1"></i>Crear lección
                </button>
                <button type="submit" name="estadoLeccion" value="BORRADOR" class="btn btn-outline-secondary btn-block btn-sm">
                  <i class="fas fa-save mr-1"></i>Guardar borrador
                </button>
              </form>
            </div>
          </div>
        <?php endif; ?>

        <div class="card card-outline card-info shadow-sm mb-0">
          <div class="card-header section-header-soft">
            <h3 class="card-title mb-0">Lecciones del curso</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          </div>
          <div class="card-body">
            <?php if (empty($lecciones)): ?>
              <div class="alert alert-light border mb-0">
                Todavia no hay lecciones cargadas para esta seccion.
              </div>
            <?php endif; ?>

            <?php foreach ($leccionesGestion as $leccion): ?>
	              <?php
	              $tipoLeccion = strtoupper((string) ($leccion['tipoLeccion'] ?? 'MATERIAL'));
	              $estadoLeccion = strtoupper((string) ($leccion['estadoLeccion'] ?? 'PUBLICADA'));
	              $estaProgramada = $leccionProgramada($leccion);
	              $recursos = ControladorLecciones::crtBuscarRecursosPorLeccion((int) $leccion['idLeccion']);
              $posts = $tipoLeccion === 'PREGUNTA' ? ControladorLecciones::crtBuscarPostsPorLeccion((int) $leccion['idLeccion']) : [];
              $entrega = $tipoLeccion === 'TAREA' && ControladorPermisos::esEstudiante()
                ? ControladorLecciones::crtBuscarEntregaPorLeccionEstudiante((int) $leccion['idLeccion'], $idUsuarioActual)
                : null;
              ?>
              <?php $collapseIdDocente = 'leccion-docente-' . (int) $leccion['idLeccion']; ?>
              <?php $abrirLeccionDocente = $leccionSolicitada === (int) $leccion['idLeccion']; ?>
              <div class="card card-light shadow-none border mb-3 lesson-item-card">
                <div class="card-header bg-white border-bottom-0 pb-0">
                  <div class="d-flex align-items-center justify-content-between flex-wrap">
                    <h4 class="card-title mb-0">
                      <?php echo htmlspecialchars($leccion['nombreLeccion'], ENT_QUOTES, 'UTF-8'); ?>
                    </h4>
                    <div class="d-flex align-items-center">
                      <span class="<?php echo $claseBadgeTipoLeccion($tipoLeccion); ?> mr-2"><?php echo htmlspecialchars($tipoLeccion, ENT_QUOTES, 'UTF-8'); ?></span>
	                      <span class="badge badge-<?php echo $estadoLeccion === 'BORRADOR' || $estaProgramada ? 'warning' : 'success'; ?> mr-2">
	                        <?php echo $estadoLeccion === 'BORRADOR' ? 'Borrador' : ($estaProgramada ? 'Programada' : 'Publicada'); ?>
	                      </span>
                      <button type="button" class="btn btn-tool" data-toggle="collapse" data-target="#<?php echo $collapseIdDocente; ?>" aria-expanded="<?php echo $abrirLeccionDocente ? 'true' : 'false'; ?>">
                        <i class="fas <?php echo $abrirLeccionDocente ? 'fa-chevron-down' : 'fa-chevron-right'; ?>"></i>
                      </button>
                    </div>
                  </div>
                  <small class="text-muted">
                    <?php echo (int) $leccion['totalRecursos']; ?> recursos ·
                    <?php echo (int) $leccion['totalEntregas']; ?> entregas ·
	                    <?php echo (int) $leccion['totalPosts']; ?> mensajes
	                    <?php if ($estaProgramada): ?>
	                      - Disponible desde <?php echo htmlspecialchars($fechaProgramadaTexto($leccion['fechaPublicacionLeccion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
	                    <?php endif; ?>
                  </small>
                </div>
                <div id="<?php echo $collapseIdDocente; ?>" class="collapse<?php echo $abrirLeccionDocente ? ' show' : ''; ?>">
                  <div class="card-body pt-3">
                    <div class="classwork-rich-content text-muted mb-3">
                      <?php echo $renderContenidoLeccion($leccion['contenidoLeccion'] ?? ''); ?>
                    </div>

                    <?php if ($puedeGestionar): ?>
                      <div class="border rounded p-3 bg-light mb-3">
                        <h6 class="text-uppercase text-muted small mb-3">Editar leccion</h6>
                        <form method="post" class="row">
                          <input type="hidden" name="accion" value="actualizar_leccion">
                          <input type="hidden" name="idLeccion" value="<?php echo (int) $leccion['idLeccion']; ?>">
                          <div class="form-group col-md-4">
                            <label class="small text-muted">Nombre</label>
                            <input type="text" name="nombreLeccion" class="form-control form-control-sm" value="<?php echo htmlspecialchars($leccion['nombreLeccion'], ENT_QUOTES, 'UTF-8'); ?>" required>
                          </div>
                          <div class="form-group col-md-4">
                            <label class="small text-muted">Tipo</label>
                            <select name="tipoLeccion" class="form-control form-control-sm" required>
                              <option value="MATERIAL" <?php echo $tipoLeccion === 'MATERIAL' ? 'selected' : ''; ?>>Material</option>
                              <option value="TAREA" <?php echo $tipoLeccion === 'TAREA' ? 'selected' : ''; ?>>Tarea</option>
                              <option value="PREGUNTA" <?php echo $tipoLeccion === 'PREGUNTA' ? 'selected' : ''; ?>>Pregunta</option>
                            </select>
                          </div>
	                          <div class="form-group col-md-4">
	                            <label class="small text-muted">Estado</label>
	                            <div>
	                              <span class="badge badge-<?php echo $estadoLeccion === 'BORRADOR' || $estaProgramada ? 'warning' : 'success'; ?> px-3 py-2">
	                                <?php echo $estadoLeccion === 'BORRADOR' ? 'Borrador' : ($estaProgramada ? 'Programada' : 'Publicada'); ?>
	                              </span>
	                            </div>
	                          </div>
	                          <div class="form-group col-md-4">
	                            <label class="small text-muted">Programar publicacion</label>
	                            <input type="datetime-local" name="fechaPublicacionLeccion" class="form-control form-control-sm" value="<?php echo htmlspecialchars($fechaProgramadaInput($leccion['fechaPublicacionLeccion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
	                            <small class="form-text text-muted">Dejalo vacio para publicar inmediatamente.</small>
	                          </div>
	                          <div class="form-group col-12">
                            <label class="small text-muted">Contenido</label>
                            <textarea name="contenidoLeccion" rows="4" class="form-control form-control-sm summernote-leccion"><?php echo htmlspecialchars((string) $leccion['contenidoLeccion'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                          </div>
                          <div class="form-group col-12 text-right mb-0">
                            <button type="submit" name="estadoLeccion" value="BORRADOR" class="btn btn-outline-secondary btn-sm mr-2">
                              <i class="fas fa-save mr-1"></i>Guardar borrador
                            </button>
                            <button type="submit" name="estadoLeccion" value="PUBLICADA" class="btn btn-success btn-sm">
                              <i class="fas fa-bullhorn mr-1"></i>Publicar
                            </button>
                          </div>
                        </form>
                        <form method="post" class="mt-2 text-right">
                          <input type="hidden" name="accion" value="eliminar_leccion">
                          <input type="hidden" name="idLeccion" value="<?php echo (int) $leccion['idLeccion']; ?>">
                          <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Eliminar esta leccion y todo su contenido?');">
                            <i class="fas fa-trash mr-1"></i>Eliminar
                          </button>
                        </form>
                      </div>
                    <?php endif; ?>

                    <div class="mb-3">
                      <h6 class="text-uppercase text-muted small">Recursos asociados</h6>
                      <?php if (empty($recursos)): ?>
                        <div class="alert alert-light border mb-0">
                          No hay recursos asociados todavia.
                        </div>
                      <?php else: ?>
                        <div class="list-group">
                          <?php foreach ($recursos as $recurso): ?>
                            <div class="list-group-item">
                              <div class="d-flex justify-content-between align-items-center flex-wrap">
                                <div class="mb-2">
                                  <span class="badge badge-secondary mr-2"><?php echo htmlspecialchars($recurso['tipoRecurso'], ENT_QUOTES, 'UTF-8'); ?></span>
                                  <strong><?php echo htmlspecialchars($recurso['tituloRecurso'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                </div>
                                <a class="btn btn-sm btn-outline-primary mb-2" href="<?php echo htmlspecialchars($recurso['urlRecurso'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                                  Abrir
                                </a>
                              </div>

                              <?php if ($puedeGestionar): ?>
                                <form method="post" enctype="multipart/form-data" class="row mt-2">
                                  <input type="hidden" name="accion" value="actualizar_recurso">
                                  <input type="hidden" name="idRecursoLeccion" value="<?php echo (int) $recurso['idRecursoLeccion']; ?>">
                                  <input type="hidden" name="id_leccion" value="<?php echo (int) $leccion['idLeccion']; ?>">
                                  <div class="form-group col-md-3 d-none">
                                    <label class="small text-muted">Tipo</label>
                                    <select name="tipoRecurso" class="form-control form-control-sm">
                                      <option value="ARCHIVO" <?php echo strtoupper((string) $recurso['tipoRecurso']) === 'ARCHIVO' ? 'selected' : ''; ?>>Archivo</option>
                                      <option value="ENLACE" <?php echo strtoupper((string) $recurso['tipoRecurso']) === 'ENLACE' ? 'selected' : ''; ?>>Enlace</option>
                                    </select>
                                  </div>
                                  <div class="form-group col-md-4">
                                    <label class="small text-muted">Titulo</label>
                                    <input type="text" name="tituloRecurso" class="form-control form-control-sm" value="<?php echo htmlspecialchars($recurso['tituloRecurso'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                  </div>
                                  <div class="form-group col-md-5">
                                    <label class="small text-muted">Archivo o URL</label>
                                    <div class="classroom-file">
                                      <input type="file" class="classroom-file__input" id="archivoRecursoEdit<?php echo (int) $recurso['idRecursoLeccion']; ?>" name="archivoRecurso">
                                      <label class="classroom-file__button" for="archivoRecursoEdit<?php echo (int) $recurso['idRecursoLeccion']; ?>">
                                        <i class="fas fa-paperclip mr-2"></i>Seleccionar archivo
                                      </label>
                                      <span class="classroom-file__name">Ningún archivo seleccionado</span>
                                    </div>
                                    <input type="text" name="urlRecurso" class="form-control form-control-sm" value="<?php echo htmlspecialchars($recurso['urlRecurso'], ENT_QUOTES, 'UTF-8'); ?>">
                                  </div>
                                  <div class="form-group col-12 text-right mb-0">
                                    <button type="submit" class="btn btn-success btn-sm">
                                      <i class="fas fa-save mr-1"></i>Actualizar recurso
                                    </button>
                                  </div>
                                </form>
                                <form method="post" class="mt-2 text-right">
                                  <input type="hidden" name="accion" value="eliminar_recurso">
                                  <input type="hidden" name="idRecursoLeccion" value="<?php echo (int) $recurso['idRecursoLeccion']; ?>">
                                  <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Eliminar este recurso?');">
                                    <i class="fas fa-trash mr-1"></i>Eliminar
                                  </button>
                                </form>
                              <?php endif; ?>
                            </div>
                          <?php endforeach; ?>
                        </div>
                      <?php endif; ?>
                    </div>

                    <?php if ($puedeGestionar): ?>
                      <div class="border rounded p-3 bg-light mb-3">
                        <h6 class="text-uppercase text-muted small mb-3">Agregar recurso</h6>
                        <form method="post" enctype="multipart/form-data" class="row" data-resource-uploader>
                          <input type="hidden" name="accion" value="crear_recurso">
                          <input type="hidden" name="id_leccion" value="<?php echo (int) $leccion['idLeccion']; ?>">
                          <div class="form-group col-md-4 d-none">
                            <label class="small text-muted">Tipo</label>
                            <select name="tipoRecurso" class="form-control form-control-sm" required>
                              <option value="ARCHIVO">Archivo adjunto</option>
                              <option value="ENLACE">Enlace externo</option>
                            </select>
                          </div>
                          <div class="form-group col-md-4">
                            <label class="small text-muted">Título o prefijo</label>
                            <input type="text" name="tituloRecurso" class="form-control form-control-sm" placeholder="Con varios recursos se combina con cada nombre">
                          </div>
                          <div class="form-group col-md-8">
                            <div class="resource-attachment-list d-none" data-attachment-list></div>
                            <input type="file" class="resource-file-input d-none" id="archivoRecursoNuevoMultiple<?php echo (int) $leccion['idLeccion']; ?>" name="archivoRecurso[]" multiple data-file-input>
                            <div class="resource-link-list" data-link-list></div>
                            <div class="lesson-attach-actions">
                              <button type="button" class="lesson-attach-button" data-trigger-file>
                                <span><i class="fas fa-upload"></i></span>
                                <strong>Subir archivo</strong>
                              </button>
                              <button type="button" class="lesson-attach-button" data-add-link>
                                <span><i class="fas fa-link"></i></span>
                                <strong>Enlace</strong>
                              </button>
                            </div>
                          </div>
                          <div class="form-group col-md-8 d-none">
                            <fieldset disabled>
                              <label class="small text-muted">Archivo o URL</label>
                            <div class="classroom-file">
                              <input type="file" class="classroom-file__input" id="archivoRecursoNuevo<?php echo (int) $leccion['idLeccion']; ?>" name="archivoRecurso[]" multiple>
                              <label class="classroom-file__button" for="archivoRecursoNuevo<?php echo (int) $leccion['idLeccion']; ?>">
                                <i class="fas fa-paperclip mr-2"></i>Seleccionar archivo
                              </label>
                              <span class="classroom-file__name">Ningún archivo seleccionado</span>
                            </div>
                              <textarea name="urlsRecurso" rows="3" class="form-control form-control-sm" placeholder="Pegá una URL por línea..."></textarea>
                            </fieldset>
                          </div>
                          <div class="form-group col-12 mb-0 text-right">
                            <button type="submit" class="btn btn-info btn-sm">
                              <i class="fas fa-paperclip mr-1"></i>Agregar recurso
                            </button>
                          </div>
                        </form>
                      </div>
                    <?php endif; ?>

                    <?php if ($tipoLeccion === 'TAREA'): ?>
                      <div class="border rounded p-3 bg-light mb-3">
                        <h6 class="text-uppercase text-muted small mb-3">Tarea</h6>

                        <?php if (ControladorPermisos::esEstudiante()): ?>
                          <div class="alert alert-info">
                            Subí uno o varios archivos y, si querés, agregá un comentario corto. Si reenviás la tarea, se actualiza la entrega anterior.
                          </div>
                          <form method="post" enctype="multipart/form-data" class="row">
                            <input type="hidden" name="accion" value="entregar_tarea">
                            <input type="hidden" name="id_leccion" value="<?php echo (int) $leccion['idLeccion']; ?>">
                            <input type="hidden" name="id_seccion" value="<?php echo (int) $seccion['idSeccion']; ?>">
                            <input type="hidden" name="id_curso" value="<?php echo (int) $seccion['id_curso']; ?>">
                            <div class="form-group col-md-5">
                              <label class="small text-muted">Archivos</label>
                              <div class="classroom-file">
                                <input type="file" class="classroom-file__input" id="archivoEntregaTarea<?php echo (int) $leccion['idLeccion']; ?>" name="archivoEntrega[]" multiple <?php echo $entrega ? '' : 'required'; ?>>
                                <label class="classroom-file__button" for="archivoEntregaTarea<?php echo (int) $leccion['idLeccion']; ?>">
                                  <i class="fas fa-paperclip mr-2"></i>Seleccionar archivos
                                </label>
                                <span class="classroom-file__name">Ningún archivo seleccionado</span>
                              </div>
                              <?php if ($entrega): ?>
                                <small class="form-text text-muted">Si elegís archivos nuevos, reemplazarán los actuales.</small>
                              <?php endif; ?>
                            </div>
                            <div class="form-group col-md-7">
                              <label class="small text-muted">Comentario</label>
                              <input type="text" name="comentarioEntrega" class="form-control form-control-sm" placeholder="Opcional">
                            </div>
                            <div class="form-group col-12 mb-0 text-right">
                              <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-upload mr-1"></i>Enviar entrega
                              </button>
                            </div>
                          </form>

                          <?php if ($entrega): ?>
                            <div class="alert alert-light border mt-3 mb-0">
                              Ya entregaste esta tarea el <?php echo htmlspecialchars((string) $entrega['fechaEntrega'], ENT_QUOTES, 'UTF-8'); ?>.
                              <ul class="list-unstyled small mt-2 mb-0">
                                <?php foreach ((array) ($entrega['adjuntos'] ?? []) as $indiceAdjunto => $adjunto): ?>
                                  <li>
                                    <a href="<?php echo htmlspecialchars((string) ($adjunto['rutaArchivo'] ?? '#'), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                                      <i class="fas fa-paperclip mr-1"></i><?php echo htmlspecialchars((string) ($adjunto['nombreOriginal'] ?? ('Archivo ' . ($indiceAdjunto + 1))), ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                  </li>
                                <?php endforeach; ?>
                              </ul>
                            </div>
                          <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($puedeGestionar): ?>
                          <?php $entregas = ControladorLecciones::crtBuscarEntregasPorLeccion((int) $leccion['idLeccion']); ?>
                          <form
                            method="post"
                            class="batch-grading-form mt-3"
                            action="index.php?r=detalle-seccion&amp;idSeccion=<?php echo (int) $seccion['idSeccion']; ?>&amp;abrirLeccion=<?php echo (int) $leccion['idLeccion']; ?>#leccion-docente-<?php echo (int) $leccion['idLeccion']; ?>"
                          >
                            <input type="hidden" name="accion" value="guardar_calificaciones_entregas">
                            <input type="hidden" name="id_seccion" value="<?php echo (int) $seccion['idSeccion']; ?>">
                            <input type="hidden" name="id_modulo" value="<?php echo (int) $leccion['idLeccion']; ?>">
                            <input type="hidden" name="id_curso" value="<?php echo (int) $seccion['id_curso']; ?>">

                            <div class="batch-grading-heading">
                              <div>
                                <strong>Corrección conjunta</strong>
                                <small>Completá una o varias notas y guardalas todas de una vez.</small>
                              </div>
                              <span class="badge badge-light border"><?php echo count($entregas); ?> entregas</span>
                            </div>

                            <div class="table-responsive">
                              <table class="table table-sm table-bordered mb-0 batch-grading-table">
                                <thead>
                                  <tr>
                                    <th>Estudiante</th>
                                    <th>Archivos</th>
                                    <th>Comentario</th>
                                    <th>Nota y devolución</th>
                                  </tr>
                                </thead>
                                <tbody>
                                  <?php foreach ($entregas as $entregaDoc): ?>
                                    <?php $notaActual = $buscarCalificacion($calificacionesSeccion, (int) $leccion['idLeccion'], (int) $entregaDoc['id_estudiante']); ?>
                                    <tr>
                                      <td>
                                        <strong><?php echo htmlspecialchars($entregaDoc['apellidoUsuario'] . ' ' . $entregaDoc['nombreUsuario'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <small class="d-block text-muted">
                                          <?php echo $notaActual ? 'Corrección guardada' : 'Pendiente de corrección'; ?>
                                        </small>
                                      </td>
                                      <td>
                                        <ul class="list-unstyled mb-0">
                                          <?php foreach ((array) ($entregaDoc['adjuntos'] ?? []) as $indiceAdjunto => $adjunto): ?>
                                            <li>
                                              <a href="<?php echo htmlspecialchars((string) ($adjunto['rutaArchivo'] ?? '#'), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                                                <i class="fas fa-paperclip mr-1"></i><?php echo htmlspecialchars((string) ($adjunto['nombreOriginal'] ?? ('Archivo ' . ($indiceAdjunto + 1))), ENT_QUOTES, 'UTF-8'); ?>
                                              </a>
                                            </li>
                                          <?php endforeach; ?>
                                        </ul>
                                      </td>
                                      <td><?php echo nl2br(htmlspecialchars((string) ($entregaDoc['comentarioEntrega'] ?? ''), ENT_QUOTES, 'UTF-8')); ?></td>
                                      <td>
                                        <div class="batch-grading-fields">
                                          <input
                                            type="number"
                                            name="calificaciones[<?php echo (int) $entregaDoc['id_estudiante']; ?>]"
                                            class="form-control form-control-sm batch-grade-input"
                                            min="0"
                                            max="100"
                                            placeholder="Nota"
                                            aria-label="Nota de <?php echo htmlspecialchars($entregaDoc['apellidoUsuario'] . ' ' . $entregaDoc['nombreUsuario'], ENT_QUOTES, 'UTF-8'); ?>"
                                            value="<?php echo $notaActual ? (int) $notaActual['calificacion'] : ''; ?>"
                                          >
                                          <input
                                            type="text"
                                            name="devoluciones[<?php echo (int) $entregaDoc['id_estudiante']; ?>]"
                                            class="form-control form-control-sm"
                                            placeholder="Devolución opcional"
                                            aria-label="Devolución para <?php echo htmlspecialchars($entregaDoc['apellidoUsuario'] . ' ' . $entregaDoc['nombreUsuario'], ENT_QUOTES, 'UTF-8'); ?>"
                                            value="<?php echo htmlspecialchars((string) ($notaActual['devolucion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                          >
                                        </div>
                                      </td>
                                    </tr>
                                  <?php endforeach; ?>
                                  <?php if (empty($entregas)): ?>
                                    <tr>
                                      <td colspan="4" class="text-center text-muted">Todavia no hay entregas registradas.</td>
                                    </tr>
                                  <?php endif; ?>
                                </tbody>
                              </table>
                            </div>

                            <?php if (!empty($entregas)): ?>
                              <div class="batch-grading-footer">
                                <small><i class="fas fa-info-circle mr-1"></i>Las filas sin nota no se modifican.</small>
                                <button type="submit" class="btn btn-success btn-sm">
                                  <i class="fas fa-check-double mr-1"></i>Guardar todas las correcciones
                                </button>
                              </div>
                            <?php endif; ?>
                          </form>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>

                    <?php if ($tipoLeccion === 'PREGUNTA'): ?>
                      <div class="border rounded p-3 bg-light mb-3">
                        <h6 class="text-uppercase text-muted small mb-3">Foro de la pregunta</h6>
                        <?php if (!empty($posts)): ?>
                          <div class="mb-3">
                            <?php foreach ($posts as $post): ?>
                              <div class="border rounded p-3 mb-2 bg-white">
                                <strong><?php echo htmlspecialchars($post['apellidoUsuario'] . ' ' . $post['nombreUsuario'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <small class="text-muted d-block"><?php echo htmlspecialchars((string) $post['fechaPosteo'], ENT_QUOTES, 'UTF-8'); ?></small>
                                <p class="mb-0 mt-2"><?php echo nl2br(htmlspecialchars($post['contenidoPosteo'], ENT_QUOTES, 'UTF-8')); ?></p>
                              </div>
                            <?php endforeach; ?>
                          </div>
                        <?php else: ?>
                          <div class="alert alert-light border">Todavia no hay mensajes en esta pregunta.</div>
                        <?php endif; ?>

                        <form method="post">
                          <input type="hidden" name="accion" value="crear_post">
                          <input type="hidden" name="id_leccion" value="<?php echo (int) $leccion['idLeccion']; ?>">
                          <input type="hidden" name="id_curso" value="<?php echo (int) $seccion['id_curso']; ?>">
                          <div class="form-group">
                            <label class="small text-muted">Tu aporte</label>
                            <textarea name="contenidoPosteo" rows="3" class="form-control form-control-sm" placeholder="Escribi tu comentario o respuesta..." required></textarea>
                          </div>
                          <div class="text-right">
                            <button type="submit" class="btn btn-primary btn-sm">
                              <i class="fas fa-comment-dots mr-1"></i>Publicar
                            </button>
                          </div>
                        </form>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>

            <?php if ($puedeGestionar && $totalPaginasLecciones > 1): ?>
              <div class="d-flex align-items-center justify-content-between flex-wrap pt-2">
                <small class="text-muted mb-2 mb-md-0">
                  Mostrando <?php echo count($leccionesGestion); ?> de <?php echo (int) $totalLeccionesGestion; ?> lecciones
                </small>
                <nav aria-label="Paginacion de lecciones">
                  <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?php echo $paginaLecciones <= 1 ? 'disabled' : ''; ?>">
                      <a class="page-link" href="index.php?r=detalle-seccion&idSeccion=<?php echo (int) $idSeccion; ?>&paginaLecciones=<?php echo max(1, $paginaLecciones - 1); ?>">Anterior</a>
                    </li>
                    <?php for ($pagina = 1; $pagina <= $totalPaginasLecciones; $pagina++): ?>
                      <li class="page-item <?php echo $pagina === $paginaLecciones ? 'active' : ''; ?>">
                        <a class="page-link" href="index.php?r=detalle-seccion&idSeccion=<?php echo (int) $idSeccion; ?>&paginaLecciones=<?php echo $pagina; ?>">
                          <?php echo $pagina; ?>
                        </a>
                      </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $paginaLecciones >= $totalPaginasLecciones ? 'disabled' : ''; ?>">
                      <a class="page-link" href="index.php?r=detalle-seccion&idSeccion=<?php echo (int) $idSeccion; ?>&paginaLecciones=<?php echo min($totalPaginasLecciones, $paginaLecciones + 1); ?>">Siguiente</a>
                    </li>
                  </ul>
                </nav>
              </div>
            <?php endif; ?>
          </div>
        </div>


      </div>
      <div class="col-12 col-lg-4">
        <?php include __DIR__ . '/detalle-seccion/_sidebar.php'; ?>
        <?php if (false): ?>
        <div class="card card-outline card-success shadow-sm lesson-side-card">
          <div class="card-header section-header-soft">
            <h3 class="card-title">Detalle de la sección</h3>
          </div>
          <div class="card-body">
            <dl class="row mb-0">
              <dt class="col-5 text-muted">Curso</dt>
              <dd class="col-7"><?php echo htmlspecialchars($seccion['nombreCurso'], ENT_QUOTES, 'UTF-8'); ?></dd>
              <dt class="col-5 text-muted">Estado</dt>
              <dd class="col-7"><?php echo htmlspecialchars((string) $seccion['estado'], ENT_QUOTES, 'UTF-8'); ?></dd>
              <dt class="col-5 text-muted">Docente</dt>
              <dd class="col-7"><?php echo htmlspecialchars(trim(($seccion['nombreUsuario'] ?? '') . ' ' . ($seccion['apellidoUsuario'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></dd>
              <dt class="col-5 text-muted">Recursos</dt>
              <dd class="col-7"><?php echo (int) ($resumen['totalRecursos'] ?? 0); ?></dd>
              <dt class="col-5 text-muted">Promedio</dt>
              <dd class="col-7"><?php echo htmlspecialchars((string) ($resumen['promedioNotas'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></dd>
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
                <dd class="col-6"><?php echo htmlspecialchars((string) ($seguimientoPersonal['promedioNotas'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></dd>
                <dt class="col-6 text-muted">Foro</dt>
                <dd class="col-6"><?php echo (int) ($seguimientoPersonal['totalPosts'] ?? 0); ?></dd>
              </dl>
            </div>
          </div>

          <div class="card card-outline card-info shadow-sm lesson-side-card">
            <div class="card-header section-header-soft">
              <h3 class="card-title">Mis calificaciones</h3>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                  <thead>
                    <tr>
                      <th>Leccion</th>
                      <th>Tipo</th>
                      <th>Nota</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($misCalificaciones as $calificacion): ?>
                      <tr>
                        <td><?php echo htmlspecialchars((string) ($calificacion['nombreLeccion'] ?? 'Actividad'), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string) ($calificacion['tipoLeccion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo (int) $calificacion['calificacion']; ?></td>
                      </tr>
                    <?php endforeach; ?>
                    <?php if (empty($misCalificaciones)): ?>
                      <tr>
                        <td colspan="3" class="text-center text-muted">Todavia no tenes notas cargadas.</td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
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
                <dd class="col-6"><?php echo htmlspecialchars((string) ($resumen['promedioNotas'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></dd>
              </dl>
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
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($estudiantesCurso as $estudiante): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($estudiante['apellidoUsuario'] . ' ' . $estudiante['nombreUsuario'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($estudiante['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                      </tr>
                    <?php endforeach; ?>
                    <?php if (empty($estudiantesCurso)): ?>
                      <tr>
                        <td colspan="2" class="text-center text-muted">Todavia no hay estudiantes asignados.</td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
</section>

<script>
  document.querySelectorAll('[data-resource-uploader]').forEach(function(uploader) {
    var fileInput = uploader.querySelector('[data-file-input]');
    var fileList = uploader.querySelector('[data-attachment-list]');
    var linkList = uploader.querySelector('[data-link-list]');
    var triggerFile = uploader.querySelector('[data-trigger-file]');
    var addLink = uploader.querySelector('[data-add-link]');
    var fileStore = window.DataTransfer ? new DataTransfer() : null;

    function formatSize(bytes) {
      if (!bytes) {
        return 'Archivo';
      }
      if (bytes < 1024 * 1024) {
        return Math.ceil(bytes / 1024) + ' KB';
      }
      return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function syncInputFiles() {
      if (fileStore && fileInput) {
        fileInput.files = fileStore.files;
      }
    }

    function renderFiles() {
      if (!fileList || !fileInput) {
        return;
      }

      var files = fileStore ? Array.from(fileStore.files) : Array.from(fileInput.files || []);
      fileList.innerHTML = '';
      fileList.classList.toggle('d-none', files.length === 0);

      files.forEach(function(file, index) {
        var item = document.createElement('div');
        item.className = 'resource-attachment-item';
        item.innerHTML =
          '<span class="resource-attachment-icon"><i class="fas fa-file-alt"></i></span>' +
          '<span class="resource-attachment-body"><strong></strong><small></small></span>' +
          '<button type="button" class="resource-remove-button" aria-label="Quitar archivo"><i class="fas fa-times"></i></button>';

        item.querySelector('strong').textContent = file.name;
        item.querySelector('small').textContent = formatSize(file.size);
        item.querySelector('button').addEventListener('click', function() {
          if (fileStore) {
            var nextStore = new DataTransfer();
            Array.from(fileStore.files).forEach(function(currentFile, currentIndex) {
              if (currentIndex !== index) {
                nextStore.items.add(currentFile);
              }
            });
            fileStore = nextStore;
            syncInputFiles();
          } else {
            fileInput.value = '';
          }
          renderFiles();
        });

        fileList.appendChild(item);
      });
    }

    function resolveUrlInputName() {
      var form = uploader.closest('form');
      var action = form ? form.querySelector('input[name="accion"]') : null;
      return action && action.value === 'crear_leccion' ? 'urlsRecursoInicial[]' : 'urlsRecurso[]';
    }

    function createLinkField() {
      if (!linkList) {
        return;
      }

      var item = document.createElement('div');
      item.className = 'resource-link-item';
      item.innerHTML =
        '<span class="resource-attachment-icon"><i class="fas fa-link"></i></span>' +
        '<input type="url" class="form-control form-control-sm" placeholder="https://..." required>' +
        '<button type="button" class="resource-remove-button" aria-label="Quitar enlace"><i class="fas fa-times"></i></button>';

      item.querySelector('input').setAttribute('name', resolveUrlInputName());
      item.querySelector('button').addEventListener('click', function() {
        item.remove();
      });
      linkList.appendChild(item);
      item.querySelector('input').focus();
    }

    if (triggerFile && fileInput) {
      triggerFile.addEventListener('click', function() {
        fileInput.click();
      });
    }

    if (fileInput) {
      fileInput.addEventListener('change', function() {
        if (fileStore) {
          Array.from(fileInput.files || []).forEach(function(file) {
            fileStore.items.add(file);
          });
          syncInputFiles();
        }
        renderFiles();
      });
    }

    if (addLink) {
      addLink.addEventListener('click', createLinkField);
    }
  });

  $('.lesson-item-card .collapse').on('show.bs.collapse', function() {
    $(this).closest('.lesson-item-card').find('[data-toggle="collapse"] i').first()
      .removeClass('fa-chevron-right')
      .addClass('fa-chevron-down');
  }).on('hide.bs.collapse', function() {
    $(this).closest('.lesson-item-card').find('[data-toggle="collapse"] i').first()
      .removeClass('fa-chevron-down')
      .addClass('fa-chevron-right');
  });
</script>
