<?php
$idUsuarioActual = (int) ($_SESSION['usuario']['id'] ?? 0);
$rolReal = ControladorPermisos::rolReal();
$esEstudiante = ControladorPermisos::esEstudiante();
$esDocente = ControladorPermisos::esDocente();
$esAdmin = ControladorPermisos::esAdministrador();
$esDocenteReal = $rolReal === 'DOCENTE';
$esAdminReal = $rolReal === 'ADMINISTRADOR';
$vistaEstudianteSimulada = ControladorPermisos::vistaEstudianteActiva() && in_array($rolReal, ['ADMINISTRADOR', 'DOCENTE'], true);
$idEstudianteContexto = ControladorPermisos::idEstudianteContexto();
$estudiantesSimulacion = [];
$estudianteSimulado = null;

if ($vistaEstudianteSimulada) {
    $cursosDisponiblesSimulacion = $esDocenteReal
        ? ControladorCursos::crtCursosPorDocente($idUsuarioActual)
        : ControladorCursos::crtListarCursos();

    if ($esAdminReal) {
        $estudiantesSimulacion = array_values(array_filter(
            (array) ControladorUsuarios::crtSeleccionarUsuario('rol', 'ESTUDIANTE'),
            static function ($usuario) {
                return (int) ($usuario['activo'] ?? 0) === 1;
            }
        ));
    } else {
        $estudiantesPorId = [];
        foreach ($cursosDisponiblesSimulacion as $cursoDocente) {
            foreach (ControladorLecciones::crtBuscarEstudiantesCurso((int) ($cursoDocente['idCurso'] ?? 0)) as $estudianteCurso) {
                $estudiantesPorId[(int) $estudianteCurso['idUsuario']] = $estudianteCurso;
            }
        }
        $estudiantesSimulacion = array_values($estudiantesPorId);
    }

    usort($estudiantesSimulacion, static function ($primero, $segundo) {
        $nombrePrimero = trim(($primero['apellidoUsuario'] ?? '') . ' ' . ($primero['nombreUsuario'] ?? ''));
        $nombreSegundo = trim(($segundo['apellidoUsuario'] ?? '') . ' ' . ($segundo['nombreUsuario'] ?? ''));
        return strcasecmp($nombrePrimero, $nombreSegundo);
    });

    foreach ($estudiantesSimulacion as $estudianteDisponible) {
        if ((int) ($estudianteDisponible['idUsuario'] ?? 0) === $idEstudianteContexto) {
            $estudianteSimulado = $estudianteDisponible;
            break;
        }
    }

    if ($estudianteSimulado) {
        $cursosEstudiante = ControladorCursos::crtCursosPorEstudiante($idEstudianteContexto);

        if ($esDocenteReal) {
            $idsCursosDocente = array_fill_keys(
                array_map('intval', array_column($cursosDisponiblesSimulacion, 'idCurso')),
                true
            );
            $cursosEstudiante = array_values(array_filter(
                $cursosEstudiante,
                static function ($curso) use ($idsCursosDocente) {
                    return isset($idsCursosDocente[(int) ($curso['idCurso'] ?? 0)]);
                }
            ));
        }

        $cursos = $cursosEstudiante;
    } else {
        $cursos = $cursosDisponiblesSimulacion;
    }
} elseif ($esEstudiante) {
    $cursos = ControladorCursos::crtCursosPorEstudiante($idUsuarioActual);
} elseif ($esDocente) {
    $cursos = ControladorCursos::crtCursosPorDocente($idUsuarioActual);
} else {
    $cursos = ControladorCursos::crtListarCursos();
}
$e = static function ($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
};
?>

