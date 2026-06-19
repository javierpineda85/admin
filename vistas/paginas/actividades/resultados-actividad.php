<?php
$idActividad = (int) ($_GET['idActividad'] ?? 0);
$actividad = ControladorActividades::crtBuscarActividadPorId($idActividad);
$e = static function ($valor) {
  return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
};

if (!$actividad || !ControladorActividades::puedeGestionarActividad($actividad)) {
?>
  <section class="content page-fade">
    <div class="container-fluid">
      <div class="empty-state">
        <i class="fas fa-lock"></i>
        <h4>No tenes acceso a los resultados</h4>
        <a href="index.php?r=listado-actividades" class="btn btn-primary">Volver</a>
      </div>
    </div>
  </section>
<?php
  return;
}

$panel = ControladorActividades::crtPanelResultadosActividad($idActividad);
$resumen = $panel['resumen'] ?? [];
$intentos = $panel['intentos'] ?? [];
$preguntas = $panel['preguntas'] ?? [];
$lenguajesCodigo = ControladorActividades::lenguajesCodigoDisponibles();
$puntajeMaximo = (float) ($actividad['puntajeMaximo'] ?? 0);
$esCodigo = ($actividad['tipoActividad'] ?? '') === 'codigo';
$porcentajePuntaje = static function ($puntaje, $maximo) {
  $puntaje = (float) $puntaje;
  $maximo = (float) $maximo;
  if ($maximo <= 0) {
    return 0;
  }
  return round(($puntaje / $maximo) * 100, 1);
};
$personaIntento = static function ($intento) use ($e) {
  $nombre = trim((string) (($intento['apellidoUsuario'] ?? '') . ' ' . ($intento['nombreUsuario'] ?? '')));
  if ($nombre !== '') {
    return $e($nombre);
  }
  $visitante = trim((string) ($intento['nombreVisitante'] ?? ''));
  return $e($visitante !== '' ? $visitante : 'Visitante');
};
?>

