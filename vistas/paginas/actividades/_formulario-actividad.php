<?php
include __DIR__ . '/_formulario-actividad-config.php';
?>

<style>
  .page-fade { cursor: default; }
  .page-fade button,
  .page-fade [type="button"],
  .page-fade [type="submit"],
  .page-fade select,
  .page-fade a.btn,
  .page-fade .btn { cursor: pointer; }
  .page-fade input,
  .page-fade textarea { cursor: text; }
  .code-editor-shell { border: 1px solid #dbe3ef; border-radius: 8px; overflow: hidden; background: linear-gradient(180deg, #111827 0%, #0f172a 100%); box-shadow: 0 16px 34px rgba(15, 23, 42, .12), inset 0 1px 0 rgba(255,255,255,.04); }
  .code-editor-shell__header { display: flex; justify-content: space-between; align-items: center; gap: .75rem; padding: .65rem .85rem; background: #111827; color: #cbd5e1; border-bottom: 1px solid rgba(148, 163, 184, .18); cursor: default; }
  .code-editor-shell__header small { color: #94a3b8; margin: 0; }
  .code-editor-textarea { border: 0; border-radius: 0; min-height: 220px; resize: vertical; background: transparent; color: #e2e8f0; font-family: Consolas, Monaco, monospace; line-height: 1.55; tab-size: 2; cursor: text !important; }
  .code-editor-textarea:focus { background: #0f172a; color: #f8fafc; box-shadow: none; }
</style>

<section class="content page-fade">
  <div class="container-fluid">
    <div class="entity-hero mb-4">
      <div class="entity-hero__content">
        <span class="entity-kicker mb-3">Actividades</span>
        <h1 class="entity-title mb-2"><?php echo $esEdicion ? 'Editar actividad' : 'Crear actividad'; ?></h1>
        <p class="entity-lead mb-0">Configura la consigna, la visibilidad y las preguntas desde un solo formulario.</p>
      </div>
    </div>

    <div class="card glass-card">
      <div class="card-body">
        <form method="post" data-actividad-form>
          <input type="hidden" name="accion_actividad" value="guardar_actividad">
          <input type="hidden" name="idActividad" value="<?php echo (int) ($actividad['idActividad'] ?? 0); ?>">
          <input type="hidden" name="preguntasPayload" id="preguntasPayload" value="">

          <div class="row">
            <div class="col-lg-8">
              <div class="form-group">
                <label>Titulo</label>
                <input type="text" name="tituloActividad" class="form-control" required value="<?php echo $e($actividad['tituloActividad'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-lg-4">
              <div class="form-group">
                <label>Tipo de actividad</label>
                <select name="tipoActividad" id="tipoActividad" class="form-control">
                  <?php foreach ($tipos as $clave => $label): ?>
                    <option value="<?php echo $e($clave); ?>" <?php echo $tipoActual === $clave ? 'selected' : ''; ?>><?php echo $e($label); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label>Descripcion</label>
            <textarea name="descripcionActividad" class="form-control" rows="3"><?php echo $e($actividad['descripcionActividad'] ?? ''); ?></textarea>
          </div>

          <div class="row">
            <div class="col-lg-4">
              <div class="form-group">
                <label>Visibilidad</label>
                <select name="visibilidad" class="form-control">
                  <?php foreach ($visibilidades as $clave => $label): ?>
                    <option value="<?php echo $e($clave); ?>" <?php echo $visibilidadActual === $clave ? 'selected' : ''; ?>><?php echo $e($label); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="form-group">
                <label>Estado</label>
                <select name="estadoActividad" class="form-control">
                  <?php foreach ($estados as $clave => $label): ?>
                    <option value="<?php echo $e($clave); ?>" <?php echo $estadoActual === $clave ? 'selected' : ''; ?>><?php echo $e($label); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="form-group">
                <label>Intentos permitidos</label>
                <input type="number" name="intentosPermitidos" class="form-control" min="0" value="<?php echo (int) ($actividad['intentosPermitidos'] ?? 1); ?>">
                <small class="form-text text-muted">Usa 0 para intentos sin limite.</small>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-lg-8">
              <div class="form-group">
                <label>Materia</label>
                <select name="id_seccion" class="form-control">
                  <option value="0">Sin materia asignada</option>
                  <?php foreach ($materias as $materia): ?>
                    <option value="<?php echo (int) ($materia['idSeccion'] ?? 0); ?>" <?php echo (int) ($actividad['id_seccion'] ?? 0) === (int) ($materia['idSeccion'] ?? 0) ? 'selected' : ''; ?>>
                      <?php echo $e(($materia['nombreCurso'] ?? 'Sin curso') . ' - ' . ($materia['tituloSeccion'] ?? 'Sin materia')); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-lg-4 d-flex align-items-center">
              <div class="form-check mt-3">
                <input type="checkbox" class="form-check-input" id="permiteVisitantes" name="permiteVisitantes" <?php echo (int) ($actividad['permiteVisitantes'] ?? 1) === 1 ? 'checked' : ''; ?>>
                <label class="form-check-label" for="permiteVisitantes">Permitir visitantes en actividades publicas</label>
              </div>
            </div>
          </div>

          <div class="actividad-externa">
            <div class="row">
              <div class="col-lg-6">
                <div class="form-group">
                  <label>URL del recurso externo</label>
                  <input type="url" name="recursoExternoUrl" class="form-control" value="<?php echo $e($actividad['recursoExternoUrl'] ?? ''); ?>">
                </div>
              </div>
              <div class="col-lg-6">
                <div class="form-group">
                  <label>Iframe o embed</label>
                  <textarea name="recursoExternoEmbed" class="form-control" rows="3"><?php echo $e($actividad['recursoExternoEmbed'] ?? ''); ?></textarea>
                </div>
              </div>
            </div>
          </div>

          <div class="actividad-preguntas">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h3 class="mb-0">Preguntas</h3>
            </div>

            <div id="preguntasContainer">
              <?php foreach ($preguntas as $indice => $pregunta): ?>
                <?php include __DIR__ . '/_pregunta-card.php'; ?>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="d-flex flex-wrap justify-content-between align-items-center mt-4 pt-2">
            <a href="index.php?r=listado-actividades" class="btn btn-outline-secondary mb-2">Volver</a>
            <div class="d-flex flex-wrap align-items-center">
              <button type="button" id="agregarPregunta" class="btn btn-outline-primary mr-2 mb-2">
                <i class="fas fa-plus"></i> Agregar pregunta
              </button>
              <button type="submit" class="btn btn-primary mb-2">Guardar actividad</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/_pregunta-template.php'; ?>
<?php include __DIR__ . '/_formulario-actividad-script.php'; ?>
