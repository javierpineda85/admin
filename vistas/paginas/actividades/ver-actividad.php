<?php
ControladorActividades::crtProcesarAcciones();

$idActividad = (int) ($_GET['idActividad'] ?? 0);
$actividad = ControladorActividades::crtBuscarActividadPorId($idActividad);
$e = static function ($valor) {
  return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
};
$lenguajesCodigo = ControladorActividades::lenguajesCodigoDisponibles();

if (!$actividad || !ControladorActividades::puedeResolverActividad($actividad)) {
?>
  <section class="content page-fade">
    <div class="container-fluid">
      <div class="empty-state">
        <i class="fas fa-lock"></i>
        <h4>No tenes acceso a esta actividad</h4>
        <p class="mb-3">Puede ser privada del curso o estar todavia en borrador.</p>
        <a href="index.php?r=listado-actividades" class="btn btn-primary">Volver</a>
      </div>
    </div>
  </section>
<?php
  return;
}

$preguntas = ControladorActividades::crtPreguntasActividad($idActividad);
$esExterna = ($actividad['tipoActividad'] ?? '') === 'externa';
$publicUrl = 'index.php?r=actividad-publica&slug=' . rawurlencode((string) $actividad['slug']);
$resultadoIntento = $_SESSION['actividad_resultado_' . $idActividad] ?? null;
unset($_SESSION['actividad_resultado_' . $idActividad]);
$idUsuarioActual = (int) ($_SESSION['usuario']['id'] ?? 0);
$intentosPermitidos = (int) ($actividad['intentosPermitidos'] ?? 1);
$intentosUsados = $idUsuarioActual > 0 ? ControladorActividades::crtIntentosUsadosUsuario($idActividad, $idUsuarioActual) : 0;
$intentosAgotados = ControladorActividades::intentosAgotados($actividad, $idUsuarioActual);
$renderIframe = static function ($embed, $url) use ($e) {
  $src = '';
  if (preg_match('/<iframe[^>]+src=["\']([^"\']+)["\']/i', (string) $embed, $match)) {
    $src = $match[1];
  } elseif (preg_match('/^https?:\/\//i', (string) $url)) {
    $src = (string) $url;
  }

  if ($src === '') {
    return '<p class="text-muted mb-0">No hay recurso externo cargado.</p>';
  }

  return '<div class="embed-responsive embed-responsive-16by9"><iframe class="embed-responsive-item" src="' . $e($src) . '" allowfullscreen loading="lazy"></iframe></div>';
};
$renderCodigo = static function ($codigo, $lenguaje) use ($e, $lenguajesCodigo) {
  $lineas = preg_split('/\r\n|\r|\n/', (string) $codigo);
  $lineas = $lineas === false ? [(string) $codigo] : $lineas;
  $contenido = [];
  foreach ($lineas as $indice => $linea) {
    $contenido[] = '<span class="activity-code__line"><span class="activity-code__number">' . (int) ($indice + 1) . '</span><span class="activity-code__text">' . $e($linea === '' ? ' ' : $linea) . '</span></span>';
  }

  return '<div class="activity-code mb-3"><div class="activity-code__header"><span>Codigo para revisar</span><span class="activity-code__badge">' . $e($lenguajesCodigo[$lenguaje] ?? 'Texto plano') . '</span></div><div class="activity-code__body">' . implode('', $contenido) . '</div></div>';
};
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
  .activity-code { border: 1px solid #dbe3ef; border-radius: 8px; overflow: hidden; background: linear-gradient(180deg, #111827 0%, #0f172a 100%); box-shadow: 0 16px 34px rgba(15, 23, 42, .12); }
  .activity-code__header { display: flex; justify-content: space-between; align-items: center; gap: .75rem; padding: .7rem .9rem; background: #111827; color: #cbd5e1; border-bottom: 1px solid rgba(148, 163, 184, .18); cursor: default; }
  .activity-code__badge { display: inline-flex; align-items: center; border-radius: 999px; background: rgba(59, 130, 246, .18); color: #bfdbfe; padding: .18rem .55rem; font-size: .72rem; font-weight: 600; }
  .activity-code__body { padding: .8rem 0; }
  .activity-code__line { display: grid; grid-template-columns: 56px 1fr; gap: .85rem; padding: 0 .9rem; font-family: Consolas, Monaco, monospace; font-size: .92rem; line-height: 1.65; color: #e2e8f0; white-space: pre-wrap; }
  .activity-code__number { color: #64748b; text-align: right; user-select: none; }
  .activity-code__text { overflow-wrap: anywhere; }
  .activity-code-answer { min-height: 120px; font-size: .95rem; cursor: text !important; }
</style>

<section class="content page-fade">
  <div class="container-fluid">
    <div class="entity-hero mb-4">
      <div class="entity-hero__content">
        <span class="entity-kicker mb-3"><?php echo $e($actividad['nombreCurso'] ?? 'Actividad'); ?></span>
        <h1 class="entity-title mb-2"><?php echo $e($actividad['tituloActividad']); ?></h1>
        <p class="entity-lead mb-0"><?php echo $e($actividad['descripcionActividad'] ?? ''); ?></p>
      </div>
    </div>

    <?php if (in_array($actividad['visibilidad'], ['publica', 'oculta'], true)): ?>
      <div class="alert alert-info d-flex justify-content-between align-items-center">
        <span>URL publica para WordPress: <code><?php echo $e($publicUrl); ?></code></span>
        <a href="<?php echo $e($publicUrl); ?>" target="_blank" class="btn btn-sm btn-outline-primary">Abrir publica</a>
      </div>
    <?php endif; ?>

    <div class="card glass-card">
      <div class="card-header">
        <h3 class="card-title mb-0"><?php echo $esExterna ? 'Recurso externo' : 'Resolver actividad'; ?></h3>
        <?php if (!$esExterna && $intentosPermitidos > 0): ?>
          <small class="text-muted">Intentos usados: <?php echo (int) $intentosUsados; ?> / <?php echo (int) $intentosPermitidos; ?></small>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <?php if ($esExterna): ?>
          <?php echo $renderIframe($actividad['recursoExternoEmbed'] ?? '', $actividad['recursoExternoUrl'] ?? ''); ?>
          <?php if (!empty($actividad['recursoExternoUrl'])): ?>
            <a href="<?php echo $e($actividad['recursoExternoUrl']); ?>" target="_blank" class="btn btn-primary mt-3">Abrir en nueva pestana</a>
          <?php endif; ?>
        <?php else: ?>
          <?php if (is_array($resultadoIntento)): ?>
            <div class="alert alert-light border">
              <h5 class="mb-2">Resultado del intento</h5>
              <p class="mb-3">Puntaje: <strong><?php echo (float) ($resultadoIntento['puntaje'] ?? 0); ?> / <?php echo (float) ($resultadoIntento['puntajeMaximo'] ?? 0); ?></strong></p>
              <?php foreach (($resultadoIntento['detalle'] ?? []) as $indexResultado => $itemResultado): ?>
                <div class="mb-2">
                  <strong><?php echo (int) ($indexResultado + 1); ?>. <?php echo $e($itemResultado['pregunta'] ?? ''); ?></strong>
                  <?php if (!empty($itemResultado['correcta'])): ?>
                    <span class="badge badge-success ml-2">Correcta</span>
                  <?php else: ?>
                    <span class="badge badge-danger ml-2">Incorrecta</span>
                    <?php if (!empty($itemResultado['explicacionError'])): ?>
                      <div class="text-muted mt-1"><?php echo $e($itemResultado['explicacionError']); ?></div>
                    <?php endif; ?>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <?php if ($intentosAgotados): ?>
            <div class="alert alert-warning mb-0">
              Ya usaste los intentos permitidos para esta actividad.
            </div>
          <?php else: ?>
          <form method="post">
            <input type="hidden" name="accion_actividad" value="responder_actividad">
            <input type="hidden" name="idActividad" value="<?php echo (int) $actividad['idActividad']; ?>">

            <?php foreach ($preguntas as $index => $pregunta): ?>
              <div class="card mb-3">
                <div class="card-body">
                  <h5><?php echo (int) ($index + 1); ?>. <?php echo $e($pregunta['textoPregunta']); ?></h5>
                  <?php if (!empty($pregunta['pista'])): ?>
                    <small class="text-muted d-block mb-2">Pista: <?php echo $e($pregunta['pista']); ?></small>
                  <?php endif; ?>

                  <?php if (($pregunta['tipoPregunta'] ?? '') === 'codigo' && !empty($pregunta['codigoBase'])): ?>
                    <?php echo $renderCodigo($pregunta['codigoBase'], (string) ($pregunta['lenguajeCodigo'] ?? 'plaintext')); ?>
                  <?php endif; ?>

                  <?php if (($pregunta['tipoPregunta'] ?? '') === 'multiple_choice'): ?>
                    <?php foreach ($pregunta['opciones'] as $opcion): ?>
                      <div class="custom-control custom-radio">
                        <input class="custom-control-input" type="radio" id="opcion<?php echo (int) $opcion['idOpcion']; ?>" name="respuesta[<?php echo (int) $pregunta['idPregunta']; ?>]" value="<?php echo (int) $opcion['idOpcion']; ?>" required>
                        <label class="custom-control-label" for="opcion<?php echo (int) $opcion['idOpcion']; ?>"><?php echo $e($opcion['textoOpcion']); ?></label>
                      </div>
                    <?php endforeach; ?>
                  <?php elseif (($pregunta['tipoPregunta'] ?? '') === 'completar' && !empty($pregunta['opciones'])): ?>
                    <?php
                      $opcionesCompletar = $pregunta['opciones'];
                      shuffle($opcionesCompletar);
                    ?>
                    <small class="text-muted d-block mb-2">Elegi la palabra que completa el espacio.</small>
                    <?php foreach ($opcionesCompletar as $opcion): ?>
                      <div class="custom-control custom-radio">
                        <input class="custom-control-input" type="radio" id="completar<?php echo (int) $opcion['idOpcion']; ?>" name="respuesta[<?php echo (int) $pregunta['idPregunta']; ?>]" value="<?php echo (int) $opcion['idOpcion']; ?>" required>
                        <label class="custom-control-label" for="completar<?php echo (int) $opcion['idOpcion']; ?>"><?php echo $e($opcion['textoOpcion']); ?></label>
                      </div>
                    <?php endforeach; ?>
                  <?php elseif (($pregunta['tipoPregunta'] ?? '') === 'verdadero_falso'): ?>
                    <div class="custom-control custom-radio">
                      <input class="custom-control-input" type="radio" id="vf<?php echo (int) $pregunta['idPregunta']; ?>v" name="respuesta[<?php echo (int) $pregunta['idPregunta']; ?>]" value="verdadero" required>
                      <label class="custom-control-label" for="vf<?php echo (int) $pregunta['idPregunta']; ?>v">Verdadero</label>
                    </div>
                    <div class="custom-control custom-radio">
                      <input class="custom-control-input" type="radio" id="vf<?php echo (int) $pregunta['idPregunta']; ?>f" name="respuesta[<?php echo (int) $pregunta['idPregunta']; ?>]" value="falso" required>
                      <label class="custom-control-label" for="vf<?php echo (int) $pregunta['idPregunta']; ?>f">Falso</label>
                    </div>
                  <?php elseif (($pregunta['tipoPregunta'] ?? '') === 'codigo'): ?>
                    <textarea class="form-control activity-code-answer" name="respuesta[<?php echo (int) $pregunta['idPregunta']; ?>]" rows="4" placeholder="Describe el error con tus palabras o escribe la correccion esperada." required></textarea>
                  <?php else: ?>
                    <input type="text" class="form-control" name="respuesta[<?php echo (int) $pregunta['idPregunta']; ?>]" required>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>

            <button type="submit" class="btn btn-primary">Entregar actividad</button>
          </form>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
