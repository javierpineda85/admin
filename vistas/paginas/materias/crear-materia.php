<?php
$db = new Conexion;
$cursos = ControladorPermisos::esDocente()
  ? ControladorCursos::crtCursosPorDocente((int) ($_SESSION['usuario']['id'] ?? 0))
  : $db->consultas("SELECT * FROM cursos ORDER BY nombreCurso ASC");
$usuarios = ControladorUsuarios::crtUsuariosDocentesAsignables();
$registro = ControladorMaterias::crtGuardarMateria();
?>

<section class="content page-fade">
  <div class="container-fluid">
    <div class="entity-hero mb-4">
      <div class="entity-hero__content">
        <span class="entity-kicker mb-3">Secciones</span>
        <h1 class="entity-title mb-2">Crear nueva materia</h1>
        <p class="entity-lead mb-0">Asigná el curso, el docente titular y, si corresponde, un docente adjunto.</p>
      </div>
    </div>

    <div class="card glass-card">
      <div class="card-header">
        <h3 class="card-title mb-0">Datos de la sección</h3>
      </div>
      <div class="card-body">
        <form action="" method="POST" enctype="multipart/form-data">
          <div class="row">
            <div class="col-12">
              <div class="form-group">
                <label>Título de la materia</label>
                <input type="text" class="form-control" name="tituloSeccion" placeholder="Ej. Matemática aplicada" required>
              </div>
            </div>
            <div class="col-12">
              <div class="form-group">
                <label>Descripción</label>
                <textarea class="form-control" rows="4" name="contenidoSeccion" placeholder="Descripción del espacio..."></textarea>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Curso</label>
                <select class="custom-select" name="id_curso" required>
                  <?php foreach ($cursos as $curso): ?>
                    <option value="<?php echo (int) $curso['idCurso']; ?>"><?php echo htmlspecialchars($curso['nombreCurso'], ENT_QUOTES, 'UTF-8'); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Docente a cargo</label>
                <?php if (ControladorPermisos::esDocente()): ?>
                  <input type="hidden" name="docente" value="<?php echo (int) ($_SESSION['usuario']['id'] ?? 0); ?>">
                  <input type="text" class="form-control" value="Vos (docente titular)" readonly>
                <?php else: ?>
                  <select class="custom-select" name="docente" required>
                    <?php foreach ($usuarios as $usuario): ?>
                      <option value="<?php echo (int) $usuario['idUsuario']; ?>"><?php echo htmlspecialchars($usuario['nombreUsuario'] . ' ' . $usuario['apellidoUsuario'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                  </select>
                <?php endif; ?>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Docente adjunto</label>
                <select class="custom-select" name="tutor">
                  <option value="">Sin docente adjunto</option>
                  <?php foreach ($usuarios as $usuario): ?>
                    <option value="<?php echo (int) $usuario['idUsuario']; ?>"><?php echo htmlspecialchars($usuario['nombreUsuario'] . ' ' . $usuario['apellidoUsuario'], ENT_QUOTES, 'UTF-8'); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-12">
              <div class="form-group">
                <label>Banner de la sección</label>
                <div class="classroom-file">
                  <input type="file" class="classroom-file__input" id="bannerSeccionCrear" name="bannerSeccion" accept="image/*">
                  <label class="classroom-file__button" for="bannerSeccionCrear">
                    <i class="fas fa-image mr-2"></i>Seleccionar imagen
                  </label>
                  <span class="classroom-file__name">Ningún archivo seleccionado</span>
                </div>
                <small class="text-muted d-block mt-1">Si no cargás una imagen, se usará un degradado por defecto.</small>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Color inicio del degradado</label>
                <input type="color" class="form-control" name="colorInicioBanner" value="#0f172a">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Color fin del degradado</label>
                <input type="color" class="form-control" name="colorFinBanner" value="#1d4ed8">
              </div>
            </div>
          </div>

          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-4">
            <a href="index.php?r=listado-materias&c=materias" class="btn btn-light border">Cancelar</a>
            <button type="submit" class="btn btn-primary">Crear materia</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>
