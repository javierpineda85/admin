<?php
$curso = ModeloCursos::mdlBuscarCursoPorId($idCurso);
$clases = ModeloAsistenciasCurso::clases($idCurso);
$idClase = (int) ($_GET['idClase'] ?? ($clases[0]['idClase'] ?? 0));
$clase = $idClase > 0 ? ModeloAsistenciasCurso::clase($idClase, $idCurso) : null;
if ($clase && (int) $clase['id_curso'] !== $idCurso) {
    $clase = null;
}
$registros = $clase ? ModeloAsistenciasCurso::registros($idClase, $idCurso) : [];
$resumenEstudiantes = [];
foreach (ModeloAsistenciasCurso::resumenCurso($idCurso) as $resumen) {
    $resumenEstudiantes[(int) $resumen['id_estudiante']] = $resumen;
}
$e = static function ($valor) { return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8'); };
?>
<section class="content page-fade">
  <div class="container-fluid">
    <div class="entity-hero mb-4"><div class="entity-hero__content">
      <span class="entity-kicker mb-3">Consulta académica</span>
      <h1 class="entity-title mb-2">Asistencia de <?php echo $e($curso['nombreCurso'] ?? 'Curso'); ?></h1>
      <p class="entity-lead mb-0">Vista de solo lectura del historial y los registros del curso.</p>
    </div></div>

    <div class="row">
      <div class="col-lg-3 mb-3"><div class="card glass-card attendance-history">
        <div class="card-header"><h3 class="card-title mb-0">Clases</h3></div>
        <div class="list-group list-group-flush">
          <?php foreach ($clases as $itemClase): ?>
            <a class="list-group-item list-group-item-action <?php echo (int) $itemClase['idClase'] === $idClase ? 'active' : ''; ?>" href="index.php?r=asistencia-curso&amp;idCurso=<?php echo $idCurso; ?>&amp;idClase=<?php echo (int) $itemClase['idClase']; ?>">
              <strong><?php echo date('d/m/Y', strtotime($itemClase['fechaClase'])); ?></strong>
              <?php if (!empty($itemClase['tema'])): ?><small class="d-block text-truncate <?php echo (int) $itemClase['idClase'] === $idClase ? 'text-white' : 'text-muted'; ?>"><?php echo $e($itemClase['tema']); ?></small><?php endif; ?>
              <small class="d-block <?php echo (int) $itemClase['idClase'] === $idClase ? 'text-white' : 'text-muted'; ?>"><?php echo (int) $itemClase['presentes']; ?> presentes · <?php echo (int) $itemClase['ausentes']; ?> ausentes</small>
            </a>
          <?php endforeach; ?>
          <?php if (!$clases): ?><div class="p-3 text-muted">Todavía no hay clases registradas.</div><?php endif; ?>
        </div>
      </div></div>
      <div class="col-lg-9">
        <?php if (!$clase): ?>
          <div class="card glass-card"><div class="empty-state"><i class="fas fa-calendar"></i><h4>Elegí una clase</h4><p>Acá se mostrarán los registros de asistencia disponibles.</p></div></div>
        <?php else: ?>
          <div class="card glass-card">
            <div class="card-header section-header-soft"><h3 class="card-title mb-1"><?php echo date('d/m/Y', strtotime($clase['fechaClase'])); ?></h3><small><?php echo $e($clase['tema']); ?></small></div>
            <div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Estudiante</th><th>Estado</th><th>Observación</th><th>Acumulado</th></tr></thead><tbody>
              <?php foreach ($registros as $registro): $acumulado = $resumenEstudiantes[(int) $registro['id_estudiante']] ?? []; ?>
                <tr><td><strong><?php echo $e($registro['apellidoUsuario'] . ' ' . $registro['nombreUsuario']); ?></strong></td><td><?php echo $e(ucfirst(strtolower($registro['estado']))); ?></td><td><?php echo $e($registro['observacion']); ?></td><td><span class="text-danger font-weight-bold"><?php echo (int) ($acumulado['ausentes'] ?? 0); ?> faltas</span> · <?php echo (int) ($acumulado['tardanzas'] ?? 0); ?> tardanzas · <?php echo (int) ($acumulado['justificadas'] ?? 0); ?> justificadas</td></tr>
              <?php endforeach; ?>
            </tbody></table></div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
