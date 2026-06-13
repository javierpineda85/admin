<?php
$registro = ControladorCursos::crtGuardarCurso();
$redirigir = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['success_message']));
?>

<?php if ($redirigir): ?>
  <script>
    setTimeout(function () {
      window.location.href = 'index.php?r=listado-cursos&c=cursos';
    }, 5200);
  </script>
<?php endif; ?>

<section class="content page-fade">
  <div class="container-fluid">
    <div class="entity-hero mb-4">
      <div class="entity-hero__content">
        <span class="entity-kicker mb-3">Cursos</span>
        <h1 class="entity-title mb-2">Crear nuevo curso</h1>
        <p class="entity-lead mb-0">Definí la base del curso y su calendario principal con una vista más clara y coherente.</p>
      </div>
    </div>

    <div class="card glass-card">
      <div class="card-header">
        <h3 class="card-title mb-0">Datos del curso</h3>
      </div>
      <div class="card-body">
        <form action="" method="POST">
          <div class="row">
            <div class="col-lg-6">
              <div class="form-group">
                <label>Nombre del curso</label>
                <input type="text" class="form-control" name="nombreCurso" placeholder="Ej. 3° Año A" required>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="form-group">
                <label>Estado</label>
                <select class="form-control custom-select" name="estado" required>
                  <option selected disabled>Seleccionar una opción</option>
                  <option value="En Curso">En curso</option>
                  <option value="Programado">Programado</option>
                  <option value="Cancelado">Cancelado</option>
                  <option value="Finalizado">Finalizado</option>
                </select>
              </div>
            </div>
            <div class="col-12">
              <div class="form-group">
                <label>Descripción</label>
                <textarea class="form-control" rows="4" name="contenidoCurso" placeholder="Descripción general del curso..."></textarea>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Fecha de inicio</label>
                <input type="date" class="form-control" name="fechaInicioCurso" required>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Fecha de finalización</label>
                <input type="date" class="form-control" name="fechaFinCurso">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Horario cursado</label>
                <input type="time" class="form-control" name="horarioCurso">
              </div>
            </div>
          </div>

          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-4">
            <a href="index.php?r=listado-cursos&c=cursos" class="btn btn-light border">Cancelar</a>
            <button type="submit" class="btn btn-primary">Crear curso</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>