<style>
  .results-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem; }
  .results-metric { border: 1px solid rgba(148, 163, 184, .2); border-radius: 12px; padding: 1rem 1.1rem; background: #fff; min-height: 120px; }
  .results-metric__label { font-size: .82rem; text-transform: uppercase; letter-spacing: .04em; color: #64748b; margin-bottom: .45rem; }
  .results-metric__value { font-size: 1.8rem; font-weight: 700; line-height: 1.1; color: #0f172a; }
  .results-metric__meta { margin-top: .35rem; color: #64748b; font-size: .92rem; }
  .results-questions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
  .results-question { border: 1px solid rgba(148, 163, 184, .18); border-radius: 14px; background: #fff; overflow: hidden; }
  .results-question__head { padding: 1rem 1rem .85rem; border-bottom: 1px solid rgba(226, 232, 240, .9); }
  .results-question__title { font-size: 1.05rem; font-weight: 700; color: #0f172a; margin-bottom: .35rem; }
  .results-question__meta { display: flex; flex-wrap: wrap; gap: .5rem; }
  .results-question__body { padding: 1rem; }
  .results-chip { display: inline-flex; align-items: center; border-radius: 999px; padding: .28rem .7rem; font-size: .82rem; font-weight: 600; background: #f8fafc; border: 1px solid #dbe3ef; color: #334155; }
  .results-chip--ok { background: #ecfdf5; border-color: #bbf7d0; color: #166534; }
  .results-chip--warn { background: #fff7ed; border-color: #fed7aa; color: #c2410c; }
  .results-chip--code { background: #eef2ff; border-color: #c7d2fe; color: #4338ca; }
  .results-bars { display: grid; gap: .75rem; }
  .results-bar__row { display: flex; justify-content: space-between; gap: 1rem; font-size: .92rem; color: #334155; margin-bottom: .28rem; }
  .results-bar { height: 10px; border-radius: 999px; background: #e2e8f0; overflow: hidden; }
  .results-bar > span { display: block; height: 100%; border-radius: inherit; }
  .results-bar__ok { background: linear-gradient(90deg, #16a34a 0%, #4ade80 100%); }
  .results-bar__warn { background: linear-gradient(90deg, #f97316 0%, #fb923c 100%); }
  .results-list { margin: .9rem 0 0; padding-left: 1.15rem; color: #475569; }
  .results-list li + li { margin-top: .45rem; }
  .attempt-card { border: 1px solid rgba(148, 163, 184, .18); border-radius: 14px; background: #fff; overflow: hidden; }
  .attempt-card + .attempt-card { margin-top: 1rem; }
  .attempt-card__head { display: flex; justify-content: space-between; gap: 1rem; align-items: flex-start; padding: 1rem 1.1rem; border-bottom: 1px solid rgba(226, 232, 240, .9); }
  .attempt-card__title { font-size: 1rem; font-weight: 700; color: #0f172a; margin-bottom: .25rem; }
  .attempt-card__meta { color: #64748b; font-size: .92rem; }
  .attempt-card__score { text-align: right; min-width: 160px; }
  .attempt-card__score strong { display: block; font-size: 1.15rem; color: #0f172a; }
  .attempt-card__body { padding: 1rem 1.1rem; background: #fbfdff; }
  .attempt-response { border: 1px solid rgba(226, 232, 240, .95); border-radius: 12px; padding: .9rem 1rem; background: #fff; }
  .attempt-response + .attempt-response { margin-top: .75rem; }
  .attempt-response__top { display: flex; justify-content: space-between; gap: 1rem; align-items: flex-start; margin-bottom: .55rem; }
  .attempt-response__question { font-weight: 700; color: #0f172a; }
  .attempt-response__answer { color: #334155; white-space: pre-wrap; overflow-wrap: anywhere; }
  .attempt-response__feedback { margin-top: .55rem; color: #64748b; font-size: .92rem; }
  .attempt-response__expected { margin-top: .45rem; font-size: .92rem; color: #475569; }
  .empty-mini { padding: 1rem; border: 1px dashed #cbd5e1; border-radius: 12px; text-align: center; color: #64748b; background: #fff; }
  @media (max-width: 1200px) {
    .results-grid, .results-questions { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  }
  @media (max-width: 768px) {
    .results-grid, .results-questions { grid-template-columns: 1fr; }
    .attempt-card__head { flex-direction: column; }
    .attempt-card__score { text-align: left; min-width: 0; }
  }
</style>

<section class="content page-fade">
  <div class="container-fluid">
    <div class="entity-hero mb-4">
      <div class="entity-hero__content">
        <span class="entity-kicker mb-3">Resultados</span>
        <h1 class="entity-title mb-2"><?php echo $e($actividad['tituloActividad']); ?></h1>
        <p class="entity-lead mb-0">
          <?php echo $esCodigo ? 'Seguimiento de debugging, errores frecuentes y respuestas por intento.' : 'Intentos registrados para estudiantes y visitantes.'; ?>
        </p>
      </div>
    </div>

    <div class="results-grid mb-4">
      <div class="results-metric">
        <div class="results-metric__label">Intentos registrados</div>
        <div class="results-metric__value"><?php echo (int) ($resumen['totalIntentos'] ?? 0); ?></div>
        <div class="results-metric__meta"><?php echo (int) ($resumen['personasUnicas'] ?? 0); ?> personas unicas</div>
      </div>
      <div class="results-metric">
        <div class="results-metric__label">Promedio general</div>
        <div class="results-metric__value"><?php echo (float) ($resumen['promedioPuntaje'] ?? 0); ?></div>
        <div class="results-metric__meta">sobre <?php echo $puntajeMaximo; ?> puntos</div>
      </div>
      <div class="results-metric">
        <div class="results-metric__label">Mejor resultado</div>
        <div class="results-metric__value"><?php echo (float) ($resumen['mejorPuntaje'] ?? 0); ?></div>
        <div class="results-metric__meta">minimo registrado: <?php echo (float) ($resumen['menorPuntaje'] ?? 0); ?></div>
      </div>
      <div class="results-metric">
        <div class="results-metric__label">Precision global</div>
        <div class="results-metric__value"><?php echo (float) ($resumen['precisionGlobal'] ?? 0); ?>%</div>
        <div class="results-metric__meta"><?php echo (int) ($resumen['respuestasCorrectas'] ?? 0); ?> correctas de <?php echo (int) ($resumen['respuestasTotales'] ?? 0); ?></div>
      </div>
    </div>

    <div class="card glass-card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0"><?php echo $esCodigo ? 'Analitica del desafio de codigo' : 'Analitica por pregunta'; ?></h3>
        <a href="index.php?r=listado-actividades" class="btn btn-light btn-sm border">Volver</a>
      </div>
      <div class="card-body">
        <?php if (empty($preguntas)): ?>
          <div class="empty-mini">Todavia no hay respuestas registradas para construir analitica.</div>
        <?php else: ?>
          <div class="results-questions">
            <?php foreach ($preguntas as $index => $pregunta): ?>
              <article class="results-question">
                <div class="results-question__head">
                  <div class="results-question__title"><?php echo (int) ($index + 1); ?>. <?php echo $e($pregunta['textoPregunta'] ?? ''); ?></div>
                  <div class="results-question__meta">
                    <span class="results-chip"><?php echo $e(ControladorActividades::tiposDisponibles()[$pregunta['tipoPregunta'] ?? ''] ?? 'Pregunta'); ?></span>
                    <?php if (($pregunta['tipoPregunta'] ?? '') === 'codigo'): ?>
                      <span class="results-chip results-chip--code"><?php echo $e($lenguajesCodigo[$pregunta['lenguajeCodigo'] ?? 'plaintext'] ?? 'Texto plano'); ?></span>
                    <?php endif; ?>
                    <span class="results-chip"><?php echo (float) ($pregunta['puntaje'] ?? 0); ?> pts</span>
                  </div>
                </div>
                <div class="results-question__body">
                  <div class="results-bars">
                    <div>
                      <div class="results-bar__row">
                        <span>Tasa de acierto</span>
                        <strong><?php echo (float) ($pregunta['tasaAcierto'] ?? 0); ?>%</strong>
                      </div>
                      <div class="results-bar"><span class="results-bar__ok" style="width: <?php echo min(100, max(0, (float) ($pregunta['tasaAcierto'] ?? 0))); ?>%"></span></div>
                    </div>
                    <div>
                      <div class="results-bar__row">
                        <span>Tasa de error</span>
                        <strong><?php echo (float) ($pregunta['tasaError'] ?? 0); ?>%</strong>
                      </div>
                      <div class="results-bar"><span class="results-bar__warn" style="width: <?php echo min(100, max(0, (float) ($pregunta['tasaError'] ?? 0))); ?>%"></span></div>
                    </div>
                  </div>

                  <div class="d-flex flex-wrap gap-2 mt-3">
                    <span class="results-chip results-chip--ok"><?php echo (int) ($pregunta['respuestasCorrectas'] ?? 0); ?> correctas</span>
                    <span class="results-chip results-chip--warn"><?php echo (int) ($pregunta['respuestasIncorrectas'] ?? 0); ?> incorrectas</span>
                    <span class="results-chip"><?php echo (int) ($pregunta['totalRespuestas'] ?? 0); ?> respuestas</span>
                  </div>

                  <?php if (($pregunta['tipoPregunta'] ?? '') === 'codigo'): ?>
                    <div class="attempt-response__expected">
                      <strong>Respuesta esperada:</strong> <?php echo $e($pregunta['respuestaCorrecta'] ?? ''); ?>
                    </div>
                    <?php if (!empty($pregunta['erroresFrecuentes'])): ?>
                      <ul class="results-list">
                        <?php foreach ($pregunta['erroresFrecuentes'] as $errorFrecuente): ?>
                          <li>
                            <strong><?php echo (int) ($errorFrecuente['total'] ?? 0); ?>x</strong>
                            <?php echo $e($errorFrecuente['textoRespuesta'] ?? ''); ?>
                          </li>
                        <?php endforeach; ?>
                      </ul>
                    <?php else: ?>
                      <p class="text-muted mb-0 mt-3">Todavia no hay respuestas incorrectas repetidas para esta consigna.</p>
                    <?php endif; ?>
                  <?php endif; ?>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card glass-card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Intentos detallados</h3>
        <span class="text-muted small">Vista por estudiante o visitante</span>
      </div>
      <div class="card-body">
        <?php if (empty($intentos)): ?>
          <div class="empty-mini">Todavia no se registraron intentos en esta actividad.</div>
        <?php else: ?>
          <?php foreach ($intentos as $intento): ?>
            <article class="attempt-card">
              <div class="attempt-card__head">
                <div>
                  <div class="attempt-card__title">
                    <?php echo $personaIntento($intento); ?>
                    <span class="badge badge-light border ml-2">Intento <?php echo (int) ($intento['numeroIntentoPersona'] ?? 1); ?></span>
                  </div>
                  <div class="attempt-card__meta">
                    <?php echo $e($intento['email'] ?? $intento['emailVisitante'] ?? 'Sin email'); ?> ·
                    <?php echo $e($intento['fechaEntrega'] ?? ''); ?> ·
                    Estado: <?php echo $e($intento['estadoIntento'] ?? ''); ?>
                  </div>
                </div>
                <div class="attempt-card__score">
                  <strong><?php echo (float) ($intento['puntaje'] ?? 0); ?> / <?php echo $puntajeMaximo; ?></strong>
                  <span class="text-muted"><?php echo $porcentajePuntaje($intento['puntaje'] ?? 0, $puntajeMaximo); ?>% del puntaje</span>
                </div>
              </div>
              <div class="attempt-card__body">
                <?php if (empty($intento['respuestas'])): ?>
                  <div class="empty-mini">Este intento no tiene respuestas visibles.</div>
                <?php else: ?>
                  <?php foreach ($intento['respuestas'] as $indexRespuesta => $respuesta): ?>
                    <div class="attempt-response">
                      <div class="attempt-response__top">
                        <div class="attempt-response__question"><?php echo (int) ($indexRespuesta + 1); ?>. <?php echo $e($respuesta['pregunta'] ?? ''); ?></div>
                        <span class="badge badge-<?php echo !empty($respuesta['esCorrecta']) ? 'success' : 'danger'; ?>">
                          <?php echo !empty($respuesta['esCorrecta']) ? 'Correcta' : 'Incorrecta'; ?>
                        </span>
                      </div>
                      <div class="attempt-response__answer"><?php echo $e($respuesta['textoRespuesta'] !== '' ? $respuesta['textoRespuesta'] : 'Sin respuesta escrita'); ?></div>
                      <div class="attempt-response__expected">
                        <strong>Esperado:</strong> <?php echo $e($respuesta['respuestaCorrecta'] ?? ''); ?> ·
                        <strong>Puntaje:</strong> <?php echo (float) ($respuesta['puntajeObtenido'] ?? 0); ?> / <?php echo (float) ($respuesta['puntajePregunta'] ?? 0); ?>
                      </div>
                      <?php if (empty($respuesta['esCorrecta']) && !empty($respuesta['explicacionError'])): ?>
                        <div class="attempt-response__feedback"><?php echo $e($respuesta['explicacionError']); ?></div>
                      <?php endif; ?>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
