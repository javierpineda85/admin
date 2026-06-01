<?php

$db = new Conexion;
$materias = $db->consultas("
    SELECT s.idSeccion, s.tituloSeccion, s.contenidoSeccion, s.id_curso, s.docente, s.tutor,
           c.nombreCurso, u.nombreUsuario, u.apellidoUsuario
    FROM secciones s
    INNER JOIN cursos c ON s.id_curso = c.idCurso
    INNER JOIN usuarios u ON s.docente = u.idUsuario
    ORDER BY s.tituloSeccion ASC
");

?>

<section class="content page-fade">
  <div class="container-fluid">
    <div class="entity-hero mb-4">
      <div class="entity-hero__content">
        <span class="entity-kicker mb-3">Secciones</span>
        <h1 class="entity-title mb-2">Listado de secciones</h1>
        <p class="entity-lead mb-0">Ordená materias por curso y docente con una interfaz coherente con cursos y usuarios.</p>
      </div>
    </div>

    <div class="card glass-card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Secciones registradas</h3>
        <a href="index.php?r=crear-materia" class="btn btn-primary btn-sm">Nueva sección</a>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table id="example1" class="table table-hover table-striped mb-0">
            <thead>
              <tr>
                <th class="text-center">Materia</th>
                <th class="text-center">Descripción</th>
                <th class="text-center">Curso</th>
                <th class="text-center">Docente</th>
                <th class="text-center">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($materias as $materia): ?>
                <tr>
                  <td><?php echo htmlspecialchars($materia['tituloSeccion'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars((string) $materia['contenidoSeccion'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($materia['nombreCurso'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($materia['nombreUsuario'] . ' ' . $materia['apellidoUsuario'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td class="text-center">
                    <div class="d-inline-flex align-items-center gap-2">
                      <a href="index.php?r=detalle-seccion&idSeccion=<?php echo (int) $materia['idSeccion']; ?>" class="btn btn-info btn-sm" title="Ver aula">
                        <i class="far fa-eye"></i>
                      </a>
                      <a href="index.php?r=editar-materia&idSeccion=<?php echo (int) $materia['idSeccion']; ?>" class="btn btn-success btn-sm" title="Editar">
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
