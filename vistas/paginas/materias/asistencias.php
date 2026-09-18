<?php
$esEstudiante = ControladorPermisos::esEstudiante();
$e = static function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$disponible = ModeloAsistenciasCurso::disponible();
$cursos = !$esEstudiante && $disponible ? ModeloAsistenciasCurso::cursos(ControladorPermisos::esAdministrador() ? 0 : (int)($_SESSION['usuario']['id'] ?? 0)) : [];
$resumen = $esEstudiante && $disponible ? ModeloAsistenciasCurso::resumenEstudiante(ControladorPermisos::idEstudianteContexto()) : [];
$historial = $esEstudiante ? ControladorAsistencias::crtResumenEstudiante(ControladorPermisos::idEstudianteContexto()) : array_filter(ControladorAsistencias::crtSecciones(), static function ($s) { return (int)$s['clases'] > 0; });
?>
<section class="content page-fade"><div class="container-fluid">
  <div class="entity-hero mb-4"><div class="entity-hero__content">
    <span class="entity-kicker mb-3">Seguimiento académico</span><h1 class="entity-title mb-2">Asistencia por curso</h1>
    <p class="entity-lead mb-0"><?php echo $esEstudiante ? 'Tu asistencia diaria en cada curso.' : 'Elegí un curso para tomar asistencia una vez por fecha o consultar su historial.'; ?></p>
  </div></div>
  <?php if (!$disponible): ?><div class="alert alert-info">La asistencia por curso todavía no está habilitada. Contactá con la administración del campus.</div><?php endif; ?>
  <?php if ($esEstudiante): ?>
    <div class="row">
    <?php foreach ($resumen as $r): ?>
      <div class="col-md-6 col-xl-4 mb-3"><div class="card glass-card h-100"><div class="card-body">
        <h4><?php echo $e($r['curso']); ?></h4><div class="display-4 font-weight-bold text-primary"><?php echo $e($r['porcentaje']); ?>%</div>
        <p class="text-muted">Asistencia computada · <?php echo (int)$r['clases']; ?> clases</p>
        <?php foreach (['presentes'=>'success','ausentes'=>'danger','tardanzas'=>'warning','justificadas'=>'info'] as $estado=>$color): ?>
          <span class="badge badge-<?php echo $color; ?>"><?php echo ucfirst($estado).' '.(int)$r[$estado]; ?></span>
        <?php endforeach; ?>
      </div></div></div>
    <?php endforeach; ?>
    </div>
    <?php if ($disponible && !$resumen): ?><div class="empty-state"><i class="fas fa-calendar-check"></i><h4>Todavía no hay asistencias por curso registradas</h4></div><?php endif; ?>
  <?php else: ?>
    <label for="buscar-curso-asistencia">Buscar curso</label><input id="buscar-curso-asistencia" type="search" class="form-control mb-3" placeholder="Nombre del curso...">
    <div class="row">
    <?php foreach ($cursos as $curso): ?>
      <div class="col-md-6 col-xl-4 mb-3" data-attendance-course="<?php echo $e($curso['curso']); ?>"><div class="card glass-card h-100"><div class="card-body">
        <h4><?php echo $e($curso['curso']); ?></h4><p><strong><?php echo (int)$curso['clases']; ?></strong> clases registradas</p>
        <a class="btn btn-primary btn-sm" href="index.php?r=asistencia-curso&amp;idCurso=<?php echo (int)$curso['idCurso']; ?>"><i class="fas fa-user-check mr-1"></i>Abrir asistencia</a>
      </div></div></div>
    <?php endforeach; ?>
    </div><p class="text-muted <?php echo $cursos ? 'd-none' : ''; ?>" id="sin-cursos-asistencia">No hay cursos para mostrar.</p>
  <?php endif; ?>
  <?php if ($historial): ?>
    <details class="card glass-card p-3 mt-4"><summary>Historial anterior por materia</summary>
      <p class="text-muted mt-3">Estos registros se conservan por separado y no se suman a la asistencia por curso.</p>
      <div class="table-responsive"><table class="table"><thead><tr><th>Curso</th><th>Materia</th><th>Clases</th><th><?php echo $esEstudiante ? 'Ausencias' : 'Consulta'; ?></th></tr></thead><tbody>
      <?php foreach ($historial as $h): ?><tr><td><?php echo $e($h['curso']); ?></td><td><?php echo $e($h['materia']); ?></td><td><?php echo (int)$h['clases']; ?></td><td>
        <?php if ($esEstudiante): ?><?php echo (int)$h['ausentes']; ?> faltas · <?php echo (int)$h['tardanzas']; ?> tardanzas · <?php echo (int)$h['justificadas']; ?> justificadas
        <?php else: ?><a href="index.php?r=asistencia-seccion&amp;idSeccion=<?php echo (int)$h['idSeccion']; ?>">Ver historial</a><?php endif; ?>
      </td></tr><?php endforeach; ?>
      </tbody></table></div>
    </details>
  <?php endif; ?>
</div></section>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var input = document.getElementById('buscar-curso-asistencia');
    if (!input) return;
    input.addEventListener('input', function () {
        var query = input.value.toLocaleLowerCase().trim(), visible = 0;
        document.querySelectorAll('[data-attendance-course]').forEach(function (card) {
            var show = card.dataset.attendanceCourse.toLocaleLowerCase().indexOf(query) !== -1;
            card.classList.toggle('d-none', !show);
            if (show) visible++;
        });
        document.getElementById('sin-cursos-asistencia').classList.toggle('d-none', visible > 0);
    });
});
</script>