<?php
$idSeccion = (int) ($_GET['idSeccion'] ?? 0);
$seccion = ControladorLecciones::crtBuscarSeccionPorId($idSeccion);
$idUsuarioActual = (int) ($_SESSION['usuario']['id'] ?? 0);
$esEstudiante = ControladorPermisos::esEstudiante();
$esDocente = ControladorPermisos::esDocente();
$esAdmin = ControladorPermisos::esAdministrador();

if (!$seccion) {
    $seccion = [
        'tituloSeccion' => 'Sección no encontrada',
        'nombreCurso' => '',
        'nombreUsuario' => '',
        'apellidoUsuario' => '',
        'contenidoSeccion' => '',
        'bannerSeccion' => '',
        'colorInicioBanner' => '#0f172a',
        'colorFinBanner' => '#1d4ed8',
    ];
}

$calificaciones = $esEstudiante
    ? ControladorCalificaciones::crtCalificacionesPorEstudiante($idSeccion, $idUsuarioActual)
    : ControladorCalificaciones::crtCalificacionesPorSeccion($idSeccion);

$tieneAcceso = true;
if ($esEstudiante) {
    $tieneAcceso = $seccion && !empty($seccion['id_curso'])
        && ControladorCursos::crtEstudianteInscriptoCurso($idUsuarioActual, (int) $seccion['id_curso']);
} elseif ($esDocente && !$esAdmin) {
    $tieneAcceso = $seccion && ControladorLecciones::crtSeccionAsignadaDocente($idSeccion, $idUsuarioActual);
}

if (!$tieneAcceso) {
    ?>
    <section class="content page-fade">
      <div class="container-fluid">
        <div class="empty-state">
          <i class="fas fa-lock"></i>
          <h4>No tenés acceso a estas calificaciones</h4>
          <p class="mb-3">Sólo podés ver notas de tus materias asignadas.</p>
          <a href="index.php?r=listado-cursos" class="btn btn-primary">Volver</a>
        </div>
      </div>
    </section>
    <?php
    return;
}

$heroStyle = 'background: linear-gradient(135deg, ' . htmlspecialchars((string) ($seccion['colorInicioBanner'] ?? '#0f172a'), ENT_QUOTES, 'UTF-8') . ', ' . htmlspecialchars((string) ($seccion['colorFinBanner'] ?? '#1d4ed8'), ENT_QUOTES, 'UTF-8') . ');';
if (!empty($seccion['bannerSeccion'])) {
    $heroStyle = 'background-image: linear-gradient(135deg, rgba(15, 23, 42, 0.88), rgba(29, 78, 216, 0.74)), url(\'img/' . htmlspecialchars((string) $seccion['bannerSeccion'], ENT_QUOTES, 'UTF-8') . '\'); background-size: cover; background-position: center;';
}
?>

<section class="content page-fade">
  <div class="container-fluid">
    <div class="entity-hero mb-4" style="<?php echo $heroStyle; ?>">
      <div class="entity-hero__content">
        <span class="entity-kicker mb-3"><?php echo htmlspecialchars($seccion['nombreCurso'] ?? 'Curso', ENT_QUOTES, 'UTF-8'); ?></span>
        <h1 class="entity-title mb-2">Calificaciones de <?php echo htmlspecialchars($seccion['tituloSeccion'] ?? 'Materia', ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="entity-lead mb-0">Notas, devoluciones y seguimiento académico en un solo lugar.</p>
      </div>
    </div>

    <div class="card glass-card">
      <div class="card-header section-header-soft d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Detalle académico</h3>
        <a href="index.php?r=detalle-seccion&idSeccion=<?php echo (int) $idSeccion; ?>" class="btn btn-light border btn-sm">Volver a la materia</a>
      </div>
      <div class="card-body">
        <?php if (empty($calificaciones)): ?>
          <div class="empty-state">
            <i class="fas fa-star"></i>
            <h4>No hay calificaciones para mostrar</h4>
            <p class="mb-0">Cuando haya notas cargadas, se van a ver acá con su devolución.</p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover table-bordered mb-0">
              <thead>
                <tr>
                  <?php if (!$esEstudiante): ?>
                    <th>Estudiante</th>
                  <?php endif; ?>
                  <th>Actividad</th>
                  <th>Tipo</th>
                  <th>Nota</th>
                  <th>Devolución</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($calificaciones as $calificacion): ?>
                  <tr>
                    <?php if (!$esEstudiante): ?>
                      <td><?php echo htmlspecialchars(trim(($calificacion['apellidoUsuario'] ?? '') . ' ' . ($calificacion['nombreUsuario'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></td>
                    <?php endif; ?>
                    <td><?php echo htmlspecialchars((string) ($calificacion['nombreLeccion'] ?? 'Actividad'), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string) ($calificacion['tipoLeccion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><strong><?php echo (int) ($calificacion['calificacion'] ?? 0); ?></strong></td>
                    <td><?php echo htmlspecialchars((string) ($calificacion['devolucion'] ?? 'Sin devolución'), ENT_QUOTES, 'UTF-8'); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
