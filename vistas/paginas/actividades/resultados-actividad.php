<?php
$idActividad = (int) ($_GET['idActividad'] ?? 0);
$actividad = ControladorActividades::crtBuscarActividadPorId($idActividad);
$e = static function ($valor) {
  return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
};

if (!$actividad || !ControladorActividades::puedeGestionarActividad($actividad)) {
?>
  <section class="content page-fade">
    <div class="container-fluid">
      <div class="empty-state">
        <i class="fas fa-lock"></i>
        <h4>No tenes acceso a los resultados</h4>
        <a href="index.php?r=listado-actividades" class="btn btn-primary">Volver</a>
      </div>
    </div>
  </section>
<?php
  return;
}

$intentos = ControladorActividades::crtIntentosActividad($idActividad);
?>

<section class="content page-fade">
  <div class="container-fluid">
    <div class="entity-hero mb-4">
      <div class="entity-hero__content">
        <span class="entity-kicker mb-3">Resultados</span>
        <h1 class="entity-title mb-2"><?php echo $e($actividad['tituloActividad']); ?></h1>
        <p class="entity-lead mb-0">Intentos registrados para estudiantes y visitantes.</p>
      </div>
    </div>

    <div class="card glass-card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Intentos</h3>
        <a href="index.php?r=listado-actividades" class="btn btn-light btn-sm border">Volver</a>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table id="example1" class="table table-hover table-striped mb-0">
            <thead>
              <tr>
                <th>Persona</th>
                <th>Email</th>
                <th>Puntaje</th>
                <th>Estado</th>
                <th>Fecha</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($intentos as $intento): ?>
                <tr>
                  <td><?php echo $e(trim(($intento['apellidoUsuario'] ?? '') . ' ' . ($intento['nombreUsuario'] ?? '')) ?: ($intento['nombreVisitante'] ?? 'Visitante')); ?></td>
                  <td><?php echo $e($intento['email'] ?? $intento['emailVisitante'] ?? ''); ?></td>
                  <td><?php echo (float) $intento['puntaje']; ?> / <?php echo (float) $actividad['puntajeMaximo']; ?></td>
                  <td><?php echo $e($intento['estadoIntento']); ?></td>
                  <td><?php echo $e($intento['fechaEntrega']); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</section>
