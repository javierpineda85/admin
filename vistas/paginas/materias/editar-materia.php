<?php
$idSeccion = (int) ($_GET['idSeccion'] ?? $_GET['id'] ?? 0);
$db = new Conexion;
$materia = $db->consultas("SELECT * FROM secciones WHERE idSeccion = $idSeccion");
$materia = $materia[0] ?? [
    'idSeccion' => $idSeccion,
    'tituloSeccion' => '',
    'contenidoSeccion' => '',
    'id_curso' => '',
    'docente' => '',
    'tutor' => '',
    'bannerSeccion' => '',
    'colorInicioBanner' => '#0f172a',
    'colorFinBanner' => '#1d4ed8',
];
$db = new Conexion;
$cursos = $db->consultas("SELECT * FROM cursos ORDER BY nombreCurso ASC");
$usuarios = ControladorUsuarios::crtUsuariosDocentesAsignables();
$registro = ControladorMaterias::crtModificarMateria();
?>
<section class="content page-fade">
  <div class="container-fluid">
    <div class="entity-hero mb-4">
      <div class="entity-hero__content">
        <span class="entity-kicker mb-3">Secciones</span>
        <h1 class="entity-title mb-2">Editar materia</h1>
        <p class="entity-lead mb-0">Mantené la misma identidad visual del sistema mientras actualizás curso, docente y contenido.</p>
      </div>
    </div>

    <div class="card glass-card">
      <div class="card-header">
        <h3 class="card-title mb-0"><?php echo htmlspecialchars($materia['tituloSeccion'], ENT_QUOTES, 'UTF-8'); ?></h3>
      </div>
      <div class="card-body">
        <form action="" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="idSeccion" value="<?php echo (int) $materia['idSeccion']; ?>">
          <div class="row">
            <div class="col-12">
              <div class="form-group">
                <label>Título de la materia</label>
                <input type="text" class="form-control" name="tituloSeccion" value="<?php echo htmlspecialchars($materia['tituloSeccion'], ENT_QUOTES, 'UTF-8'); ?>" required>
              </div>
            </div>
            <div class="col-12">
              <div class="form-group">
                <label>Descripción</label>
                <textarea class="form-control" rows="4" name="contenidoSeccion"><?php echo htmlspecialchars((string) $materia['contenidoSeccion'], ENT_QUOTES, 'UTF-8'); ?></textarea>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Curso</label>
                <select class="custom-select" name="id_curso" required>
                  <?php foreach ($cursos as $curso): ?>
                    <option value="<?php echo (int) $curso['idCurso']; ?>" <?php echo (int) $materia['id_curso'] === (int) $curso['idCurso'] ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($curso['nombreCurso'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Docente a cargo</label>
                <select class="custom-select" name="docente" required>
                  <?php foreach ($usuarios as $usuario): ?>
                    <option value="<?php echo (int) $usuario['idUsuario']; ?>" <?php echo (int) $materia['docente'] === (int) $usuario['idUsuario'] ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($usuario['nombreUsuario'] . ' ' . $usuario['apellidoUsuario'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Tutor</label>
                <select class="custom-select" name="tutor">
                  <option value="">Sin tutor</option>
                  <?php foreach ($usuarios as $usuario): ?>
                    <option value="<?php echo (int) $usuario['idUsuario']; ?>" <?php echo (int) $materia['tutor'] === (int) $usuario['idUsuario'] ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($usuario['nombreUsuario'] . ' ' . $usuario['apellidoUsuario'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-12">
              <div class="form-group">
                <label>Banner de la sección</label>
                <div class="classroom-file">
                  <input type="file" class="classroom-file__input" id="bannerSeccionEditar" name="bannerSeccion" accept="image/*">
                  <label class="classroom-file__button" for="bannerSeccionEditar">
                    <i class="fas fa-image mr-2"></i>Seleccionar imagen
                  </label>
                  <span class="classroom-file__name">Ningún archivo seleccionado</span>
                </div>
                <?php if (!empty($materia['bannerSeccion'])): ?>
                  <small class="text-muted d-block mt-1">Banner actual: <?php echo htmlspecialchars((string) $materia['bannerSeccion'], ENT_QUOTES, 'UTF-8'); ?></small>
                <?php endif; ?>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Color inicio del degradado</label>
                <input type="color" class="form-control" name="colorInicioBanner" value="<?php echo htmlspecialchars((string) $materia['colorInicioBanner'], ENT_QUOTES, 'UTF-8'); ?>">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Color fin del degradado</label>
                <input type="color" class="form-control" name="colorFinBanner" value="<?php echo htmlspecialchars((string) $materia['colorFinBanner'], ENT_QUOTES, 'UTF-8'); ?>">
              </div>
            </div>
          </div>

          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-4">
            <a href="index.php?r=detalle-seccion&idSeccion=<?php echo (int) $idSeccion; ?>" class="btn btn-light border">Volver</a>
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>
