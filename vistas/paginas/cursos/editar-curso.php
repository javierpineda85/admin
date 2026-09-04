<?php
$idCurso = (int) ($_GET['idCurso'] ?? $_GET['id'] ?? 0);
if (!ControladorCursos::crtPuedeGestionarCurso($idCurso)) {
    $_SESSION['error_message'] = 'No podes editar un curso que no esta a tu cargo.';
    header('Location: index.php?r=listado-cursos');
    exit;
}
$db = new Conexion;
$curso = $db->consultas("SELECT * FROM cursos WHERE idCurso = $idCurso");
$curso = $curso[0] ?? [
    'idCurso' => $idCurso,
    'nombreCurso' => '',
    'contenidoCurso' => '',
    'estado' => 'En Curso',
    'fechaInicioCurso' => '',
    'fechaFinCurso' => '',
    'horarioCurso' => '',
];
$registro = ControladorCursos::crtModificarCurso();
?>

<section class="content page-fade">
  <div class="container-fluid">
    <div class="entity-hero mb-4">
      <div class="entity-hero__content">
        <span class="entity-kicker mb-3">Cursos</span>
        <h1 class="entity-title mb-2">Editar curso</h1>
        <p class="entity-lead mb-0">Ajustá datos generales, calendario y estado desde una vista consistente con el resto del sistema.</p>
      </div>
    </div>

    <div class="card glass-card">
      <div class="card-header">
        <h3 class="card-title mb-0"><?php echo htmlspecialchars($curso['nombreCurso'], ENT_QUOTES, 'UTF-8'); ?></h3>
      </div>
      <div class="card-body">
        <form action="" method="POST">
          <input type="hidden" name="idCurso" value="<?php echo (int) $curso['idCurso']; ?>">
          <div class="row">
            <div class="col-lg-6">
              <div class="form-group">
                <label>Nombre del curso</label>
                <input type="text" class="form-control" name="nombreCurso" value="<?php echo htmlspecialchars($curso['nombreCurso'], ENT_QUOTES, 'UTF-8'); ?>" required>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="form-group">
                <label>Estado</label>
                <select class="form-control custom-select" name="estado" required>
                  <?php foreach (['En Curso', 'Programado', 'Cancelado', 'Finalizado'] as $estado): ?>
                    <option value="<?php echo $estado; ?>" <?php echo ($curso['estado'] ?? '') === $estado ? 'selected' : ''; ?>><?php echo $estado; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-12">
              <div class="form-group">
                <label>Descripción</label>
                <textarea class="form-control" rows="4" name="contenidoCurso"><?php echo htmlspecialchars((string) $curso['contenidoCurso'], ENT_QUOTES, 'UTF-8'); ?></textarea>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Fecha de inicio</label>
                <input type="date" class="form-control" name="fechaInicioCurso" value="<?php echo htmlspecialchars((string) $curso['fechaInicioCurso'], ENT_QUOTES, 'UTF-8'); ?>" required>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Fecha de finalización</label>
                <input type="date" class="form-control" name="fechaFinCurso" value="<?php echo htmlspecialchars((string) $curso['fechaFinCurso'], ENT_QUOTES, 'UTF-8'); ?>">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Horario cursado</label>
                <input type="time" class="form-control" name="horarioCurso" value="<?php echo htmlspecialchars((string) $curso['horarioCurso'], ENT_QUOTES, 'UTF-8'); ?>">
              </div>
            </div>
          </div>

          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-4">
            <a href="index.php?r=detalle-curso&idCurso=<?php echo (int) $idCurso; ?>" class="btn btn-light border">Volver</a>
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>