<section class="content page-fade">
  <div class="container-fluid" data-management-list>
    <?php if ($vistaEstudianteSimulada): ?>
      <div class="entity-hero mb-4">
        <div class="entity-hero__content">
          <span class="entity-kicker mb-3">Vista estudiante</span>
          <h1 class="entity-title mb-2">Explorá el campus como estudiante</h1>
          <p class="entity-lead mb-0">Elegí un estudiante para comprobar exactamente qué cursos, entregas y calificaciones puede ver.</p>
        </div>
      </div>

      <div class="student-preview-selector mb-4">
        <div class="student-preview-selector__copy">
          <span class="student-preview-selector__icon"><i class="fas fa-user-graduate"></i></span>
          <div>
            <strong><?php echo $estudianteSimulado
              ? 'Previsualizando como ' . $e(trim(($estudianteSimulado['nombreUsuario'] ?? '') . ' ' . ($estudianteSimulado['apellidoUsuario'] ?? '')))
              : 'Vista general del campus'; ?></strong>
            <small><?php echo $estudianteSimulado
              ? 'Los datos académicos corresponden al estudiante seleccionado.'
              : 'Seleccioná un estudiante para visualizar información personal como entregas y notas.'; ?></small>
          </div>
        </div>
        <form method="get" action="index.php" class="student-preview-selector__form">
          <input type="hidden" name="r" value="vista-estudiante">
          <input type="hidden" name="estado" value="1">
          <input type="hidden" name="redir" value="index.php?r=listado-cursos">
          <label class="sr-only" for="estudianteVistaSimulada">Estudiante</label>
          <select name="idEstudiante" id="estudianteVistaSimulada" class="form-control">
            <option value="0">Vista general, sin datos personales</option>
            <?php foreach ($estudiantesSimulacion as $estudianteDisponible): ?>
              <?php $idEstudianteDisponible = (int) ($estudianteDisponible['idUsuario'] ?? 0); ?>
              <option value="<?php echo $idEstudianteDisponible; ?>" <?php echo $idEstudianteDisponible === $idEstudianteContexto ? 'selected' : ''; ?>>
                <?php echo $e(trim(($estudianteDisponible['apellidoUsuario'] ?? '') . ' ' . ($estudianteDisponible['nombreUsuario'] ?? ''))); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-eye mr-1"></i>Aplicar vista
          </button>
        </form>
      </div>

      <?php if (empty($cursos)): ?>
        <div class="empty-state">
          <i class="fas fa-layer-group"></i>
          <h4><?php echo $estudianteSimulado ? 'Este estudiante no tiene cursos asignados' : 'Todavía no hay cursos para previsualizar'; ?></h4>
          <p class="mb-0"><?php echo $estudianteSimulado
            ? 'Podés seleccionar otro estudiante o revisar sus asignaciones.'
            : 'Cuando existan cursos cargados, vas a poder abrirlos desde esta vista.'; ?></p>
        </div>
      <?php else: ?>
        <div class="student-class-grid">
          <?php foreach ($cursos as $index => $curso): ?>
            <a class="student-class-card theme-<?php echo (int) ($index % 4); ?>" href="index.php?r=detalle-curso&idCurso=<?php echo (int) $curso['idCurso']; ?>">
              <div class="student-class-card__cover">
                <div>
                  <h2><?php echo $e($curso['nombreCurso'] ?? 'Curso'); ?></h2>
                  <p><?php echo $e($curso['estado'] ?? 'Activo'); ?> · <?php echo $e($curso['fInicio'] ?? ''); ?></p>
                </div>
              </div>
              <div class="student-class-card__body">
                <p><?php echo $e($curso['contenidoCurso'] ?? 'Sin descripción cargada.'); ?></p>
              </div>
              <div class="student-class-card__footer">
                <span><i class="fas fa-book-open"></i> <?php echo (int) ($curso['totalSecciones'] ?? 0); ?> materias</span>
                <span><i class="fas fa-tasks"></i> <?php echo (int) ($curso['totalLecciones'] ?? 0); ?> clases</span>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php elseif ($esEstudiante): ?>
      <div class="entity-hero mb-4">
        <div class="entity-hero__content">
          <span class="entity-kicker mb-3">Mis cursos</span>
          <h1 class="entity-title mb-2">Tus aulas activas</h1>
          <p class="entity-lead mb-0">Entrá al curso y desde ahí elegí la materia. Así mantenemos el tablero limpio aunque un curso tenga muchas materias.</p>
        </div>
      </div>

      <?php if (empty($cursos)): ?>
        <div class="empty-state">
          <i class="fas fa-layer-group"></i>
          <h4>Todavía no tenés cursos asignados</h4>
          <p class="mb-0">Cuando te inscriban a un curso, va a aparecer en este tablero.</p>
        </div>
      <?php else: ?>
        <div class="student-class-grid">
          <?php foreach ($cursos as $index => $curso): ?>
            <a class="student-class-card theme-<?php echo (int) ($index % 4); ?>" href="index.php?r=detalle-curso&idCurso=<?php echo (int) $curso['idCurso']; ?>">
              <div class="student-class-card__cover">
                <div>
                  <h2><?php echo $e($curso['nombreCurso'] ?? 'Curso'); ?></h2>
                  <p><?php echo $e($curso['estado'] ?? 'Activo'); ?> · <?php echo $e($curso['fInicio'] ?? ''); ?></p>
                </div>
              </div>
              <div class="student-class-card__body">
                <p><?php echo $e($curso['contenidoCurso'] ?? 'Sin descripción cargada.'); ?></p>
              </div>
              <div class="student-class-card__footer">
                <span><i class="fas fa-book-open"></i> <?php echo (int) ($curso['totalSecciones'] ?? 0); ?> materias</span>
                <span><i class="fas fa-tasks"></i> <?php echo (int) ($curso['totalLecciones'] ?? 0); ?> clases</span>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php else: ?>
      <div class="entity-hero mb-4">
        <div class="entity-hero__content">
          <span class="entity-kicker mb-3"><?php echo $esDocente ? 'Mis cursos' : 'Cursos'; ?></span>
          <h1 class="entity-title mb-2"><?php echo $esDocente ? 'Tus aulas asignadas' : 'Gestión de cursos'; ?></h1>
          <p class="entity-lead mb-0">
            <?php echo $esDocente
              ? 'Revisá los cursos donde participás como docente o tutor y entrá directamente a sus aulas.'
              : 'Explorá los cursos de forma visual y accedé a la información administrativa cuando la necesites.'; ?>
          </p>
        </div>
      </div>

      <div class="management-list-header mb-3">
        <div>
          <div class="section-title"><?php echo $esDocente ? 'Cursos a tu cargo' : 'Cursos registrados'; ?></div>
          <div class="section-subtitle"><span data-management-count><?php echo count($cursos); ?></span> cursos disponibles</div>
        </div>
        <div class="management-list-actions">
          <label class="management-search mb-0">
            <span class="sr-only">Buscar cursos</span>
            <i class="fas fa-search"></i>
            <input type="search" class="form-control form-control-sm" placeholder="Buscar curso..." data-management-search>
          </label>
          <?php if ($esAdmin): ?>
            <a href="index.php?r=crear-curso" class="btn btn-primary btn-sm">
              <i class="fas fa-plus mr-1"></i>Nuevo curso
            </a>
          <?php endif; ?>
        </div>
      </div>

      <?php if (empty($cursos)): ?>
        <div class="empty-state">
          <i class="fas fa-layer-group"></i>
          <h4><?php echo $esDocente ? 'Todavía no tenés cursos asignados' : 'Todavía no hay cursos registrados'; ?></h4>
          <p class="mb-0"><?php echo $esDocente ? 'Cuando te asignen una sección, su curso aparecerá acá.' : 'Creá el primer curso para comenzar a organizar las aulas.'; ?></p>
        </div>
      <?php else: ?>
        <div class="management-card-grid" data-management-grid>
          <?php foreach ($cursos as $index => $curso): ?>
            <?php
            $estadoCurso = trim((string) ($curso['estado'] ?? 'Sin estado'));
            $inicioCurso = trim((string) ($curso['fInicio'] ?? '')) ?: 'Sin fecha';
            $finCurso = trim((string) ($curso['fFin'] ?? '')) ?: 'Sin fecha';
            $horarioCurso = trim((string) ($curso['horarioCurso'] ?? '')) ?: 'Sin horario';
            $textoBusqueda = implode(' ', [
              $curso['nombreCurso'] ?? '',
              $curso['contenidoCurso'] ?? '',
              $estadoCurso,
              $inicioCurso,
              $finCurso,
              $horarioCurso,
            ]);
            ?>
            <article class="management-entity-card theme-<?php echo (int) ($index % 4); ?>"
              data-management-card
              data-search="<?php echo $e($textoBusqueda); ?>">
              <div class="management-entity-card__top">
                <div class="management-entity-card__heading">
                  <span class="management-entity-card__eyebrow">Curso</span>
                  <h2><?php echo $e($curso['nombreCurso'] ?? 'Curso'); ?></h2>
                  <p><span class="management-status-dot"></span><?php echo $e($estadoCurso); ?> · <?php echo $e($inicioCurso); ?></p>
                </div>
              </div>

              <div class="management-entity-card__body">
                <p class="management-entity-card__description"><?php echo $e($curso['contenidoCurso'] ?? 'Sin descripción cargada.'); ?></p>
                <div class="management-detail-grid">
                  <div>
                    <span><i class="far fa-clock"></i> Horario</span>
                    <strong><?php echo $e($horarioCurso); ?></strong>
                  </div>
                  <div>
                    <span><i class="far fa-calendar-check"></i> Finaliza</span>
                    <strong><?php echo $e($finCurso); ?></strong>
                  </div>
                </div>
              </div>

              <div class="management-entity-card__footer">
                <div class="management-card-metrics">
                  <span><i class="fas fa-book-open"></i><?php echo (int) ($curso['totalSecciones'] ?? 0); ?> materias</span>
                  <span><i class="fas fa-tasks"></i><?php echo (int) ($curso['totalLecciones'] ?? 0); ?> clases</span>
                </div>
                <div class="management-card-actions">
                  <a href="index.php?r=detalle-curso&idCurso=<?php echo (int) $curso['idCurso']; ?>" class="btn btn-info btn-sm">
                    <i class="far fa-eye mr-1"></i>Abrir
                  </a>
                  <?php if ($esAdmin): ?>
                    <div class="dropdown">
                      <button type="button" class="btn btn-outline-secondary btn-sm management-menu-button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Más opciones">
                        <i class="fas fa-ellipsis-v"></i>
                      </button>
                      <div class="dropdown-menu dropdown-menu-right shadow-sm">
                        <a class="dropdown-item" href="index.php?r=editar-curso&idCurso=<?php echo (int) $curso['idCurso']; ?>">
                          <i class="fas fa-edit mr-2"></i>Editar curso
                        </a>
                        <a class="dropdown-item" href="index.php?r=detalle-curso&idCurso=<?php echo (int) $curso['idCurso']; ?>">
                          <i class="fas fa-users-cog mr-2"></i>Secciones y estudiantes
                        </a>
                      </div>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>

        <div class="empty-state d-none" data-management-empty>
          <i class="fas fa-search"></i>
          <h4>No encontramos cursos</h4>
          <p class="mb-0">Probá con otro nombre, estado o fecha.</p>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>
