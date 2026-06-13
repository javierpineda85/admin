<?php
$idCurso = (int) ($_GET['idCurso'] ?? 0);
$rolActual = ControladorPermisos::rolActual();
$esAdmin = ControladorPermisos::esAdministrador();
$esDocente = ControladorPermisos::esDocente();
$idUsuarioActual = (int) ($_SESSION['usuario']['id'] ?? 0);

$db = new Conexion;
$sql = "SELECT * FROM cursos WHERE idCurso = $idCurso";
$curso = $db->consultas($sql);

$db = new Conexion;
$sql = "SELECT * FROM usuarios WHERE rol = 'ESTUDIANTE' ORDER BY apellidoUsuario ASC, nombreUsuario ASC";
$estudiantes = $db->consultas($sql);

$db = new Conexion;
$sql = "SELECT * FROM asignacioncursos
        RIGHT JOIN usuarios ON asignacioncursos.id_estudiante = usuarios.idUsuario
        WHERE usuarios.rol = 'ESTUDIANTE'
        AND asignacioncursos.id_seccion = $idCurso";
$cursantes = $db->consultas($sql);

$secciones = $esDocente && !$esAdmin
    ? ControladorCursos::crtSeccionesPorCursoParaDocente($idCurso, $idUsuarioActual)
    : ControladorCursos::crtSeccionesPorCurso($idCurso);

if (!$curso) {
    $curso = [[
        'nombreCurso' => 'Curso no encontrado',
        'estado' => '',
        'contenidoCurso' => '',
        'fechaInicioCurso' => '',
        'fechaFinCurso' => '',
        'horarioCurso' => '',
    ]];
}

if (ControladorPermisos::esEstudiante()) {
    $idUsuarioActual = (int) ($_SESSION['usuario']['id'] ?? 0);
    $cursoEstudiante = ControladorCursos::crtBuscarCursoPorId($idCurso);
    $estaInscripto = $idCurso > 0 && ControladorCursos::crtEstudianteInscriptoCurso($idUsuarioActual, $idCurso);
    $seccionesEstudiante = $estaInscripto ? ControladorCursos::crtSeccionesPorCurso($idCurso) : [];
    $e = static function ($valor) {
        return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
    };
    ?>

    <section class="content page-fade">
      <div class="container-fluid">
        <?php if (!$cursoEstudiante || !$estaInscripto): ?>
          <div class="empty-state">
            <i class="fas fa-lock"></i>
            <h4>No tenés acceso a este curso</h4>
            <p class="mb-3">Solo podés ver cursos en los que estás inscripto.</p>
            <a href="index.php?r=listado-cursos" class="btn btn-primary">Volver a mis cursos</a>
          </div>
        <?php else: ?>
          <div class="student-course-hero mb-4">
            <div class="student-course-hero__content">
              <span class="entity-kicker mb-3">Curso</span>
              <h1><?php echo $e($cursoEstudiante['nombreCurso'] ?? 'Curso'); ?></h1>
              <p><?php echo $e($cursoEstudiante['contenidoCurso'] ?? ''); ?></p>
              <div class="d-flex flex-wrap" style="gap: .6rem;">
                <span class="entity-chip"><i class="fas fa-circle"></i><?php echo $e($cursoEstudiante['estado'] ?? 'Activo'); ?></span>
                <span class="entity-chip"><i class="fas fa-calendar"></i><?php echo $e($cursoEstudiante['fInicio'] ?? ''); ?></span>
                <span class="entity-chip"><i class="fas fa-book-open"></i><?php echo count($seccionesEstudiante); ?> materias</span>
              </div>
            </div>
          </div>

          <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
            <div>
              <div class="section-title">Materias del curso</div>
              <div class="section-subtitle">Elegí una materia para entrar al tablón, tareas, materiales y foro.</div>
            </div>
            <a href="index.php?r=listado-cursos" class="btn btn-light border">
              <i class="fas fa-arrow-left mr-1"></i>Mis cursos
            </a>
          </div>

          <?php if (empty($seccionesEstudiante)): ?>
            <div class="empty-state">
              <i class="fas fa-book"></i>
              <h4>Este curso todavía no tiene materias</h4>
              <p class="mb-0">Cuando se carguen materias, las vas a ver acá.</p>
            </div>
          <?php else: ?>
            <div class="student-class-grid">
              <?php foreach ($seccionesEstudiante as $index => $seccionItem): ?>
                <a class="student-class-card theme-<?php echo (int) (($index + 1) % 4); ?>" href="index.php?r=detalle-seccion&idSeccion=<?php echo (int) $seccionItem['idSeccion']; ?>">
                  <div class="student-class-card__cover">
                    <div>
                      <h2><?php echo $e($seccionItem['tituloSeccion'] ?? 'Materia'); ?></h2>
                      <p><?php echo $e(trim(($seccionItem['nombreUsuario'] ?? '') . ' ' . ($seccionItem['apellidoUsuario'] ?? ''))); ?></p>
                    </div>
                  </div>
                  <div class="student-class-card__body">
                    <p><?php echo $e($seccionItem['contenidoSeccion'] ?? 'Sin descripción cargada.'); ?></p>
                  </div>
                  <div class="student-class-card__footer">
                    <span><i class="fas fa-tasks"></i> <?php echo (int) ($seccionItem['totalLecciones'] ?? 0); ?> clases</span>
                    <span><i class="fas fa-file-alt"></i> <?php echo (int) ($seccionItem['totalTareas'] ?? 0); ?> tareas</span>
                  </div>
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </section>
    <?php
    return;
}
?>

