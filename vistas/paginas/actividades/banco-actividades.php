<?php
ControladorActividades::crtProcesarAcciones();

$actividades = ControladorActividades::crtListarBancoActividades();
$puedeCrear = ControladorPermisos::esAdministrador() || ControladorPermisos::esDocente();
$idUsuarioActual = (int) ($_SESSION['usuario']['id'] ?? 0);
$busquedaActual = trim((string) ($_GET['q'] ?? ''));
$tipoActual = trim((string) ($_GET['tipo'] ?? ''));
$alcanceActual = trim((string) ($_GET['alcance'] ?? ''));
$estadoActual = trim((string) ($_GET['estado'] ?? ''));
$e = static function ($valor) {
  return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
};
$tipoLabels = ControladorActividades::tiposDisponibles();
$visibilidadLabels = ControladorActividades::visibilidadesDisponibles();
$alcanceLabels = ControladorActividades::alcancesPlantillaDisponibles();
$esBanco = true;
?>

<style>
  .activity-card-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem; }
  @media (max-width: 1399.98px) { .activity-card-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
  @media (max-width: 991.98px) { .activity-card-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
  @media (max-width: 575.98px) { .activity-card-grid { grid-template-columns: 1fr; } }
  .activity-card { position: relative; z-index: 1; border: 1px solid rgba(15, 23, 42, .1); border-radius: 8px; background: #fff; min-height: 100%; box-shadow: 0 10px 24px rgba(15, 23, 42, .06); overflow: visible; }
  .activity-card.is-menu-open { z-index: 30; }
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
  .activity-actions .dropdown { position: relative; }
  .activity-actions .dropdown-menu { z-index: 1080; }
</style>

<section class="content page-fade">
  <div class="container-fluid">
    <div class="entity-hero mb-4">
      <div class="entity-hero__content">
        <span class="entity-kicker mb-3">Plantillas</span>
        <h1 class="entity-title mb-2">Banco de actividades</h1>
        <p class="entity-lead mb-0">Guarda actividades reutilizables, clasificalas y crea nuevas propuestas sin empezar de cero.</p>
      </div>
    </div>

    <div class="card glass-card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Plantillas disponibles</h3>
        <a href="index.php?r=listado-actividades" class="btn btn-outline-secondary btn-sm">Volver al listado</a>
      </div>
      <div class="card-body">
        <form method="get" class="mb-4">
          <input type="hidden" name="r" value="banco-actividades">
          <div class="row align-items-end">
            <div class="col-lg-3">
              <label for="q">Buscar plantilla</label>
              <input type="text" id="q" name="q" class="form-control" value="<?php echo $e($busquedaActual); ?>" placeholder="Titulo, descripcion, curso o materia">
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
              <label for="alcance">Alcance</label>
              <select id="alcance" name="alcance" class="form-control">
                <option value="">Todos</option>
                <?php foreach ($alcanceLabels as $clave => $label): ?>
                  <option value="<?php echo $e($clave); ?>" <?php echo $alcanceActual === $clave ? 'selected' : ''; ?>><?php echo $e($label); ?></option>
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
              <div class="d-flex flex-wrap mt-3 mt-lg-0">
                <button type="submit" class="btn btn-outline-primary mr-2 mb-2 mb-lg-0"><i class="fas fa-search"></i> Filtrar</button>
                <?php if ($busquedaActual !== '' || $tipoActual !== '' || $alcanceActual !== '' || $estadoActual !== ''): ?>
                  <a href="index.php?r=banco-actividades" class="btn btn-outline-secondary mb-2 mb-lg-0">Limpiar</a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </form>

        <?php if (empty($actividades)): ?>
          <div class="empty-state">
            <i class="fas fa-bookmark"></i>
            <h4>No hay plantillas disponibles</h4>
            <p class="mb-0">Desde el listado de actividades podes guardar una actividad como plantilla y va a aparecer aca.</p>
          </div>
        <?php else: ?>
          <div class="activity-card-grid">
            <?php foreach ($actividades as $index => $actividad): ?>
              <?php include __DIR__ . '/_actividad-card.php'; ?>
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
          <h5 class="modal-title" id="eliminarActividadTitulo">Eliminar plantilla</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
        </div>
        <form method="post">
          <div class="modal-body">
            <input type="hidden" name="accion_actividad" value="eliminar_actividad">
            <input type="hidden" name="idActividad" id="eliminarActividadId" value="0">
            <p class="mb-2">Vas a eliminar permanentemente <strong id="eliminarActividadNombre"></strong>.</p>
            <p class="text-danger mb-0">Esta accion no se puede deshacer.</p>
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

      if (window.jQuery) {
        window.jQuery('.activity-actions .dropdown').on('show.bs.dropdown', function () {
          var card = this.closest('.activity-card');
          if (card) {
            card.classList.add('is-menu-open');
          }
        });

        window.jQuery('.activity-actions .dropdown').on('hidden.bs.dropdown', function () {
          var card = this.closest('.activity-card');
          if (card) {
            card.classList.remove('is-menu-open');
          }
        });
      }
    });
  </script>
<?php endif; ?>
