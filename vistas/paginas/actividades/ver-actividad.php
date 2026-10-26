<?php
require_once __DIR__ . '/../../../controladores/seguridad-html.php';
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
$urlExternaSegura = SeguridadHtml::urlHttpSegura($actividad['recursoExternoUrl'] ?? '', false);
$renderIframe = static function ($embed, $url) use ($e) {
  $src = '';
  if (preg_match('/<iframe[^>]+src=["\']([^"\']+)["\']/i', (string) $embed, $match)) {
    $src = $match[1];
  } elseif (preg_match('/^https?:\/\//i', (string) $url)) {
    $src = (string) $url;
  }

  $src = SeguridadHtml::urlHttpSegura($src, false);
  if ($src === '') {
    return '<p class="text-muted mb-0">No hay recurso externo cargado.</p>';
  }

  return '<div class="embed-responsive embed-responsive-16by9"><iframe class="embed-responsive-item" src="' . $e($src) . '" sandbox="allow-scripts allow-same-origin allow-forms allow-presentation" referrerpolicy="no-referrer" allowfullscreen loading="lazy"></iframe></div>';
};
$renderCodigo = static function ($codigo, $lenguaje) use ($e, $lenguajesCodigo) {
  $contenido = ControladorActividades::renderCodigoConEstilo($codigo, $lenguaje, $e);
  return '<div class="activity-code mb-3"><div class="activity-code__header"><span>Codigo para revisar</span><span class="activity-code__badge">' . $e($lenguajesCodigo[$lenguaje] ?? 'Texto plano') . '</span></div><div class="activity-code__body">' . $contenido . '</div></div>';
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
  .activity-code__token--kw { color: #c792ea; font-weight: 600; }
  .activity-code__token--fn { color: #82aaff; }
  .activity-code__token--var { color: #ffcb6b; }
  .activity-code__token--num { color: #f78c6c; }
  .activity-code__token--tag { color: #7fdbca; }
  .activity-code__token--attr { color: #c3e88d; }
  .activity-code__token--string { color: #ecc48d; }
  .activity-code-answer { min-height: 120px; font-size: .95rem; cursor: text !important; }
  .activity-workspace { overflow: visible; }
  .activity-workspace > .card-header { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.1rem 1.25rem; }
  .activity-workspace__meta { color: var(--text-muted, #64748b); font-size: .88rem; white-space: nowrap; }
  .activity-result { padding: 1.15rem 1.2rem; border: 1px solid rgba(22, 163, 74, .22); border-radius: 16px; background: linear-gradient(135deg, rgba(236, 253, 245, .95), rgba(255, 255, 255, .96)); }
  .activity-result__head { display: flex; align-items: flex-start; gap: .85rem; margin-bottom: 1rem; }
  .activity-result__icon { display: inline-flex; align-items: center; justify-content: center; width: 42px; height: 42px; flex: 0 0 42px; border-radius: 13px; background: #dcfce7; color: #15803d; }
  .activity-result__title { margin: 0 0 .15rem; font-size: 1rem; font-weight: 700; color: #14532d; }
  .activity-result__score { margin: 0; color: #166534; }
  .activity-result__items { display: grid; gap: .65rem; }
  .activity-result__item { padding-top: .7rem; border-top: 1px solid rgba(22, 163, 74, .16); }
  .activity-result__question { color: #1f2937; font-weight: 600; }
  .activity-result__feedback { margin-top: .35rem; color: var(--text-muted, #64748b); font-size: .9rem; }
  .activity-flow__layout { display: grid; grid-template-columns: minmax(200px, 248px) minmax(0, 1fr); gap: 1.25rem; align-items: start; }
  .activity-flow__rail { position: sticky; top: 1rem; padding: 1rem; border: 1px solid rgba(89, 36, 155, .12); border-radius: 18px; background: linear-gradient(160deg, rgba(89, 36, 155, .07), rgba(255, 255, 255, .95) 38%); }
  .activity-flow__rail-kicker { margin-bottom: .25rem; color: var(--primary, #59249b); font-size: .72rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; }
  .activity-flow__rail-title { margin: 0; color: var(--text, #1f2937); font-size: 1rem; font-weight: 700; }
  .activity-flow__progress { height: 8px; margin: .9rem 0 .5rem; overflow: hidden; border-radius: 999px; background: #e7eaf1; }
  .activity-flow__progress > span { display: block; width: 0; height: 100%; border-radius: inherit; background: linear-gradient(90deg, var(--primary, #59249b), #8b5cf6); transition: width .2s ease; }
  .activity-flow__progress-label { color: var(--text-muted, #64748b); font-size: .78rem; }
  .activity-flow__nav { display: grid; gap: .3rem; margin-top: 1rem; }
  .activity-flow__nav-item { display: flex; align-items: center; gap: .65rem; padding: .65rem .7rem; border: 1px solid transparent; border-radius: 12px; color: var(--text-muted, #64748b); font-size: .87rem; text-decoration: none; transition: background .18s ease, border-color .18s ease, color .18s ease; }
  .activity-flow__nav-item:hover { color: var(--primary, #59249b); background: rgba(89, 36, 155, .06); text-decoration: none; }
  .activity-flow__nav-number { display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; flex: 0 0 26px; border-radius: 9px; color: var(--primary, #59249b); background: rgba(89, 36, 155, .1); font-size: .75rem; font-weight: 700; }
  .activity-flow__nav-item.is-active { border-color: rgba(89, 36, 155, .18); color: var(--primary, #59249b); background: #fff; font-weight: 700; box-shadow: 0 8px 18px rgba(89, 36, 155, .08); }
  .activity-flow__nav-item.is-answered .activity-flow__nav-number { color: #fff; background: var(--success, #28a745); }
  .activity-flow__question { margin-bottom: 0; border: 1px solid rgba(15, 23, 42, .08); border-radius: 18px; box-shadow: 0 12px 28px rgba(15, 23, 42, .06); }
  .activity-flow__question .card-body { padding: 1.5rem; }
  .activity-flow__question-meta { display: flex; flex-wrap: wrap; align-items: center; gap: .6rem; margin-bottom: .85rem; }
  .activity-flow__question-index { display: inline-flex; align-items: center; border-radius: 999px; padding: .32rem .7rem; color: var(--primary, #59249b); background: rgba(89, 36, 155, .1); font-size: .75rem; font-weight: 700; }
  .activity-flow__question-type { color: var(--text-muted, #64748b); font-size: .8rem; }
  .activity-flow__question-title { margin: 0; color: var(--text, #1f2937); font-size: 1.2rem; font-weight: 700; line-height: 1.4; }
  .activity-flow__hint { display: flex; gap: .55rem; align-items: flex-start; margin: 1rem 0; padding: .8rem .9rem; border-radius: 12px; color: #75450b; background: #fff5e9; font-size: .9rem; }
  .activity-flow__hint i { margin-top: .16rem; }
  .activity-options { display: grid; gap: .7rem; margin-top: 1.25rem; }
  .activity-option { min-height: 0; padding-left: 0; }
  .activity-option .custom-control-input { z-index: 1; top: 50%; left: .85rem; width: 30px; height: 30px; transform: translateY(-50%); }
  .activity-option__label { display: flex; align-items: center; gap: .8rem; width: 100%; min-height: 58px; margin: 0; padding: .78rem 3rem .78rem 1rem; border: 1px solid #dfe4ee; border-radius: 14px; color: var(--text, #1f2937) !important; background: #fff; font-size: .95rem; font-weight: 600; line-height: 1.4; cursor: pointer; transition: border-color .18s ease, background .18s ease, box-shadow .18s ease; }
  .activity-option__label::before, .activity-option__label::after { display: none; }
  .activity-option__letter { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; flex: 0 0 30px; border-radius: 10px; color: var(--primary, #59249b); background: rgba(89, 36, 155, .1); font-size: .8rem; font-weight: 700; }
  .activity-option .custom-control-input:focus ~ .activity-option__label { box-shadow: 0 0 0 .18rem rgba(89, 36, 155, .12); }
  .activity-option .custom-control-input:checked ~ .activity-option__label { border-color: var(--primary, #59249b); color: var(--primary, #59249b) !important; background: rgba(89, 36, 155, .055); box-shadow: 0 8px 18px rgba(89, 36, 155, .08); }
  .activity-option .custom-control-input:checked ~ .activity-option__label .activity-option__letter { color: #fff; background: var(--primary, #59249b); }
  .activity-flow__answer-field { margin-top: 1.25rem; }
  .activity-flow__validation { margin-top: 1rem; color: #b42318; font-size: .88rem; font-weight: 600; }
  .activity-flow__actions { display: none; justify-content: space-between; gap: .75rem; margin-top: 1.4rem; padding-top: 1rem; border-top: 1px solid #edf0f5; }
  .activity-flow__actions .btn + .btn { margin-left: .5rem; }
  .activity-flow__submit-fallback { margin-top: 1.25rem; }
  .activity-flow.activity-flow--ready .activity-flow__question { display: none; }
  .activity-flow.activity-flow--ready .activity-flow__question.is-active { display: block; }
  .activity-flow.activity-flow--ready .activity-flow__question.is-active .activity-flow__actions { display: flex; }
  .activity-flow.activity-flow--ready .activity-flow__submit-fallback { display: none; }
  @media (max-width: 767.98px) {
    .activity-workspace > .card-header { align-items: flex-start; flex-direction: column; }
    .activity-flow__layout { grid-template-columns: 1fr; }
    .activity-flow__rail { position: static; }
    .activity-flow__nav { display: flex; flex-wrap: wrap; }
    .activity-flow__nav-item { width: 39px; height: 39px; justify-content: center; padding: 0; }
    .activity-flow__nav-item > span:last-child { position: absolute; width: 1px; height: 1px; padding: 0; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
    .activity-flow__question .card-body { padding: 1.15rem; }
    .activity-flow__question-title { font-size: 1.08rem; }
  }
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

    <div class="card glass-card activity-workspace">
      <div class="card-header">
        <h3 class="card-title mb-0"><?php echo $esExterna ? 'Recurso externo' : 'Resolver actividad'; ?></h3>
        <?php if (!$esExterna && $intentosPermitidos > 0): ?>
          <small class="activity-workspace__meta">Intentos usados: <?php echo (int) $intentosUsados; ?> / <?php echo (int) $intentosPermitidos; ?></small>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <?php if ($esExterna): ?>
          <?php echo $renderIframe($actividad['recursoExternoEmbed'] ?? '', $actividad['recursoExternoUrl'] ?? ''); ?>
          <?php if ($urlExternaSegura !== ''): ?>
            <a href="<?php echo $e($urlExternaSegura); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary mt-3">Abrir en nueva pestana</a>
          <?php endif; ?>
        <?php else: ?>
          <?php if (is_array($resultadoIntento)): ?>
            <section class="activity-result mb-4" aria-label="Resultado del intento">
              <div class="activity-result__head">
                <span class="activity-result__icon"><i class="fas fa-check" aria-hidden="true"></i></span>
                <div>
                  <h4 class="activity-result__title">Resultado del intento</h4>
                  <p class="activity-result__score">Puntaje: <strong><?php echo (float) ($resultadoIntento['puntaje'] ?? 0); ?> / <?php echo (float) ($resultadoIntento['puntajeMaximo'] ?? 0); ?></strong></p>
                </div>
              </div>
              <div class="activity-result__items">
              <?php foreach (($resultadoIntento['detalle'] ?? []) as $indexResultado => $itemResultado): ?>
                <div class="activity-result__item">
                  <div class="activity-result__question">
                    <?php echo (int) ($indexResultado + 1); ?>. <?php echo $e($itemResultado['pregunta'] ?? ''); ?>
                  <?php if (!empty($itemResultado['correcta'])): ?>
                    <span class="badge badge-success ml-2">Correcta</span>
                  <?php else: ?>
                    <span class="badge badge-danger ml-2">Incorrecta</span>
                    <?php if (!empty($itemResultado['explicacionError'])): ?>
                      <div class="activity-result__feedback"><?php echo $e($itemResultado['explicacionError']); ?></div>
                    <?php endif; ?>
                  <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
              </div>
            </section>
          <?php endif; ?>

          <?php if ($intentosAgotados): ?>
            <div class="alert alert-warning mb-0">
              Ya usaste los intentos permitidos para esta actividad.
            </div>
          <?php else: ?>
          <?php $totalPreguntas = count($preguntas); ?>
          <form method="post" class="activity-flow" data-activity-flow>
            <input type="hidden" name="accion_actividad" value="responder_actividad">
            <input type="hidden" name="idActividad" value="<?php echo (int) $actividad['idActividad']; ?>">

            <div class="activity-flow__layout">
              <aside class="activity-flow__rail" aria-label="Preguntas de la actividad">
                <div class="activity-flow__rail-kicker">Tu recorrido</div>
                <h4 class="activity-flow__rail-title">Resolver actividad</h4>
                <div class="activity-flow__progress" role="progressbar" aria-label="Progreso de preguntas" aria-valuemin="0" aria-valuemax="<?php echo (int) $totalPreguntas; ?>" aria-valuenow="0"><span data-activity-progress></span></div>
                <div class="activity-flow__progress-label" data-activity-progress-label>Pregunta 1 de <?php echo (int) $totalPreguntas; ?></div>
                <nav class="activity-flow__nav" aria-label="Ir a una pregunta">
                  <?php foreach ($preguntas as $indiceNavegacion => $preguntaNavegacion): ?>
                    <a class="activity-flow__nav-item<?php echo $indiceNavegacion === 0 ? ' is-active' : ''; ?>" href="#activity-question-<?php echo (int) $preguntaNavegacion['idPregunta']; ?>" data-activity-nav="<?php echo (int) $indiceNavegacion; ?>">
                      <span class="activity-flow__nav-number"><?php echo (int) ($indiceNavegacion + 1); ?></span>
                      <span>Pregunta <?php echo (int) ($indiceNavegacion + 1); ?></span>
                    </a>
                  <?php endforeach; ?>
                </nav>
              </aside>

              <div class="activity-flow__questions">
            <?php foreach ($preguntas as $index => $pregunta): ?>
              <article id="activity-question-<?php echo (int) $pregunta['idPregunta']; ?>" class="card activity-flow__question<?php echo $index === 0 ? ' is-active' : ''; ?>" data-activity-question="<?php echo (int) $index; ?>">
                <div class="card-body">
                  <div class="activity-flow__question-meta">
                    <span class="activity-flow__question-index">Pregunta <?php echo (int) ($index + 1); ?> de <?php echo (int) $totalPreguntas; ?></span>
                  </div>
                  <h5 class="activity-flow__question-title"><?php echo $e($pregunta['textoPregunta']); ?></h5>
                  <?php if (!empty($pregunta['pista'])): ?>
                    <div class="activity-flow__hint"><i class="fas fa-lightbulb" aria-hidden="true"></i><span><strong>Pista:</strong> <?php echo $e($pregunta['pista']); ?></span></div>
                  <?php endif; ?>

                  <?php if (($pregunta['tipoPregunta'] ?? '') === 'codigo' && !empty($pregunta['codigoBase'])): ?>
                    <?php echo $renderCodigo($pregunta['codigoBase'], (string) ($pregunta['lenguajeCodigo'] ?? 'plaintext')); ?>
                  <?php endif; ?>

                  <?php if (($pregunta['tipoPregunta'] ?? '') === 'multiple_choice'): ?>
                    <div class="activity-options">
                    <?php foreach ($pregunta['opciones'] as $indiceOpcion => $opcion): ?>
                      <div class="custom-control custom-radio activity-option">
                        <input class="custom-control-input" type="radio" id="opcion<?php echo (int) $opcion['idOpcion']; ?>" name="respuesta[<?php echo (int) $pregunta['idPregunta']; ?>]" value="<?php echo (int) $opcion['idOpcion']; ?>" required>
                        <label class="custom-control-label activity-option__label" for="opcion<?php echo (int) $opcion['idOpcion']; ?>"><span class="activity-option__letter"><?php echo chr(65 + $indiceOpcion); ?></span><span><?php echo $e($opcion['textoOpcion']); ?></span></label>
                      </div>
                    <?php endforeach; ?>
                    </div>
                  <?php elseif (($pregunta['tipoPregunta'] ?? '') === 'completar' && !empty($pregunta['opciones'])): ?>
                    <?php
                      $opcionesCompletar = $pregunta['opciones'];
                      shuffle($opcionesCompletar);
                    ?>
                    <div class="activity-flow__hint"><i class="fas fa-puzzle-piece" aria-hidden="true"></i><span>Elegi la palabra que completa el espacio.</span></div>
                    <div class="activity-options">
                    <?php foreach ($opcionesCompletar as $indiceOpcion => $opcion): ?>
                      <div class="custom-control custom-radio activity-option">
                        <input class="custom-control-input" type="radio" id="completar<?php echo (int) $opcion['idOpcion']; ?>" name="respuesta[<?php echo (int) $pregunta['idPregunta']; ?>]" value="<?php echo (int) $opcion['idOpcion']; ?>" required>
                        <label class="custom-control-label activity-option__label" for="completar<?php echo (int) $opcion['idOpcion']; ?>"><span class="activity-option__letter"><?php echo chr(65 + $indiceOpcion); ?></span><span><?php echo $e($opcion['textoOpcion']); ?></span></label>
                      </div>
                    <?php endforeach; ?>
                    </div>
                  <?php elseif (($pregunta['tipoPregunta'] ?? '') === 'verdadero_falso'): ?>
                    <div class="activity-options">
                    <div class="custom-control custom-radio activity-option">
                      <input class="custom-control-input" type="radio" id="vf<?php echo (int) $pregunta['idPregunta']; ?>v" name="respuesta[<?php echo (int) $pregunta['idPregunta']; ?>]" value="verdadero" required>
                      <label class="custom-control-label activity-option__label" for="vf<?php echo (int) $pregunta['idPregunta']; ?>v"><span class="activity-option__letter">V</span><span>Verdadero</span></label>
                    </div>
                    <div class="custom-control custom-radio activity-option">
                      <input class="custom-control-input" type="radio" id="vf<?php echo (int) $pregunta['idPregunta']; ?>f" name="respuesta[<?php echo (int) $pregunta['idPregunta']; ?>]" value="falso" required>
                      <label class="custom-control-label activity-option__label" for="vf<?php echo (int) $pregunta['idPregunta']; ?>f"><span class="activity-option__letter">F</span><span>Falso</span></label>
                    </div>
                    </div>
                  <?php elseif (($pregunta['tipoPregunta'] ?? '') === 'codigo'): ?>
                    <div class="activity-flow__answer-field"><textarea class="form-control activity-code-answer" name="respuesta[<?php echo (int) $pregunta['idPregunta']; ?>]" rows="4" placeholder="Describe el error con tus palabras o escribe la correccion esperada." required></textarea></div>
                  <?php else: ?>
                    <div class="activity-flow__answer-field"><input type="text" class="form-control" name="respuesta[<?php echo (int) $pregunta['idPregunta']; ?>]" required></div>
                  <?php endif; ?>
                  <div class="activity-flow__validation" data-activity-validation role="alert" hidden>Respondé esta pregunta antes de entregar la actividad.</div>
                  <div class="activity-flow__actions">
                    <button type="button" class="btn btn-light border" data-activity-previous><i class="fas fa-arrow-left mr-1" aria-hidden="true"></i>Anterior</button>
                    <div>
                      <button type="button" class="btn btn-primary" data-activity-next>Siguiente<i class="fas fa-arrow-right ml-1" aria-hidden="true"></i></button>
                      <button type="submit" class="btn btn-primary" data-activity-submit>Entregar actividad<i class="fas fa-check ml-1" aria-hidden="true"></i></button>
                    </div>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
              <div class="activity-flow__submit-fallback"><button type="submit" class="btn btn-primary">Entregar actividad</button></div>
              </div>
            </div>
          </form>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var flow = document.querySelector('[data-activity-flow]');
  if (!flow) {
    return;
  }

  var questions = Array.prototype.slice.call(flow.querySelectorAll('[data-activity-question]'));
  var navigation = Array.prototype.slice.call(flow.querySelectorAll('[data-activity-nav]'));
  var progress = flow.querySelector('[data-activity-progress]');
  var progressLabel = flow.querySelector('[data-activity-progress-label]');
  var progressContainer = flow.querySelector('.activity-flow__progress');
  var total = questions.length;
  var activeIndex = 0;

  if (total === 0) {
    return;
  }

  flow.classList.add('activity-flow--ready');
  flow.noValidate = true;

  function questionHasAnswer(question) {
    var selected = question.querySelector('input[type="radio"]:checked');
    if (selected) {
      return true;
    }

    var field = question.querySelector('textarea[name^="respuesta["] , input[type="text"][name^="respuesta["]');
    return !!field && field.value.trim() !== '';
  }

  function updateAnsweredState() {
    questions.forEach(function (question, index) {
      if (navigation[index]) {
        navigation[index].classList.toggle('is-answered', questionHasAnswer(question));
      }
    });
  }

  function showQuestion(index) {
    activeIndex = Math.max(0, Math.min(index, total - 1));
    questions.forEach(function (question, questionIndex) {
      var isActive = questionIndex === activeIndex;
      question.classList.toggle('is-active', isActive);
      var validation = question.querySelector('[data-activity-validation]');
      if (validation) {
        validation.hidden = true;
      }
    });
    navigation.forEach(function (item, navigationIndex) {
      item.classList.toggle('is-active', navigationIndex === activeIndex);
      item.setAttribute('aria-current', navigationIndex === activeIndex ? 'step' : 'false');
    });

    var currentQuestion = questions[activeIndex];
    var previous = currentQuestion.querySelector('[data-activity-previous]');
    var next = currentQuestion.querySelector('[data-activity-next]');
    var submit = currentQuestion.querySelector('[data-activity-submit]');
    if (previous) {
      previous.hidden = activeIndex === 0;
    }
    if (next) {
      next.hidden = activeIndex === total - 1;
    }
    if (submit) {
      submit.hidden = activeIndex !== total - 1;
    }

    var completed = activeIndex + 1;
    if (progress) {
      progress.style.width = ((completed / total) * 100) + '%';
    }
    if (progressLabel) {
      progressLabel.textContent = 'Pregunta ' + completed + ' de ' + total;
    }
    if (progressContainer) {
      progressContainer.setAttribute('aria-valuenow', String(completed));
    }
  }

  navigation.forEach(function (item) {
    item.addEventListener('click', function (event) {
      event.preventDefault();
      showQuestion(Number(item.getAttribute('data-activity-nav')));
    });
  });

  questions.forEach(function (question, index) {
    var previous = question.querySelector('[data-activity-previous]');
    var next = question.querySelector('[data-activity-next]');
    if (previous) {
      previous.addEventListener('click', function () {
        showQuestion(index - 1);
      });
    }
    if (next) {
      next.addEventListener('click', function () {
        showQuestion(index + 1);
      });
    }
    question.querySelectorAll('input, textarea').forEach(function (field) {
      field.addEventListener('input', updateAnsweredState);
      field.addEventListener('change', updateAnsweredState);
    });
  });

  flow.addEventListener('submit', function (event) {
    var unansweredIndex = questions.findIndex(function (question) {
      return !questionHasAnswer(question);
    });
    if (unansweredIndex === -1) {
      return;
    }

    event.preventDefault();
    showQuestion(unansweredIndex);
    var validation = questions[unansweredIndex].querySelector('[data-activity-validation]');
    if (validation) {
      validation.hidden = false;
    }
  });

  updateAnsweredState();
  showQuestion(0);
});
</script>