<section class="content page-fade">
  <div class="container-fluid">
    <div class="entity-hero mb-4">
      <div class="entity-hero__content">
        <span class="entity-kicker mb-3">Cursos</span>
        <h1 class="entity-title mb-2"><?php echo htmlspecialchars($curso[0]['nombreCurso'], ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="entity-lead mb-0">Gestioná el aula, sus secciones y la inscripción de estudiantes con una vista más clara y coherente.</p>
      </div>
    </div>

<div class="row">
    <div class="card-body col-12">
        <div class="alert alert-info border-0 shadow-sm">
            Estás dentro del detalle del curso como <strong><?php echo htmlspecialchars($rolActual !== '' ? $rolActual : 'usuario', ENT_QUOTES, 'UTF-8'); ?></strong>.
            <?php if ($esAdmin): ?>
                Podés editar el curso e inscribir estudiantes.
            <?php else: ?>
                Desde acá podés revisar la información general y la composición del aula.
            <?php endif; ?>
        </div>

        <section class="content">
            <div class="row">
                <div class="col-12 col-lg-6">
                    <div class="card card-primary">
                        <div class="card-header bg-secondary">
                            <h3 class="card-title">General del curso: <b><?php echo htmlspecialchars($curso[0]['nombreCurso'], ENT_QUOTES, 'UTF-8'); ?></b></h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="" method="post">
                                <input type="hidden" name="idCurso" value="<?php echo $idCurso; ?>">
                                <div class="row">
                                    <div class="form-group col-md-4 col-sm-12">
                                        <label for="inputName">Nombre del Curso</label>
                                        <input type="text" class="form-control form-control-sm" name="nombreCurso" value="<?php echo htmlspecialchars($curso[0]['nombreCurso'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo !$esAdmin ? 'readonly' : ''; ?>>
                                    </div>
                                    <div class="form-group col-md-4 col-sm-12">
                                        <label for="inputStatus">Estado</label>
                                        <select id="inputStatus" class="form-control form-control-sm" name="estado" <?php echo !$esAdmin ? 'disabled' : ''; ?>>
                                            <option value="<?php echo htmlspecialchars($curso[0]['estado'], ENT_QUOTES, 'UTF-8'); ?>" selected><?php echo htmlspecialchars($curso[0]['estado'], ENT_QUOTES, 'UTF-8'); ?></option>
                                            <option value="En Curso">En curso</option>
                                            <option value="Programado">Programado</option>
                                            <option value="Cancelado">Cancelado</option>
                                            <option value="Finalizado">Finalizado</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-4 col-sm-12">
                                        <label for="inputDescription">Descripción</label>
                                        <textarea id="inputDescription" class="form-control form-control-sm" rows="1" name="contenidoCurso" <?php echo !$esAdmin ? 'readonly' : ''; ?>><?php echo htmlspecialchars($curso[0]['contenidoCurso'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-4 col-sm-12">
                                        <label for="inputEstimatedBudget">Inicio:</label>
                                        <input type="date" id="inputEstimatedBudget" class="form-control form-control-sm" name="fechaInicioCurso" value="<?php echo htmlspecialchars($curso[0]['fechaInicioCurso'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo !$esAdmin ? 'readonly' : ''; ?>>
                                    </div>
                                    <div class="form-group col-md-4 col-sm-12">
                                        <label for="inputSpentBudget">Finaliza:</label>
                                        <input type="date" id="inputSpentBudget" class="form-control form-control-sm" name="fechaFinCurso" value="<?php echo htmlspecialchars((string) $curso[0]['fechaFinCurso'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo !$esAdmin ? 'readonly' : ''; ?>>
                                    </div>
                                    <div class="form-group col-md-3 col-sm-12">
                                        <label for="inputEstimatedDuration">Horario:</label>
                                        <input type="time" id="inputEstimatedDuration" class="form-control form-control-sm" name="horarioCurso" value="<?php echo htmlspecialchars((string) $curso[0]['horarioCurso'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo !$esAdmin ? 'readonly' : ''; ?>>
                                    </div>
                                    <div class="form-group col-1 mt-4">
                                        <?php if ($esAdmin): ?>
                                            <?php $editar = ControladorCursos::crtModificarCurso(); ?>
                                            <button type="submit" class="btn btn-success btn-sm mt-2"><i class="fas fa-edit"></i></button>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-12"></div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="card ">
                        <div class="card-header">
                            <h3 class="card-title">Secciones del curso</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="example2" class="table table-bordered table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th style="text-align: center;">Sección</th>
                                        <th style="text-align: center;">Descripción</th>
                                        <th style="text-align: center;">Docente</th>
                                        <th style="text-align: center;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($secciones as $valor): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($valor['tituloSeccion'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars((string) $valor['contenidoSeccion'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($valor['nombreUsuario'] . ' ' . $valor['apellidoUsuario'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <div class="row d-flex justify-content-around">
                                                    <a href="index.php?r=detalle-seccion&idSeccion=<?php echo (int) $valor['idSeccion']; ?>" class="btn btn-info btn-sm" title="Abrir aula"><i class="fas fa-eye"></i></a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php if (!$esAdmin): ?>
                                <div class="alert alert-warning mt-3 mb-0">
                                    Desde el aula de cada sección podés ver el contenido y los recursos. La edición seguirá concentrada en administración.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
</div>
</section>

<div class="row">
    <div class="card-body col-lg-12 col-md-12">
        <section class="content">
            <div class="row">
                <?php if ($esAdmin): ?>
                    <div class="col-12 col-lg-6">
                        <div class="card ">
                            <div class="card-header">
                                <h3 class="card-title">Inscribir estudiantes</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <form action="" method="POST">
                                    <table id="example1" class="table table-bordered table-striped table-sm">
                                        <thead>
                                            <tr>
                                                <th style="text-align: center;">Apellido y Nombre</th>
                                                <th style="text-align: center;">Email</th>
                                                <th style="text-align: center;"><i class="fas fa-user-plus"></i></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($estudiantes as $valor): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($valor['apellidoUsuario'] . ' ' . $valor['nombreUsuario'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars($valor['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><input type="checkbox" name="idUsuarios[]" value="<?php echo (int) $valor['idUsuario']; ?>"></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    <?php $registro = ControladorCursos::crtAsignarCurso(); ?>
                                    <input type="submit" value="AGREGAR" class="btn btn-primary">
                                    <?php
                                    if (isset($_SESSION['success_message'])) {
                                        echo "<script>setTimeout(function(){ window.location.href = 'index.php?r=detalle-curso&idCurso=" . $idCurso . "'; }, 5200);</script>";
                                    }
                                    ?>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="col-12 <?php echo $esAdmin ? 'col-lg-6' : 'col-lg-12'; ?>">
                    <div class="card ">
                        <div class="card-header">
                            <h3 class="card-title">Estudiantes inscriptos al curso</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="example3" class="table table-bordered table-striped table-sm">
                                <thead>
                                    <tr>
                                        <th style="text-align: center;">Apellido y Nombre</th>
                                        <th style="text-align: center;">DNI</th>
                                        <th style="text-align: center;">Email</th>
                                        <th style="text-align: center;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cursantes as $valor): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($valor['apellidoUsuario'] . ' ' . $valor['nombreUsuario'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars((string) $valor['idUsuario'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($valor['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="text-center">
                                                <a href="index.php?r=nuevo-mensaje&id_destinatario=<?php echo (int) $valor['idUsuario']; ?>" class="btn btn-primary btn-sm" title="Enviar mensaje">
                                                    <i class="fas fa-paper-plane"></i>
                                                </a>
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
    </div>
</div>
