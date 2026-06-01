<?php
$idCurso = (int) ($_GET['idCurso'] ?? 0);
$rolActual = ControladorPermisos::rolActual();
$esAdmin = ControladorPermisos::esAdministrador();

$db = new Conexion;
$sql = "SELECT * FROM cursos WHERE idCurso = $idCurso";
$curso = $db->consultas($sql);

$db = new Conexion;
$sql = "SELECT idSeccion, tituloSeccion, contenidoSeccion, id_curso, docente, tutor, cursos.nombreCurso, usuarios.nombreUsuario, usuarios.apellidoUsuario
        FROM secciones
        JOIN cursos ON secciones.id_curso = cursos.idCurso
        JOIN usuarios ON secciones.docente = usuarios.idUsuario
        WHERE secciones.id_curso = $idCurso
        ORDER BY tituloSeccion ASC";
$secciones = $db->consultas($sql);

$db = new Conexion;
$sql = "SELECT * FROM usuarios WHERE rol = 'ESTUDIANTE' ORDER BY apellidoUsuario ASC, nombreUsuario ASC";
$estudiantes = $db->consultas($sql);

$db = new Conexion;
$sql = "SELECT * FROM asignacioncursos
        RIGHT JOIN usuarios ON asignacioncursos.id_estudiante = usuarios.idUsuario
        WHERE usuarios.rol = 'ESTUDIANTE'
          AND asignacioncursos.id_seccion = $idCurso";
$cursantes = $db->consultas($sql);

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
?>

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
                                    <div class="col-12">
                                        <?php
                                        if (isset($_SESSION['success_message'])) {
                                            echo '<div class="alert alert-success alert-dismissible">
                                                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                                                    <h4><i class="icon fas fa-check"></i></h4>' . $_SESSION['success_message'] .
                                                '</div>';
                                            unset($_SESSION['success_message']);
                                        }
                                        ?>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="card card-info">
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

<div class="row">
    <div class="card-body col-lg-12 col-md-12">
        <section class="content">
            <div class="row">
                <div class="col-12 col-lg-6">
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">Inscribir estudiantes</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if ($esAdmin): ?>
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
                                        echo "<script>window.location.href = 'index.php?r=detalle-curso&idCurso=" . $idCurso . "';</script>";
                                        unset($_SESSION['success_message']);
                                    }
                                    ?>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-warning mb-0">
                                    Solo el administrador puede inscribir estudiantes desde este panel.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="card card-info">
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
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cursantes as $valor): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($valor['apellidoUsuario'] . ' ' . $valor['nombreUsuario'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars((string) $valor['idUsuario'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($valor['email'], ENT_QUOTES, 'UTF-8'); ?></td>
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
