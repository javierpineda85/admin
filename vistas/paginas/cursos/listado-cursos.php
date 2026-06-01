<?php

$db = new Conexion;
$cursos = $db->consultas("SELECT *, DATE_FORMAT(fechaInicioCurso, '%d/%m/%Y') AS fInicio, DATE_FORMAT(fechaFinCurso, '%d/%m/%Y') AS fFin FROM cursos ORDER BY nombreCurso ASC");

?>

<section class="content page-fade">
  <div class="container-fluid">
    <div class="entity-hero mb-4">
      <div class="entity-hero__content">
        <span class="entity-kicker mb-3">Cursos</span>
        <h1 class="entity-title mb-2">Listado de cursos</h1>
        <p class="entity-lead mb-0">Gestioná cada curso desde una vista más limpia, con acciones claras y el mismo lenguaje visual del sistema.</p>
      </div>
    </div>

    <div class="card glass-card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Cursos registrados</h3>
        <a href="index.php?r=crear-curso" class="btn btn-primary btn-sm">Nuevo curso</a>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table id="example1" class="table table-hover table-striped mb-0">
            <thead>
              <tr>
                <th class="text-center">Curso</th>
                <th class="text-center">Estado</th>
                <th class="text-center">Inicio</th>
                <th class="text-center">Horario</th>
                <th class="text-center">Finalización</th>
                <th class="text-center">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($cursos as $curso): ?>
                <tr>
                  <td><?php echo htmlspecialchars($curso['nombreCurso'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($curso['estado'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($curso['fInicio'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars((string) $curso['horarioCurso'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($curso['fFin'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td class="text-center">
                    <div class="d-inline-flex align-items-center gap-2">
                      <a href="index.php?r=detalle-curso&c=cursos&idCurso=<?php echo (int) $curso['idCurso']; ?>" class="btn btn-info btn-sm" title="Ver detalle">
                        <i class="far fa-eye"></i>
                      </a>
                      <a href="index.php?r=editar-curso&idCurso=<?php echo (int) $curso['idCurso']; ?>" class="btn btn-success btn-sm" title="Editar">
                        <i class="fas fa-edit"></i>
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</section>
