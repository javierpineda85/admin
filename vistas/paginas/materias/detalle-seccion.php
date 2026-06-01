<?php
$idSeccion = (int) ($_GET['idSeccion'] ?? 0);
$seccion = ControladorLecciones::crtBuscarSeccionPorId($idSeccion);
$lecciones = $idSeccion > 0 ? ControladorLecciones::crtBuscarLeccionesPorSeccion($idSeccion) : [];
$puedeGestionar = ControladorPermisos::esAdministrador() || ControladorPermisos::esDocente();

if ($puedeGestionar) {
    ControladorLecciones::crtGuardarLeccion();
    ControladorLecciones::crtGuardarRecursoLeccion();
}

if (!$seccion) {
    $seccion = [
        'tituloSeccion' => 'Sección no encontrada',
        'contenidoSeccion' => '',
        'nombreCurso' => '',
        'estado' => '',
        'nombreUsuario' => '',
        'apellidoUsuario' => '',
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

        <div class="card card-outline card-info shadow-sm">
          <div class="card-header">
            <h3 class="card-title">Lecciones del aula</h3>
          </div>
          <div class="card-body">
            <?php if (empty($lecciones)): ?>
              <div class="alert alert-light border mb-0">
                Todavía no hay lecciones cargadas para esta sección.
              </div>
            <?php endif; ?>

            <?php foreach ($lecciones as $leccion): ?>
              <?php $recursos = ControladorLecciones::crtBuscarRecursosPorLeccion((int) $leccion['idLeccion']); ?>
              <div class="card card-light shadow-none border mb-3">
                <div class="card-header bg-white border-bottom-0 pb-0">
                  <h4 class="card-title mb-0">
                    <?php echo htmlspecialchars($leccion['nombreLeccion'], ENT_QUOTES, 'UTF-8'); ?>
                  </h4>
                  <span class="badge badge-info float-right"><?php echo (int) $leccion['totalRecursos']; ?> recursos</span>
                </div>
                <div class="card-body pt-3">
                  <p class="text-muted mb-3">
                    <?php echo nl2br(htmlspecialchars((string) $leccion['contenidoLeccion'], ENT_QUOTES, 'UTF-8')); ?>
                  </p>

                  <div class="mb-3">
                    <h6 class="text-uppercase text-muted small">Recursos asociados</h6>
                    <?php if (empty($recursos)): ?>
                      <div class="alert alert-light border mb-0">
                        No hay recursos asociados todavía.
                      </div>
                    <?php else: ?>
                      <ul class="list-group list-group-flush">
                        <?php foreach ($recursos as $recurso): ?>
                          <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                            <div>
                              <span class="badge badge-secondary mr-2"><?php echo htmlspecialchars($recurso['tipoRecurso'], ENT_QUOTES, 'UTF-8'); ?></span>
                              <strong><?php echo htmlspecialchars($recurso['tituloRecurso'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            </div>
                            <a class="btn btn-sm btn-outline-primary" href="<?php echo htmlspecialchars($recurso['urlRecurso'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                              Abrir
                            </a>
                          </li>
                        <?php endforeach; ?>
                      </ul>
                    <?php endif; ?>
                  </div>

                  <?php if ($puedeGestionar): ?>
                    <div class="border rounded p-3 bg-light">
                      <h6 class="text-uppercase text-muted small mb-3">Agregar recurso</h6>
                      <form method="post" enctype="multipart/form-data" class="row">
                        <input type="hidden" name="id_leccion" value="<?php echo (int) $leccion['idLeccion']; ?>">
                        <div class="form-group col-md-4">
                          <label class="small text-muted">Tipo</label>
                          <select name="tipoRecurso" class="form-control form-control-sm" required>
                            <option value="ARCHIVO">Archivo adjunto</option>
                            <option value="ENLACE">Enlace externo</option>
                          </select>
                        </div>
                        <div class="form-group col-md-4">
                          <label class="small text-muted">Título</label>
                          <input type="text" name="tituloRecurso" class="form-control form-control-sm" placeholder="Ej. Guía de práctica" required>
                        </div>
                        <div class="form-group col-md-4">
                          <label class="small text-muted">Archivo o URL</label>
                          <input type="file" name="archivoRecurso" class="form-control form-control-sm mb-2">
                          <input type="url" name="urlRecurso" class="form-control form-control-sm" placeholder="https://...">
                        </div>
                        <div class="form-group col-12 mb-0 text-right">
                          <button type="submit" class="btn btn-info btn-sm">
                            <i class="fas fa-paperclip mr-1"></i>Agregar recurso
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
            </dl>
          </div>
        </div>

        <?php if ($puedeGestionar): ?>
          <div class="card card-outline card-primary shadow-sm">
            <div class="card-header">
              <h3 class="card-title">Crear lección</h3>
            </div>
            <div class="card-body">
              <form method="post">
                <input type="hidden" name="id_modulo" value="<?php echo (int) $idSeccion; ?>">
                <div class="form-group">
                  <label class="small text-muted">Nombre</label>
                  <input type="text" name="nombreLeccion" class="form-control form-control-sm" placeholder="Ej. Introducción al tema" required>
                </div>
                <div class="form-group">
                  <label class="small text-muted">Contenido</label>
                  <textarea name="contenidoLeccion" rows="6" class="form-control form-control-sm" placeholder="Resumen, instrucciones, actividades..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-block btn-sm">
                  <i class="fas fa-plus mr-1"></i>Crear lección
                </button>
              </form>
            </div>
          </div>
        <?php else: ?>
          <div class="card card-outline card-warning shadow-sm">
            <div class="card-body">
              Desde este panel solo podés leer el contenido. Las altas y recursos quedan para docentes y administradores.
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['success_message'])): ?>
  <script>
    window.location.href = 'index.php?r=detalle-seccion&idSeccion=<?php echo (int) $idSeccion; ?>';
  </script>
<?php endif; ?>
