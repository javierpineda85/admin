<?php
$idUsuarioActual = (int) ($_SESSION['usuario']['id'] ?? 0);
$esAdmin = ControladorPermisos::esAdministrador();
$esDocente = ControladorPermisos::esDocente();

$db = new Conexion;
$materias = $esDocente && !$esAdmin
    ? ControladorMaterias::crtBuscarMateriasPorDocente($idUsuarioActual)
    : $db->consultas("
        SELECT s.idSeccion, s.tituloSeccion, s.contenidoSeccion, s.id_curso, s.docente, s.tutor,
               s.bannerSeccion, s.colorInicioBanner, s.colorFinBanner,
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
        <?php if ($esAdmin): ?>
          <a href="index.php?r=crear-materia" class="btn btn-primary btn-sm">Nueva sección</a>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <?php if ($esDocente && !$esAdmin): ?>
          <div class="student-class-grid">
            <?php foreach ($materias as $index => $materia): ?>
              <div class="student-class-card theme-<?php echo (int) ($index % 4); ?>">
                <div class="student-class-card__cover">
                  <div>
                    <h2><?php echo htmlspecialchars($materia['tituloSeccion'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p><?php echo htmlspecialchars($materia['nombreCurso'], ENT_QUOTES, 'UTF-8'); ?></p>
                  </div>
                </div>
                <div class="student-class-card__body">
                  <p><?php echo htmlspecialchars((string) $materia['contenidoSeccion'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <div class="student-class-card__footer">
                  <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($materia['nombreUsuario'] . ' ' . $materia['apellidoUsuario'], ENT_QUOTES, 'UTF-8'); ?></span>
                  <span class="d-inline-flex align-items-center" style="gap:.5rem;">
                    <a href="index.php?r=detalle-seccion&idSeccion=<?php echo (int) $materia['idSeccion']; ?>" class="btn btn-sm btn-light border">Ver</a>
                    <a href="index.php?r=editar-materia&idSeccion=<?php echo (int) $materia['idSeccion']; ?>" class="btn btn-sm btn-primary">Editar</a>
                  </span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
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
                        <?php if ($esAdmin): ?>
                          <a href="index.php?r=editar-materia&idSeccion=<?php echo (int) $materia['idSeccion']; ?>" class="btn btn-success btn-sm" title="Editar">
                            <i class="fas fa-edit"></i>
                          </a>
                        <?php endif; ?>
                      </div>
                    </td>
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
