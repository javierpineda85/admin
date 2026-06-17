<?php
ControladorActividades::crtProcesarAcciones();

$idActividad = (int) ($_GET['idActividad'] ?? 0);
$actividad = ControladorActividades::crtBuscarActividadPorId($idActividad);

if (!$actividad || !ControladorActividades::puedeGestionarActividad($actividad)) {
?>
  <section class="content page-fade">
    <div class="container-fluid">
      <div class="empty-state">
        <i class="fas fa-lock"></i>
        <h4>No tenes acceso a esta actividad</h4>
        <p class="mb-3">Solo el autor, el docente asignado o el administrador pueden editarla.</p>
        <a href="index.php?r=listado-actividades" class="btn btn-primary">Volver</a>
      </div>
    </div>
  </section>
<?php
  return;
}

$preguntas = ControladorActividades::crtPreguntasActividad($idActividad);
include __DIR__ . '/_formulario-actividad.php';
