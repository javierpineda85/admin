<?php
ControladorActividades::crtProcesarAcciones();

$actividades = ControladorActividades::crtListarActividades();
$puedeCrear = ControladorPermisos::esAdministrador() || ControladorPermisos::esDocente();
$idUsuarioActual = (int) ($_SESSION['usuario']['id'] ?? 0);
$busquedaActual = trim((string) ($_GET['q'] ?? ''));
$tipoActual = trim((string) ($_GET['tipo'] ?? ''));
$visibilidadActual = trim((string) ($_GET['visibilidad'] ?? ''));
$estadoActual = trim((string) ($_GET['estado'] ?? ''));
$destacadasActual = isset($_GET['destacadas']) && $_GET['destacadas'] !== '';
$e = static function ($valor) {
  return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
};
$tipoLabels = ControladorActividades::tiposDisponibles();
$visibilidadLabels = ControladorActividades::visibilidadesDisponibles();
?>

<style>
  .activity-card-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem; }
  @media (max-width: 1399.98px) { .activity-card-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
  @media (max-width: 991.98px) { .activity-card-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
  @media (max-width: 575.98px) { .activity-card-grid { grid-template-columns: 1fr; } }
  .activity-card { border: 1px solid rgba(15, 23, 42, .1); border-radius: 8px; background: #fff; min-height: 100%; box-shadow: 0 10px 24px rgba(15, 23, 42, .06); overflow: hidden; }
  .activity-card__top { min-height: 92px; padding: 1rem; background: linear-gradient(135deg, #5b21b6, #2563eb); color: #fff; }
  .activity-card__top.theme-1 { background: linear-gradient(135deg, #047857, #0f766e); }
  .activity-card__top.theme-2 { background: linear-gradient(135deg, #be123c, #c2410c); }
  .activity-card__top.theme-3 { background: linear-gradient(135deg, #1f2937, #4f46e5); }
  .activity-card__top h3 { font-size: 1.08rem; line-height: 1.25; margin: 0 0 .35rem; color: #fff; }
  .activity-card__top small { color: rgba(255, 255, 255, .82); }
  .activity-card__body { padding: 1rem; }
  .activity-card__meta { display: flex; flex-wrap: wrap; gap: .4rem; margin-bottom: .85rem; }
  .activity-chip { display: inline-flex; align-items: center; gap: .35rem; border: 1px solid #dbe3ef; border-radius: 999px; padding: .25rem .55rem; font-size: .78rem; color: #475569; background: #f8fafc; }
  .activity-chip--tipo { border-color: rgba(124, 58, 237, .18); background: rgba(255, 255, 255, .96); color: #4c1d95; font-weight: 600; box-shadow: 0 4px 10px rgba(15, 23, 42, .06); }
  .activity-chip--destacada { border-color: rgba(245, 158, 11, .25); background: rgba(255, 251, 235, .98); color: #b45309; font-weight: 600; }
  .activity-card__footer { display: flex; justify-content: space-between; align-items: center; gap: .75rem; padding: .85rem 1rem; border-top: 1px solid #e5e7eb; background: #f8fafc; }
  .activity-actions { display: inline-flex; align-items: center; gap: .35rem; flex-wrap: wrap; justify-content: flex-end; }
  .activity-menu-button { min-width: 36px; }
  .dropdown-item-form { margin: 0; }
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
          <div class="d-flex align-items-center">
            <a href="index.php?r=banco-actividades" class="btn btn-outline-secondary btn-sm mr-2">Banco</a>
            <a href="index.php?r=crear-actividad" class="btn btn-primary btn-sm">Nueva actividad</a>
          </div>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <form method="get" class="mb-4">
          <input type="hidden" name="r" value="listado-actividades">
          <div class="row align-items-end">
            <div class="col-lg-3">
              <label for="q">Buscar actividad</label>
              <input type="text" id="q" name="q" class="form-control" value="<?php echo $e($busquedaActual); ?>" placeholder="Titulo, descripcion, curso, materia o slug">
            </div>
            <div class="col-lg-2">
              <label for="tipo">Tipo</label>
              <select id="tipo" name="tipo" class="form-control">
                <option value="">Todos</option>
                <?php foreach ($tipoLabels as $clave => $label): ?>
                  <option value="<?php echo $e($clave); ?>" <?php echo $tipoActual === $clave ? 'selected' : ''; ?>><?php echo $e($label); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-lg-2">
              <label for="visibilidad">Visibilidad</label>
              <select id="visibilidad" name="visibilidad" class="form-control">
                <option value="">Todas</option>
                <?php foreach ($visibilidadLabels as $clave => $label): ?>
                  <option value="<?php echo $e($clave); ?>" <?php echo $visibilidadActual === $clave ? 'selected' : ''; ?>><?php echo $e($label); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-lg-2">
              <label for="estado">Estado</label>
              <select id="estado" name="estado" class="form-control">
                <option value="">Todos</option>
                <option value="BORRADOR" <?php echo $estadoActual === 'BORRADOR' ? 'selected' : ''; ?>>Borrador</option>
                <option value="PUBLICADA" <?php echo $estadoActual === 'PUBLICADA' ? 'selected' : ''; ?>>Publicada</option>
              </select>
            </div>
            <div class="col-lg-3">
              <div class="d-flex flex-wrap align-items-center mt-3 mt-lg-0">
                <div class="form-check mr-3 mt-2">
                  <input class="form-check-input" type="checkbox" id="destacadas" name="destacadas" value="1" <?php echo $destacadasActual ? 'checked' : ''; ?>>
                  <label class="form-check-label" for="destacadas">Solo destacadas</label>
                </div>
                <button type="submit" class="btn btn-outline-primary mr-2 mb-2 mb-lg-0"><i class="fas fa-search"></i> Filtrar</button>
                <?php if ($busquedaActual !== '' || $tipoActual !== '' || $visibilidadActual !== '' || $estadoActual !== '' || $destacadasActual): ?>
                  <a href="index.php?r=listado-actividades" class="btn btn-outline-secondary mb-2 mb-lg-0">Limpiar</a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </form>

        <?php if (empty($actividades)): ?>
          <div class="empty-state">
            <i class="fas fa-tasks"></i>
            <h4><?php echo ($busquedaActual !== '' || $tipoActual !== '' || $visibilidadActual !== '' || $estadoActual !== '' || $destacadasActual) ? 'No hay resultados para el filtro actual' : 'No hay actividades cargadas'; ?></h4>
            <p class="mb-0"><?php echo $puedeCrear ? 'Crea una actividad nueva o reutiliza una plantilla desde el banco.' : 'Cuando haya actividades disponibles van a aparecer aca.'; ?></p>
          </div>
        <?php else: ?>
          <div class="activity-card-grid">
            <?php foreach ($actividades as $index => $actividad): ?>
              <?php $esBanco = false; include __DIR__ . '/_actividad-card.php'; ?>
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
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
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

      document.querySelectorAll('.activity-copy').forEach(function (boton) {
        boton.addEventListener('click', function () {
          var texto = boton.getAttribute('data-copy-text') || '';
          if (!texto || !navigator.clipboard) {
            return;
          }
          navigator.clipboard.writeText(texto);
        });
      });
    });
  </script>
<?php endif; ?>
