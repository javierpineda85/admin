<?php
$idSeccion = (int) ($_GET['idSeccion'] ?? 0);
$accion = trim((string) ($_POST['accion'] ?? ''));

if ($accion !== '') {
    ControladorLecciones::crtProcesarAcciones();
    ControladorCalificaciones::crtProcesarAcciones();
}

$seccion = ControladorLecciones::crtBuscarSeccionPorId($idSeccion);
$lecciones = $idSeccion > 0 ? ControladorLecciones::crtBuscarLeccionesPorSeccion($idSeccion) : [];
$puedeGestionar = ControladorPermisos::esAdministrador() || ControladorPermisos::esDocente();
$idUsuarioActual = (int) ($_SESSION['usuario']['id'] ?? 0);
$resumen = $idSeccion > 0 ? ControladorLecciones::crtResumenSeccion($idSeccion) : [];
$estudiantesCurso = $puedeGestionar && $seccion ? ControladorLecciones::crtBuscarEstudiantesCurso((int) $seccion['id_curso']) : [];
$calificacionesSeccion = $idSeccion > 0 ? ControladorCalificaciones::crtCalificacionesPorSeccion($idSeccion) : [];
$seguimientoPersonal = ControladorPermisos::esEstudiante() && $idSeccion > 0 && $idUsuarioActual > 0
    ? ControladorLecciones::crtResumenEstudianteSeccion($idSeccion, $idUsuarioActual)
    : [];
$misCalificaciones = ControladorPermisos::esEstudiante() && $idSeccion > 0 && $idUsuarioActual > 0
    ? ControladorCalificaciones::crtCalificacionesPorEstudiante($idSeccion, $idUsuarioActual)
    : [];

$buscarCalificacion = function (array $lista, int $idLeccion, int $idEstudiante): ?array {
    foreach ($lista as $item) {
        if ((int) ($item['id_modulo'] ?? 0) === $idLeccion && (int) ($item['id_estudiante'] ?? 0) === $idEstudiante) {
            return $item;
        }
    }

    return null;
};

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
?>

