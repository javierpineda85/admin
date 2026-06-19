<?php
ControladorActividades::crtProcesarAcciones();

$actividades = ControladorActividades::crtListarActividades();
$puedeCrear = ControladorPermisos::esAdministrador() || ControladorPermisos::esDocente();
$idUsuarioActual = (int) ($_SESSION['usuario']['id'] ?? 0);
$busquedaActual = trim((string) ($_GET['q'] ?? ''));
$e = static function ($valor) {
  return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
};
$tipoLabels = ControladorActividades::tiposDisponibles();
$visibilidadLabels = ControladorActividades::visibilidadesDisponibles();
?>

<style>
  .activity-card-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1rem;
  }

  .activity-card {
    border: 1px solid rgba(15, 23, 42, .1);
    border-radius: 8px;
    background: #fff;
    min-height: 100%;
    box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
    overflow: hidden;
  }

  .activity-card__top {
    min-height: 92px;
    padding: 1rem;
    background: linear-gradient(135deg, #5b21b6, #2563eb);
    color: #fff;
  }

  .activity-card__top.theme-1 { background: linear-gradient(135deg, #047857, #0f766e); }
  .activity-card__top.theme-2 { background: linear-gradient(135deg, #be123c, #c2410c); }
  .activity-card__top.theme-3 { background: linear-gradient(135deg, #1f2937, #4f46e5); }

  .activity-card__top h3 {
    font-size: 1.08rem;
    line-height: 1.25;
    margin: 0 0 .35rem;
    color: #fff;
  }

  .activity-card__top small {
    color: rgba(255, 255, 255, .82);
  }

  .activity-card__body {
    padding: 1rem;
  }

  .activity-card__meta {
    display: flex;
    flex-wrap: wrap;
    gap: .4rem;
    margin-bottom: .85rem;
  }

  .activity-chip {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    border: 1px solid #dbe3ef;
    border-radius: 999px;
    padding: .25rem .55rem;
    font-size: .78rem;
    color: #475569;
    background: #f8fafc;
  }

  .activity-chip--tipo {
    border-color: rgba(124, 58, 237, .18);
    background: rgba(255, 255, 255, .96);
    color: #4c1d95;
    font-weight: 600;
    box-shadow: 0 4px 10px rgba(15, 23, 42, .06);
  }

  .activity-card__footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: .75rem;
    padding: .85rem 1rem;
    border-top: 1px solid #e5e7eb;
    background: #f8fafc;
  }

  .activity-actions {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    flex-wrap: wrap;
    justify-content: flex-end;
  }
</style>

<section class="content page-fade">
  <div class="container-fluid">
    <div class="entity-hero mb-4">
      <div class="entity-hero__content">
        <span class="entity-kicker mb-3">Actividades</span>
        <h1 class="entity-title mb-2"><?php echo $puedeCrear ? 'Gestion de actividades' : 'Mis actividades'; ?></h1>
        <p class="entity-lead mb-0">Administra actividades privadas del curso o publicas para compartir desde la web.</p>
      </div>
    </div>

    <div class="card glass-card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Actividades registradas</h3>
        <?php if ($puedeCrear): ?>
          <a href="index.php?r=crear-actividad" class="btn btn-primary btn-sm">Nueva actividad</a>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <form method="get" class="mb-4">
          <input type="hidden" name="r" value="listado-actividades">
          <div class="row align-items-end">
            <div class="col-lg-8">
              <label for="q">Buscar actividad</label>
              <input type="text" id="q" name="q" class="form-control" value="<?php echo $e($busquedaActual); ?>" placeholder="Titulo, descripcion, curso, materia o slug">
            </div>
            <div class="col-lg-4">
              <div class="d-flex flex-wrap mt-3 mt-lg-0">
                <button type="submit" class="btn btn-outline-primary mr-2 mb-2 mb-lg-0">
                  <i class="fas fa-search"></i> Buscar
                </button>
                <?php if ($busquedaActual !== ''): ?>
                  <a href="index.php?r=listado-actividades" class="btn btn-outline-secondary mb-2 mb-lg-0">Limpiar</a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </form>

        <?php if (empty($actividades)): ?>
          <div class="empty-state">
            <i class="fas fa-tasks"></i>
            <h4><?php echo $busquedaActual !== '' ? 'No hay resultados para la busqueda' : 'No hay actividades cargadas'; ?></h4>
            <p class="mb-0">
              <?php
              if ($busquedaActual !== '') {
                echo 'Proba con otro termino o limpia el filtro para volver al listado completo.';
              } else {
                echo $puedeCrear ? 'Crea la primera actividad para una materia o para acceso publico.' : 'Cuando haya actividades disponibles van a aparecer aca.';
              }
              ?>
            </p>
          </div>
        <?php else: ?>
          <div class="activity-card-grid">
            <?php foreach ($actividades as $index => $actividad): ?>
              <?php
                $publicUrl = 'index.php?r=actividad-publica&slug=' . rawurlencode((string) $actividad['slug']);
                $intentosPermitidos = (int) ($actividad['intentosPermitidos'] ?? 1);
                $intentosUsados = $idUsuarioActual > 0
                  ? ControladorActividades::crtIntentosUsadosUsuario((int) $actividad['idActividad'], $idUsuarioActual)
                  : 0;
                $intentosAgotados = ControladorActividades::intentosAgotados($actividad, $idUsuarioActual);
              ?>
              <article class="activity-card">
                <div class="activity-card__top theme-<?php echo (int) ($index % 4); ?>">
                  <h3><?php echo $e($actividad['tituloActividad']); ?></h3>
                  <small><?php echo $e($actividad['nombreCurso'] ?? 'Sin curso'); ?> · <?php echo $e($actividad['tituloSeccion'] ?? 'Sin materia'); ?></small>
                </div>
                <div class="activity-card__body">
                  <div class="activity-card__meta">
                    <span class="activity-chip activity-chip--tipo"><i class="fas fa-tasks"></i><?php echo $e($tipoLabels[$actividad['tipoActividad']] ?? $actividad['tipoActividad']); ?></span>
                    <span class="activity-chip"><i class="fas fa-eye"></i><?php echo $e($visibilidadLabels[$actividad['visibilidad']] ?? $actividad['visibilidad']); ?></span>
                    <span class="activity-chip"><i class="fas fa-star"></i><?php echo (float) ($actividad['puntajeMaximo'] ?? 0); ?> pts</span>
                  </div>

                  <p class="text-muted mb-2"><?php echo $e($actividad['descripcionActividad'] ?? 'Sin descripcion cargada.'); ?></p>

                  <div class="d-flex justify-content-between align-items-center">
                    <span class="badge <?php echo ($actividad['estadoActividad'] ?? '') === 'PUBLICADA' ? 'badge-success' : 'badge-secondary'; ?>">
                      <?php echo $e($actividad['estadoActividad']); ?>
                    </span>
                    <?php if ($puedeCrear): ?>
                      <small class="text-muted">Total intentos: <?php echo (int) ($actividad['totalIntentos'] ?? 0); ?></small>
                    <?php else: ?>
                      <small class="<?php echo $intentosAgotados ? 'text-danger' : 'text-muted'; ?>">
                        Intentos: <?php echo (int) $intentosUsados; ?> / <?php echo $intentosPermitidos > 0 ? (int) $intentosPermitidos : 'sin limite'; ?>
                      </small>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="activity-card__footer">
                  <small class="text-muted">
                    <?php echo $intentosPermitidos > 0 ? 'Limite: ' . (int) $intentosPermitidos . ' por estudiante' : 'Intentos sin limite'; ?>
                  </small>
                  <div class="activity-actions">
                    <a href="index.php?r=ver-actividad&idActividad=<?php echo (int) $actividad['idActividad']; ?>" class="btn btn-info btn-sm" title="Resolver/ver">
                      <i class="far fa-eye"></i>
                    </a>
                    <?php if ($puedeCrear): ?>
                      <a href="index.php?r=editar-actividad&idActividad=<?php echo (int) $actividad['idActividad']; ?>" class="btn btn-success btn-sm" title="Editar">
                        <i class="fas fa-edit"></i>
                      </a>
                      <a href="index.php?r=resultados-actividad&idActividad=<?php echo (int) $actividad['idActividad']; ?>" class="btn btn-secondary btn-sm" title="Resultados">
                        <i class="fas fa-chart-bar"></i>
                      </a>
                      <button type="button" class="btn btn-danger btn-sm eliminar-actividad" title="Eliminar" data-toggle="modal" data-target="#eliminarActividadModal" data-id="<?php echo (int) $actividad['idActividad']; ?>" data-titulo="<?php echo $e($actividad['tituloActividad']); ?>">
                        <i class="fas fa-trash"></i>
                      </button>
                    <?php endif; ?>
                    <?php if (in_array($actividad['visibilidad'], ['publica', 'oculta'], true)): ?>
                      <a href="<?php echo $e($publicUrl); ?>" target="_blank" class="btn btn-warning btn-sm" title="Abrir publica">
                        <i class="fas fa-external-link-alt"></i>
                      </a>
                    <?php endif; ?>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php if ($puedeCrear): ?>
  <div class="modal fade" id="eliminarActividadModal" tabindex="-1" role="dialog" aria-labelledby="eliminarActividadTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="eliminarActividadTitulo">Eliminar actividad</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form method="post">
          <div class="modal-body">
            <input type="hidden" name="accion_actividad" value="eliminar_actividad">
            <input type="hidden" name="idActividad" id="eliminarActividadId" value="0">
            <p class="mb-2">Vas a eliminar permanentemente <strong id="eliminarActividadNombre"></strong>.</p>
            <p class="text-danger mb-0">Tambien se eliminaran sus preguntas, intentos y respuestas. Esta accion no se puede deshacer.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-danger">Eliminar permanentemente</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var campoId = document.getElementById('eliminarActividadId');
      var campoNombre = document.getElementById('eliminarActividadNombre');

      document.querySelectorAll('.eliminar-actividad').forEach(function (boton) {
        boton.addEventListener('click', function () {
          campoId.value = boton.getAttribute('data-id') || '0';
          campoNombre.textContent = boton.getAttribute('data-titulo') || 'esta actividad';
        });
      });
    });
  </script>
<?php endif; ?>