<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <?php if (isset($_SESSION['success_message'])): ?>
          <div class="alert alert-success alert-dismissible shadow-sm">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <h4><i class="icon fas fa-check"></i></h4>
            <?php echo htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8'); ?>
          </div>
          <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
          <div class="alert alert-danger alert-dismissible shadow-sm">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <h4><i class="icon fas fa-ban"></i></h4>
            <?php echo htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8'); ?>
          </div>
          <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="row">
      <div class="col-12 col-lg-8">
        <div class="card card-outline card-primary shadow-sm">
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
        </div>

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

        <div class="card card-outline card-info shadow-sm">
          <div class="card-header">
            <h3 class="card-title">Lecciones del aula</h3>
          </div>
          <div class="card-body">
            <?php if (empty($lecciones)): ?>
              <div class="alert alert-light border mb-0">
                Todavia no hay lecciones cargadas para esta seccion.
              </div>
            <?php endif; ?>

            <?php foreach ($lecciones as $leccion): ?>
              <?php
                $tipoLeccion = strtoupper((string) ($leccion['tipoLeccion'] ?? 'MATERIAL'));
                $recursos = ControladorLecciones::crtBuscarRecursosPorLeccion((int) $leccion['idLeccion']);
                $posts = $tipoLeccion === 'PREGUNTA' ? ControladorLecciones::crtBuscarPostsPorLeccion((int) $leccion['idLeccion']) : [];
                $entrega = $tipoLeccion === 'TAREA' && ControladorPermisos::esEstudiante()
                    ? ControladorLecciones::crtBuscarEntregaPorLeccionEstudiante((int) $leccion['idLeccion'], $idUsuarioActual)
                    : null;
              ?>
              <div class="card card-light shadow-none border mb-3">
                <div class="card-header bg-white border-bottom-0 pb-0">
                  <div class="d-flex align-items-center justify-content-between flex-wrap">
                    <h4 class="card-title mb-0">
                      <?php echo htmlspecialchars($leccion['nombreLeccion'], ENT_QUOTES, 'UTF-8'); ?>
                    </h4>
                    <span class="badge badge-primary"><?php echo htmlspecialchars($tipoLeccion, ENT_QUOTES, 'UTF-8'); ?></span>
                  </div>
                  <small class="text-muted">
                    <?php echo (int) $leccion['totalRecursos']; ?> recursos ·
                    <?php echo (int) $leccion['totalEntregas']; ?> entregas ·
                    <?php echo (int) $leccion['totalPosts']; ?> mensajes
                  </small>
                </div>
                <div class="card-body pt-3">
                  <p class="text-muted mb-3">
                    <?php echo nl2br(htmlspecialchars((string) $leccion['contenidoLeccion'], ENT_QUOTES, 'UTF-8')); ?>
                  </p>

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
                          <label class="small text-muted">Contenido</label>
                          <input type="text" name="contenidoLeccion" class="form-control form-control-sm" value="<?php echo htmlspecialchars((string) $leccion['contenidoLeccion'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="form-group col-12 text-right mb-0">
                          <button type="submit" class="btn btn-success btn-sm">
                            <i class="fas fa-save mr-1"></i>Guardar cambios
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
                                <div class="form-group col-md-3">
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
                                  <input type="file" name="archivoRecurso" class="form-control form-control-sm mb-2">
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
                      <form method="post" enctype="multipart/form-data" class="row">
                        <input type="hidden" name="accion" value="crear_recurso">
                        <input type="hidden" name="id_leccion" value="<?php echo (int) $leccion['idLeccion']; ?>">
                        <div class="form-group col-md-4">
                          <label class="small text-muted">Tipo</label>
                          <select name="tipoRecurso" class="form-control form-control-sm" required>
                            <option value="ARCHIVO">Archivo adjunto</option>
                            <option value="ENLACE">Enlace externo</option>
                          </select>
                        </div>
                        <div class="form-group col-md-4">
                          <label class="small text-muted">Titulo</label>
                          <input type="text" name="tituloRecurso" class="form-control form-control-sm" placeholder="Ej. Guia de practica" required>
                        </div>
                        <div class="form-group col-md-4">
                          <label class="small text-muted">Archivo o URL</label>
                          <input type="file" name="archivoRecurso" class="form-control form-control-sm mb-2">
                          <input type="text" name="urlRecurso" class="form-control form-control-sm" placeholder="https://...">
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
                          Subi tu entrega y, si queres, agrega un comentario corto. Si reenviás la tarea, se actualiza la entrega anterior.
                        </div>
                        <form method="post" enctype="multipart/form-data" class="row">
                          <input type="hidden" name="accion" value="entregar_tarea">
                          <input type="hidden" name="id_leccion" value="<?php echo (int) $leccion['idLeccion']; ?>">
                          <input type="hidden" name="id_seccion" value="<?php echo (int) $seccion['idSeccion']; ?>">
                          <input type="hidden" name="id_curso" value="<?php echo (int) $seccion['id_curso']; ?>">
                          <div class="form-group col-md-5">
                            <label class="small text-muted">Archivo</label>
                            <input type="file" name="archivoEntrega" class="form-control form-control-sm" required>
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
                          <div class="alert alert-success mt-3 mb-0">
                            Ya entregaste esta tarea el <?php echo htmlspecialchars((string) $entrega['fechaEntrega'], ENT_QUOTES, 'UTF-8'); ?>.
                            <a href="<?php echo htmlspecialchars($entrega['urlArchivo'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Ver archivo</a>
                          </div>
                        <?php endif; ?>
                      <?php endif; ?>

                      <?php if ($puedeGestionar): ?>
                        <?php $entregas = ControladorLecciones::crtBuscarEntregasPorLeccion((int) $leccion['idLeccion']); ?>
                        <div class="table-responsive mt-3">
                          <table class="table table-sm table-bordered mb-0">
                            <thead>
                              <tr>
                                <th>Estudiante</th>
                                <th>Entrega</th>
                                <th>Nota</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php foreach ($entregas as $entregaDoc): ?>
                                <?php $notaActual = $buscarCalificacion($calificacionesSeccion, (int) $leccion['idLeccion'], (int) $entregaDoc['id_estudiante']); ?>
                                <tr>
                                  <td><?php echo htmlspecialchars($entregaDoc['apellidoUsuario'] . ' ' . $entregaDoc['nombreUsuario'], ENT_QUOTES, 'UTF-8'); ?></td>
                                  <td>
                                    <a href="<?php echo htmlspecialchars($entregaDoc['urlArchivo'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Abrir archivo</a>
                                  </td>
                                  <td>
                                    <form method="post" class="form-inline">
                                      <input type="hidden" name="accion" value="guardar_calificacion">
                                      <input type="hidden" name="id_estudiante" value="<?php echo (int) $entregaDoc['id_estudiante']; ?>">
                                      <input type="hidden" name="id_seccion" value="<?php echo (int) $seccion['idSeccion']; ?>">
                                      <input type="hidden" name="id_modulo" value="<?php echo (int) $leccion['idLeccion']; ?>">
                                      <input type="hidden" name="id_curso" value="<?php echo (int) $seccion['id_curso']; ?>">
                                      <input type="number" name="calificacion" class="form-control form-control-sm mr-2" min="0" max="100" value="<?php echo (int) ($notaActual['calificacion'] ?? 0); ?>">
                                      <button type="submit" class="btn btn-success btn-sm">Guardar</button>
                                    </form>
                                  </td>
                                </tr>
                              <?php endforeach; ?>
                              <?php if (empty($entregas)): ?>
                                <tr>
                                  <td colspan="3" class="text-center text-muted">Todavia no hay entregas registradas.</td>
                                </tr>
                              <?php endif; ?>
                            </tbody>
                          </table>
                        </div>
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
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-4">
        <div class="card card-outline card-success shadow-sm">
          <div class="card-header">
            <h3 class="card-title">Detalle de la seccion</h3>
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

        <?php if ($puedeGestionar): ?>
          <div class="card card-outline card-primary shadow-sm">
            <div class="card-header">
              <h3 class="card-title">Crear leccion</h3>
            </div>
            <div class="card-body">
              <form method="post">
                <input type="hidden" name="accion" value="crear_leccion">
                <input type="hidden" name="id_modulo" value="<?php echo (int) $idSeccion; ?>">
                <div class="form-group">
                  <label class="small text-muted">Nombre</label>
                  <input type="text" name="nombreLeccion" class="form-control form-control-sm" placeholder="Ej. Introduccion al tema" required>
                </div>
                <div class="form-group">
                  <label class="small text-muted">Tipo</label>
                  <select name="tipoLeccion" class="form-control form-control-sm" required>
                    <option value="MATERIAL">Material</option>
                    <option value="TAREA">Tarea</option>
                    <option value="PREGUNTA">Pregunta</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="small text-muted">Contenido</label>
                  <textarea name="contenidoLeccion" rows="5" class="form-control form-control-sm" placeholder="Resumen, instrucciones o consigna..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-sm">
                  <i class="fas fa-plus mr-1"></i>Crear leccion
                </button>
              </form>
            </div>
          </div>
        <?php endif; ?>

        <?php if (ControladorPermisos::esEstudiante()): ?>
          <div class="card card-outline card-warning shadow-sm">
            <div class="card-header">
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

          <div class="card card-outline card-info shadow-sm">
            <div class="card-header">
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
          <div class="card card-outline card-info shadow-sm">
            <div class="card-header">
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

          <div class="card card-outline card-light shadow-sm">
            <div class="card-header">
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
      </div>
    </div>
  </div>
</section>

<?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $accion !== ''): ?>
  <script>
    window.location.href = 'index.php?r=detalle-seccion&idSeccion=<?php echo (int) $idSeccion; ?>';
  </script>
<?php endif; ?>
