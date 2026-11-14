<?php
require_once __DIR__ . '/../../../controladores/seguridad-html.php';
ControladorActividades::crtProcesarAcciones();

$slug = trim((string) ($_GET['slug'] ?? ''));
$actividad = $slug !== '' ? ControladorActividades::crtBuscarActividadPorSlug($slug) : null;
$actividadesPublicas = $slug === '' ? ControladorActividades::crtListarPublicas() : [];
$mensajeOk = $_SESSION['success_message'] ?? '';
$mensajeError = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$e = static function ($valor) {
  return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
};
$tipoLabels = ControladorActividades::tiposDisponibles();
$lenguajesCodigo = ControladorActividades::lenguajesCodigoDisponibles();

$renderIframe = static function ($embed, $url) use ($e) {
  $src = '';
  if (preg_match('/<iframe[^>]+src=["\']([^"\']+)["\']/i', (string) $embed, $match)) {
    $src = $match[1];
  } elseif (preg_match('/^https?:\/\//i', (string) $url)) {
    $src = (string) $url;
  }

  $src = SeguridadHtml::urlHttpSegura($src, false);
  if ($src === '') {
    return '<p class="muted">No hay recurso externo cargado.</p>';
  }

  return '<iframe class="activity-frame" src="' . $e($src) . '" sandbox="allow-scripts allow-same-origin allow-forms allow-presentation" referrerpolicy="no-referrer" allowfullscreen loading="lazy"></iframe>';
};
$renderCodigo = static function ($codigo, $lenguaje) use ($e, $lenguajesCodigo) {
  $contenido = ControladorActividades::renderCodigoConEstilo($codigo, $lenguaje, $e);
  $contenido = str_replace(
    ['activity-code__line', 'activity-code__number', 'activity-code__text'],
    ['code-line', 'code-line__number', 'code-line__text'],
    $contenido
  );
  return '<div class="code-box"><div class="code-box__header"><span>Codigo para revisar</span><span class="code-pill">' . $e($lenguajesCodigo[$lenguaje] ?? 'Texto plano') . '</span></div><div class="code-box__body">' . $contenido . '</div></div>';
};
$esArrastre = static function ($tipo) { return in_array((string)$tipo, ['arrastrar_pares','arrastrar_orden','arrastrar_completar'], true); };
$renderTarjetaArrastre = static function ($opcion) use ($e) {
  $html='<strong>' . $e($opcion['textoOpcion'] ?? '') . '</strong>';
  if (strpos((string)($opcion['imagenOpcion']??''),'uploads/actividades/')===0) { $html.='<img src="'.$e($opcion['imagenOpcion']).'" alt="">'; }
  if (strpos((string)($opcion['audioOpcion']??''),'uploads/actividades/')===0) { $html.='<audio controls preload="metadata" src="'.$e($opcion['audioOpcion']).'"></audio>'; }
  return $html;
};
$renderEnunciadoCompletar = static function ($texto) use ($e) {
  $partes = preg_split('/\[\[[^\]\r\n]+\]\]|_{2,}/u', (string) $texto);
  if (count($partes) < 2) { return nl2br($e($texto)); }
  $html = ''; $espacio = 0;
  foreach ($partes as $indice => $parte) {
    $html .= nl2br($e($parte));
    if ($indice < count($partes) - 1) { $espacio++; $html .= '<span class="public-dnd-zone public-dnd-zone--inline" data-dnd-zone data-dnd-blank="' . $espacio . '"></span>'; }
  }
  return $html;
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo $actividad ? $e($actividad['tituloActividad']) : 'Actividades publicas'; ?> | MenteMotion</title>
  <link rel="icon" type="image/png" sizes="16x16" href="/img/favicon-16x16.png?v=2">
  <style>
    :root { --ink:#172033; --muted:#667085; --line:#d9e0ea; --brand:#2563eb; --bg:#f5f7fb; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: Arial, sans-serif; color: var(--ink); background: var(--bg); cursor: default; }
    .wrap { width: min(1040px, calc(100% - 32px)); margin: 0 auto; }
    .top { background: #fff; border-bottom: 1px solid var(--line); }
    .top .wrap { min-height: 68px; display:flex; align-items:center; justify-content:space-between; gap:16px; }
    .brand { font-weight: 800; color: var(--ink); text-decoration:none; }
    .hero { padding: 42px 0 26px; }
    .kicker { color: var(--brand); font-weight: 700; text-transform: uppercase; font-size: 12px; letter-spacing: .08em; }
    h1 { margin: 10px 0; font-size: clamp(28px, 5vw, 44px); line-height: 1.05; }
    .lead, .muted { color: var(--muted); }
    .grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap:16px; margin-bottom: 40px; }
    .card { background:#fff; border:1px solid var(--line); border-radius:8px; padding:20px; box-shadow:0 10px 24px rgba(15,23,42,.06); }
    .card h2 { margin:0 0 8px; font-size:20px; }
    .btn { display:inline-flex; align-items:center; justify-content:center; min-height:40px; padding:0 16px; border-radius:6px; border:1px solid var(--brand); background:var(--brand); color:#fff; text-decoration:none; font-weight:700; cursor:pointer; }
    .btn.secondary { background:#fff; color:var(--brand); }
    .question { margin-bottom:16px; }
    .question h3 { margin:0 0 10px; font-size:18px; }
    label { display:block; margin:8px 0; }
    input[type="text"], input[type="email"] { width:100%; min-height:42px; border:1px solid var(--line); border-radius:6px; padding:8px 10px; cursor:text; }
    .activity-frame { width:100%; aspect-ratio:16/9; border:1px solid var(--line); border-radius:8px; background:#fff; }
    .alert { padding:12px 14px; border-radius:6px; margin-bottom:16px; }
    .alert.ok { background:#e8f8ef; color:#166534; border:1px solid #bbf7d0; }
    .alert.err { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
    .result-item { border-top:1px solid var(--line); padding-top:12px; margin-top:12px; }
    .badge { display:inline-block; border-radius:999px; padding:3px 8px; font-size:12px; font-weight:700; }
    .badge.ok { background:#dcfce7; color:#166534; }
    .badge.err { background:#fee2e2; color:#991b1b; }
    .meta { display:flex; flex-wrap:wrap; gap:8px; margin-top:12px; }
    .pill { border:1px solid var(--line); border-radius:999px; padding:5px 10px; color:var(--muted); font-size:13px; background:#fff; }
    textarea { width:100%; min-height:110px; border:1px solid var(--line); border-radius:6px; padding:10px 12px; font: inherit; cursor:text; }
    .code-box { border:1px solid #1e293b; border-radius:8px; overflow:hidden; background:linear-gradient(180deg, #111827 0%, #0f172a 100%); margin:12px 0; box-shadow:0 16px 34px rgba(15,23,42,.12); }
    .code-box__header { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 16px; background:#111827; color:#cbd5e1; border-bottom:1px solid rgba(148,163,184,.18); cursor:default; }
    .code-pill { border-radius:999px; padding:4px 10px; font-size:12px; font-weight:700; background:rgba(59,130,246,.18); color:#bfdbfe; }
    .code-box__body { padding:12px 0; }
    .code-line { display:grid; grid-template-columns:48px 1fr; gap:12px; padding:0 16px; font-family:Consolas, Monaco, monospace; font-size:14px; line-height:1.7; color:#e2e8f0; white-space:pre-wrap; }
    .code-line__number { color:#64748b; text-align:right; user-select:none; }
    .code-line__text { overflow-wrap:anywhere; }
    .activity-code__token--kw { color:#c792ea; font-weight:600; }
    .activity-code__token--fn { color:#82aaff; }
    .activity-code__token--var { color:#ffcb6b; }
    .activity-code__token--num { color:#f78c6c; }
    .activity-code__token--tag { color:#7fdbca; }
    .activity-code__token--attr { color:#c3e88d; }
    .activity-code__token--string { color:#ecc48d; }
    :root { --brand:#59249b; --accent:#ff914d; --brand-soft:rgba(89,36,155,.09); }
    body { background:radial-gradient(circle at top left, rgba(89,36,155,.12), transparent 28%), linear-gradient(180deg,#f8f7fc 0%,#f1f3f8 100%); }
    .top { border-bottom:0; background:linear-gradient(115deg,#1e1b4b 0%,#59249b 65%,#6d28d9 100%); box-shadow:0 5px 18px rgba(31,24,78,.14); }
    .brand { color:#fff; }
    .top .btn.secondary { border-color:rgba(255,255,255,.35); color:#fff; background:rgba(255,255,255,.1); }
    .top .btn.secondary:hover { background:rgba(255,255,255,.18); }
    .hero { padding:34px 0 20px; }
    .hero .kicker { color:var(--brand); }
    .card { border-color:rgba(15,23,42,.08); border-radius:18px; box-shadow:0 12px 28px rgba(15,23,42,.07); }
    .btn { min-height:42px; border-radius:12px; background:var(--brand); border-color:var(--brand); }
    .btn:hover { background:#6f33c5; border-color:#6f33c5; }
    .public-activity-flow { padding:0; overflow:visible; }
    .public-activity-flow__visitor { padding:20px 22px 4px; }
    .public-activity-flow__visitor h2 { margin:0 0 6px; font-size:17px; }
    .public-activity-flow__visitor-fields { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; margin-top:12px; }
    .public-activity-flow__layout { display:grid; grid-template-columns:minmax(190px,230px) minmax(0,1fr); gap:18px; padding:22px; }
    .public-activity-flow__rail { position:sticky; top:16px; padding:16px; border:1px solid rgba(89,36,155,.12); border-radius:16px; background:linear-gradient(160deg,var(--brand-soft),#fff 38%); align-self:start; }
    .public-activity-flow__eyebrow { color:var(--brand); font-size:11px; font-weight:800; letter-spacing:.07em; text-transform:uppercase; }
    .public-activity-flow__rail h2 { margin:4px 0 0; font-size:16px; }
    .public-activity-flow__progress { height:7px; margin:14px 0 7px; overflow:hidden; border-radius:999px; background:#e5e8f0; }
    .public-activity-flow__progress span { display:block; width:0; height:100%; border-radius:inherit; background:linear-gradient(90deg,var(--brand),#8b5cf6); transition:width .2s ease; }
    .public-activity-flow__progress-label { color:var(--muted); font-size:12px; }
    .public-activity-flow__nav { display:grid; gap:5px; margin-top:15px; }
    .public-activity-flow__nav a { display:flex; align-items:center; gap:9px; padding:9px; border:1px solid transparent; border-radius:11px; color:var(--muted); font-size:13px; font-weight:700; text-decoration:none; }
    .public-activity-flow__nav a:hover { color:var(--brand); background:var(--brand-soft); }
    .public-activity-flow__nav-number { display:grid; width:25px; height:25px; place-items:center; border-radius:8px; color:var(--brand); background:var(--brand-soft); font-size:11px; }
    .public-activity-flow__nav a.is-active { color:var(--brand); border-color:rgba(89,36,155,.16); background:#fff; box-shadow:0 8px 17px rgba(89,36,155,.08); }
    .public-activity-flow__nav a.is-answered .public-activity-flow__nav-number { color:#fff; background:#198754; }
    .public-activity-flow__question { margin:0; padding:0; border:0; box-shadow:none; }
    .public-activity-flow__question h3 { margin:7px 0; font-size:21px; line-height:1.35; }
    .public-activity-flow__hint { display:flex; gap:8px; margin:15px 0; padding:11px 13px; border-radius:12px; color:#75450b; background:#fff5e9; font-size:14px; }
    .public-activity-flow__options { display:grid; gap:9px; margin-top:18px; }
    .public-activity-flow__option { display:flex; align-items:center; gap:11px; min-height:57px; margin:0; padding:10px 14px; border:1px solid var(--line); border-radius:14px; color:var(--ink); background:#fff; font-weight:700; cursor:pointer; transition:border-color .18s ease,background .18s ease,box-shadow .18s ease; }
    .public-activity-flow__option.is-selected, .public-activity-flow__option:has(input:checked) { border-color:var(--brand); color:var(--brand); background:var(--brand-soft); box-shadow:0 8px 17px rgba(89,36,155,.08); }
    .public-activity-flow__option input { width:17px; height:17px; margin:0; accent-color:var(--brand); }
    .public-activity-flow__letter { display:grid; width:29px; height:29px; flex:0 0 29px; place-items:center; border-radius:9px; color:var(--brand); background:var(--brand-soft); font-size:12px; }
    .public-activity-flow__option.is-selected .public-activity-flow__letter, .public-activity-flow__option:has(input:checked) .public-activity-flow__letter { color:#fff; background:var(--brand); }
    .public-activity-flow__answer { margin-top:18px; }
    .public-dnd { display:grid; gap:12px; margin-top:16px; } .public-dnd__cards { display:grid; gap:8px; } .public-dnd__cards[data-dnd-palette] { justify-items:start; } .public-dnd__cards--horizontal { display:flex; flex-wrap:wrap; align-items:flex-start; } .public-dnd__pair { display:grid; grid-template-columns:1fr 1fr; gap:8px; align-items:center; } .public-dnd__order-slots { display:grid; gap:8px; } .public-dnd__order-slot { display:grid; grid-template-columns:36px 1fr; gap:8px; align-items:center; } .public-dnd__order-number { width:32px; height:32px; display:grid; place-items:center; border-radius:50%; background:#eee8fb; color:var(--brand); font-weight:800; } .public-dnd-card { display:inline-grid; width:fit-content; max-width:100%; gap:8px; padding:12px; border:1px solid var(--line); border-radius:12px; background:#fff; cursor:grab; touch-action:none; } .public-dnd-card img { width:100%; max-height:130px; object-fit:cover; border-radius:8px; } .public-dnd-card audio { width:100%; } .public-dnd-zone { min-height:68px; padding:8px; border:2px dashed #b6a4d5; border-radius:12px; background:#fbf9ff; } .public-dnd-zone--inline { display:inline-flex; min-width:120px; min-height:42px; margin:0 4px; vertical-align:middle; align-items:center; justify-content:center; } .public-dnd__cloze { font-size:1.22rem; line-height:2.45; } .public-dnd-card.is-dragging { opacity:.45; }
    .public-activity-flow__answer textarea, .public-activity-flow__answer input { border-radius:12px; }
    .public-activity-flow__actions { display:none; align-items:center; justify-content:space-between; gap:12px; margin-top:21px; padding-top:16px; border-top:1px solid #edf0f5; }
    .public-activity-flow__actions .btn + .btn { margin-left:8px; }
    .public-activity-flow__actions .btn.secondary { color:var(--brand); border-color:rgba(89,36,155,.18); background:#fff; }
    .public-activity-flow__validation { margin-top:14px; color:#b42318; font-size:13px; font-weight:700; }
    .public-activity-flow__submit-fallback { margin-top:18px; }
    .public-activity-flow.is-ready .public-activity-flow__question { display:none; }
    .public-activity-flow.is-ready .public-activity-flow__question.is-active { display:block; }
    .public-activity-flow.is-ready .public-activity-flow__question.is-active .public-activity-flow__actions { display:flex; }
    .public-activity-flow.is-ready .public-activity-flow__submit-fallback { display:none; }
    @media (max-width:720px) {
      .public-activity-flow__visitor-fields, .public-activity-flow__layout { grid-template-columns:1fr; }
      .public-activity-flow__layout { padding:16px; }
      .public-activity-flow__rail { position:static; }
      .public-activity-flow__nav { display:flex; flex-wrap:wrap; }
      .public-activity-flow__nav a { width:38px; height:38px; justify-content:center; padding:0; }
      .public-activity-flow__nav a > span:last-child { position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0,0,0,0); }
    }
  </style>
</head>
<body>
  <header class="top">
    <div class="wrap">
      <a class="brand" href="https://mentemotion.com">MenteMotion</a>
      <a class="btn secondary" href="index.php?r=login">Ingresar al campus</a>
    </div>
  </header>

  <main class="wrap">
    <?php if ($mensajeOk !== ''): ?><div class="alert ok"><?php echo $e($mensajeOk); ?></div><?php endif; ?>
    <?php if ($mensajeError !== ''): ?><div class="alert err"><?php echo $e($mensajeError); ?></div><?php endif; ?>

    <?php if (!$actividad && $slug !== ''): ?>
      <section class="hero">
        <span class="kicker">Actividad</span>
        <h1>No encontramos esta actividad</h1>
        <p class="lead">Puede estar en borrador, ser privada o tener un enlace incorrecto.</p>
        <a class="btn" href="index.php?r=actividad-publica">Ver actividades publicas</a>
      </section>
    <?php elseif (!$actividad): ?>
      <section class="hero">
        <span class="kicker">Actividades publicas</span>
        <h1>Practicas abiertas de MenteMotion</h1>
        <p class="lead">Actividades disponibles para visitantes desde la web publica.</p>
      </section>
      <section class="grid">
        <?php foreach ($actividadesPublicas as $item): ?>
          <article class="card">
            <h2><?php echo $e($item['tituloActividad']); ?></h2>
            <p class="muted"><?php echo $e($item['descripcionActividad'] ?? ''); ?></p>
            <div class="meta">
              <span class="pill"><?php echo $e($item['nombreCurso'] ?? 'Publica'); ?></span>
              <span class="pill"><?php echo $e($tipoLabels[$item['tipoActividad']] ?? $item['tipoActividad']); ?></span>
            </div>
            <p><a class="btn" href="index.php?r=actividad-publica&slug=<?php echo rawurlencode((string) $item['slug']); ?>">Abrir actividad</a></p>
          </article>
        <?php endforeach; ?>
        <?php if (empty($actividadesPublicas)): ?>
          <article class="card">
            <h2>Todavia no hay actividades publicas</h2>
            <p class="muted">Cuando se publique la primera actividad aparecera en esta pagina.</p>
          </article>
        <?php endif; ?>
      </section>
    <?php else: ?>
      <?php
        $puedeVer = ControladorActividades::puedeResolverActividad($actividad, true);
        $preguntas = $puedeVer ? ControladorActividades::crtPreguntasActividad((int) $actividad['idActividad']) : [];
        $esExterna = ($actividad['tipoActividad'] ?? '') === 'externa';
        $resultadoIntento = $_SESSION['actividad_resultado_' . (int) $actividad['idActividad']] ?? null;
        unset($_SESSION['actividad_resultado_' . (int) $actividad['idActividad']]);
        $idUsuarioActual = (int) ($_SESSION['usuario']['id'] ?? 0);
        $intentosPermitidos = (int) ($actividad['intentosPermitidos'] ?? 1);
        $intentosUsados = $idUsuarioActual > 0 ? ControladorActividades::crtIntentosUsadosUsuario((int) $actividad['idActividad'], $idUsuarioActual) : 0;
        $intentosAgotados = ControladorActividades::intentosAgotados($actividad, $idUsuarioActual);
        $urlExternaSegura = SeguridadHtml::urlHttpSegura($actividad['recursoExternoUrl'] ?? '', false);
      ?>
      <section class="hero">
        <span class="kicker"><?php echo $e($actividad['nombreCurso'] ?? 'Actividad publica'); ?></span>
        <h1><?php echo $e($actividad['tituloActividad']); ?></h1>
        <p class="lead"><?php echo $e($actividad['descripcionActividad'] ?? ''); ?></p>
        <div class="meta">
          <span class="pill"><?php echo $e($tipoLabels[$actividad['tipoActividad']] ?? $actividad['tipoActividad']); ?></span>
          <span class="pill"><?php echo (float) ($actividad['puntajeMaximo'] ?? 0); ?> puntos</span>
          <?php if ($intentosPermitidos > 0): ?>
            <span class="pill">Intentos <?php echo (int) $intentosPermitidos; ?></span>
          <?php endif; ?>
        </div>
      </section>

      <?php if (!$puedeVer): ?>
        <article class="card">
          <h2>Actividad no disponible publicamente</h2>
          <p class="muted">Esta actividad es privada del curso o esta en borrador.</p>
        </article>
      <?php elseif ($esExterna): ?>
        <article class="card">
          <?php echo $renderIframe($actividad['recursoExternoEmbed'] ?? '', $actividad['recursoExternoUrl'] ?? ''); ?>
          <?php if ($urlExternaSegura !== ''): ?>
            <p><a class="btn" href="<?php echo $e($urlExternaSegura); ?>" target="_blank" rel="noopener noreferrer">Abrir en nueva pestana</a></p>
          <?php endif; ?>
        </article>
      <?php else: ?>
        <?php if (is_array($resultadoIntento)): ?>
          <article class="card">
            <h2>Resultado del intento</h2>
            <p>Puntaje: <strong><?php echo (float) ($resultadoIntento['puntaje'] ?? 0); ?> / <?php echo (float) ($resultadoIntento['puntajeMaximo'] ?? 0); ?></strong></p>
            <?php foreach (($resultadoIntento['detalle'] ?? []) as $indexResultado => $itemResultado): ?>
              <div class="result-item">
                <strong><?php echo (int) ($indexResultado + 1); ?>. <?php echo $e($itemResultado['pregunta'] ?? ''); ?></strong>
                <?php if (!empty($itemResultado['correcta'])): ?>
                  <span class="badge ok">Correcta</span>
                <?php else: ?>
                  <span class="badge err">Incorrecta</span>
                <?php endif; ?>
                <?php if (isset($itemResultado['aciertos']) && $itemResultado['aciertos'] !== null): ?>
                  <p class="muted"><?php echo (int) $itemResultado['aciertos']; ?> de <?php echo (int) ($itemResultado['totalItems'] ?? 0); ?> coincidencias correctas · <?php echo (int) ($itemResultado['errores'] ?? 0); ?> por revisar · Puntaje: <?php echo (float) ($itemResultado['puntajeObtenido'] ?? 0); ?></p>
                <?php endif; ?>
                <?php if (!empty($itemResultado['explicacionError'])): ?>
                  <p class="muted"><?php echo $e($itemResultado['explicacionError']); ?></p>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </article>
        <?php endif; ?>

        <?php if ($intentosAgotados): ?>
          <article class="card">
            <h2>Intentos agotados</h2>
            <p class="muted">Ya usaste los intentos permitidos para esta actividad.</p>
          </article>
        <?php else: ?>
        <?php $totalPreguntas = count($preguntas); ?>
        <form method="post" class="card public-activity-flow" data-public-activity-flow>
          <input type="hidden" name="accion_actividad" value="responder_actividad">
          <input type="hidden" name="idActividad" value="<?php echo (int) $actividad['idActividad']; ?>">
          <?php if (empty($_SESSION['logueado'])): ?>
            <div class="public-activity-flow__visitor">
              <h3>Tus datos</h3>
              <p class="muted">Podés completarlos para identificar tu intento.</p>
              <div class="public-activity-flow__visitor-fields">
              <label>Nombre
                <input type="text" name="nombreVisitante" placeholder="Opcional">
              </label>
              <label>Email
                <input type="email" name="emailVisitante" placeholder="Opcional">
              </label>
              </div>
            </div>
          <?php endif; ?>

          <div class="public-activity-flow__layout">
            <aside class="public-activity-flow__rail" aria-label="Preguntas de la actividad">
              <div class="public-activity-flow__eyebrow">Tu recorrido</div>
              <h2>Resolver actividad</h2>
              <div class="public-activity-flow__progress" role="progressbar" aria-label="Progreso de preguntas" aria-valuemin="0" aria-valuemax="<?php echo (int) $totalPreguntas; ?>" aria-valuenow="0"><span data-public-activity-progress></span></div>
              <div class="public-activity-flow__progress-label" data-public-activity-progress-label>Pregunta 1 de <?php echo (int) $totalPreguntas; ?></div>
              <nav class="public-activity-flow__nav" aria-label="Ir a una pregunta">
                <?php foreach ($preguntas as $indiceNavegacion => $preguntaNavegacion): ?>
                  <a class="<?php echo $indiceNavegacion === 0 ? 'is-active' : ''; ?>" href="#public-activity-question-<?php echo (int) $preguntaNavegacion['idPregunta']; ?>" data-public-activity-nav="<?php echo (int) $indiceNavegacion; ?>"><span class="public-activity-flow__nav-number"><?php echo (int) ($indiceNavegacion + 1); ?></span><span>Pregunta <?php echo (int) ($indiceNavegacion + 1); ?></span></a>
                <?php endforeach; ?>
              </nav>
            </aside>
            <div class="public-activity-flow__questions">
          <?php foreach ($preguntas as $index => $pregunta): ?>
            <section id="public-activity-question-<?php echo (int) $pregunta['idPregunta']; ?>" class="question public-activity-flow__question<?php echo $index === 0 ? ' is-active' : ''; ?>" data-public-activity-question="<?php echo (int) $index; ?>">
              <div class="public-activity-flow__eyebrow">Pregunta <?php echo (int) ($index + 1); ?> de <?php echo (int) $totalPreguntas; ?></div>
              <?php if (($pregunta['tipoPregunta'] ?? '') !== 'arrastrar_completar'): ?><h3><?php echo $e($pregunta['textoPregunta']); ?></h3><?php endif; ?>
              <?php if (!empty($pregunta['pista'])): ?><div class="public-activity-flow__hint"><span>✦</span><span><strong>Pista:</strong> <?php echo $e($pregunta['pista']); ?></span></div><?php endif; ?>
              <?php if (($pregunta['tipoPregunta'] ?? '') === 'codigo' && !empty($pregunta['codigoBase'])): ?>
                <?php echo $renderCodigo($pregunta['codigoBase'], (string) ($pregunta['lenguajeCodigo'] ?? 'plaintext')); ?>
              <?php endif; ?>
              <?php if ($esArrastre($pregunta['tipoPregunta'] ?? '')): ?>
                <?php $tipoArrastre=(string)$pregunta['tipoPregunta'];$opcionesArrastre=$pregunta['opciones']??[]; ?>
                <div class="public-dnd" data-dnd-board data-dnd-mode="<?php echo $e($tipoArrastre); ?>"><input type="hidden" name="respuesta[<?php echo (int)$pregunta['idPregunta']; ?>]" data-dnd-answer>
                <?php if($tipoArrastre==='arrastrar_pares'): ?>
                  <?php $origenes=array_values(array_filter($opcionesArrastre,static function($o){return ($o['tipoArrastre']??'')==='origen';}));$destinos=array_values(array_filter($opcionesArrastre,static function($o){return ($o['tipoArrastre']??'')==='destino';}));shuffle($origenes); ?><p class="muted">Arrastrá cada tarjeta a su par.</p><div class="public-dnd__cards" data-dnd-palette><?php foreach($origenes as $o):?><div class="public-dnd-card" data-dnd-card data-dnd-id="<?php echo (int)$o['idOpcion'];?>"><?php echo $renderTarjetaArrastre($o);?></div><?php endforeach;?></div><div class="public-dnd__cards"><?php foreach($destinos as $d):?><div class="public-dnd__pair"><div class="public-dnd-zone" data-dnd-zone data-dnd-target="<?php echo (int)$d['idOpcion'];?>"></div><div class="public-dnd-card"><?php echo $renderTarjetaArrastre($d);?></div></div><?php endforeach;?></div>
                <?php elseif($tipoArrastre==='arrastrar_orden'): ?>
                  <?php $barajadasOrden=$opcionesArrastre;shuffle($barajadasOrden); ?><p class="muted">Arrastrá cada tarjeta a la posición correcta.</p><div class="public-dnd__cards public-dnd__cards--horizontal" data-dnd-palette><?php foreach($barajadasOrden as $o):?><div class="public-dnd-card" data-dnd-card data-dnd-id="<?php echo (int)$o['idOpcion'];?>"><?php echo $renderTarjetaArrastre($o);?></div><?php endforeach;?></div><div class="public-dnd__order-slots"><?php foreach($opcionesArrastre as $posicion=>$o):?><div class="public-dnd__order-slot"><span class="public-dnd__order-number"><?php echo (int)($posicion+1);?></span><div class="public-dnd-zone" data-dnd-zone data-dnd-order-slot><span class="muted">Soltá el paso <?php echo (int)($posicion+1);?> aquí</span></div></div><?php endforeach;?></div>
                <?php else: ?>
                  <?php shuffle($opcionesArrastre);$tieneEspacios=preg_match('/\[\[[^\]\r\n]+\]\]|_{2,}/u',(string)$pregunta['textoPregunta']); ?><p class="muted">Arrastrá cada respuesta al espacio que le corresponde.</p><div class="public-dnd__cards" data-dnd-palette><?php foreach($opcionesArrastre as $o):?><div class="public-dnd-card" data-dnd-card data-dnd-id="<?php echo (int)$o['idOpcion'];?>"><?php echo $renderTarjetaArrastre($o);?></div><?php endforeach;?></div><div class="public-dnd__cloze"><?php echo $renderEnunciadoCompletar($pregunta['textoPregunta']); ?></div><?php if(!$tieneEspacios): ?><div class="public-dnd-zone" data-dnd-zone data-dnd-single></div><?php endif; ?>
                <?php endif;?></div>
              <?php elseif (($pregunta['tipoPregunta'] ?? '') === 'multiple_choice'): ?>
                <div class="public-activity-flow__options">
                <?php foreach ($pregunta['opciones'] as $indiceOpcion => $opcion): ?>
                  <label class="public-activity-flow__option">
                    <input type="radio" name="respuesta[<?php echo (int) $pregunta['idPregunta']; ?>]" value="<?php echo (int) $opcion['idOpcion']; ?>" required>
                    <span class="public-activity-flow__letter"><?php echo chr(65 + $indiceOpcion); ?></span><span><?php echo $e($opcion['textoOpcion']); ?></span>
                  </label>
                <?php endforeach; ?>
                </div>
              <?php elseif (($pregunta['tipoPregunta'] ?? '') === 'completar' && !empty($pregunta['opciones'])): ?>
                <?php
                  $opcionesCompletar = $pregunta['opciones'];
                  shuffle($opcionesCompletar);
                ?>
                <div class="public-activity-flow__hint"><span>✦</span><span>Elegi la palabra que completa el espacio.</span></div>
                <div class="public-activity-flow__options">
                <?php foreach ($opcionesCompletar as $indiceOpcion => $opcion): ?>
                  <label class="public-activity-flow__option">
                    <input type="radio" name="respuesta[<?php echo (int) $pregunta['idPregunta']; ?>]" value="<?php echo (int) $opcion['idOpcion']; ?>" required>
                    <span class="public-activity-flow__letter"><?php echo chr(65 + $indiceOpcion); ?></span><span><?php echo $e($opcion['textoOpcion']); ?></span>
                  </label>
                <?php endforeach; ?>
                </div>
              <?php elseif (($pregunta['tipoPregunta'] ?? '') === 'verdadero_falso'): ?>
                <div class="public-activity-flow__options">
                  <label class="public-activity-flow__option"><input type="radio" name="respuesta[<?php echo (int) $pregunta['idPregunta']; ?>]" value="verdadero" required><span class="public-activity-flow__letter">V</span><span>Verdadero</span></label>
                  <label class="public-activity-flow__option"><input type="radio" name="respuesta[<?php echo (int) $pregunta['idPregunta']; ?>]" value="falso" required><span class="public-activity-flow__letter">F</span><span>Falso</span></label>
                </div>
              <?php elseif (($pregunta['tipoPregunta'] ?? '') === 'codigo'): ?>
                <div class="public-activity-flow__answer"><textarea name="respuesta[<?php echo (int) $pregunta['idPregunta']; ?>]" rows="3" placeholder="Describe el error o escribe la correccion esperada." required></textarea></div>
              <?php else: ?>
                <div class="public-activity-flow__answer"><input type="text" name="respuesta[<?php echo (int) $pregunta['idPregunta']; ?>]" required></div>
              <?php endif; ?>
              <div class="public-activity-flow__validation" data-public-activity-validation role="alert" hidden>Respondé esta pregunta antes de entregar la actividad.</div>
              <div class="public-activity-flow__actions">
                <button class="btn secondary" type="button" data-public-activity-previous>← Anterior</button>
                <div><button class="btn" type="button" data-public-activity-next>Siguiente →</button><button class="btn" type="submit" data-public-activity-submit>Entregar actividad</button></div>
              </div>
            </section>
          <?php endforeach; ?>
            <div class="public-activity-flow__submit-fallback"><button class="btn" type="submit">Entregar actividad</button></div>
            </div>
          </div>
        </form>
        <?php endif; ?>
      <?php endif; ?>
    <?php endif; ?>
  </main>
  <script>
  document.addEventListener('DOMContentLoaded', function () {
    var flow = document.querySelector('[data-public-activity-flow]');
    if (!flow) {
      return;
    }

    var questions = Array.prototype.slice.call(flow.querySelectorAll('[data-public-activity-question]'));
    var navigation = Array.prototype.slice.call(flow.querySelectorAll('[data-public-activity-nav]'));
    var progress = flow.querySelector('[data-public-activity-progress]');
    var progressLabel = flow.querySelector('[data-public-activity-progress-label]');
    var progressContainer = flow.querySelector('.public-activity-flow__progress');
    var total = questions.length;
    var activeIndex = 0;
    if (total === 0) {
      return;
    }

    flow.classList.add('is-ready');
    flow.noValidate = true;

    function questionHasAnswer(question) {
      var dnd = question.querySelector('[data-dnd-answer]');
      if (dnd) { return dnd.value.trim() !== ''; }
      if (question.querySelector('input[type="radio"]:checked')) {
        return true;
      }
      var field = question.querySelector('textarea[name^="respuesta["] , input[type="text"][name^="respuesta["]');
      return !!field && field.value.trim() !== '';
    }

    flow.querySelectorAll('[data-dnd-board]').forEach(function (board) {
      var dragged = null;
      function actualizar() { var answer=board.querySelector('[data-dnd-answer]');var mode=board.getAttribute('data-dnd-mode');if(mode==='arrastrar_pares'){var pares={};board.querySelectorAll('[data-dnd-zone][data-dnd-target]').forEach(function(z){var c=z.querySelector('[data-dnd-card]');if(c){pares[c.getAttribute('data-dnd-id')]=z.getAttribute('data-dnd-target');}});answer.value=Object.keys(pares).length?JSON.stringify(pares):'';}else if(mode==='arrastrar_orden'){var slots=board.querySelectorAll('[data-dnd-order-slot]');if(slots.length){var orden=[];var completo=false;slots.forEach(function(z){var c=z.querySelector('[data-dnd-card]');orden.push(c?c.getAttribute('data-dnd-id'):0);completo=completo||!!c;});answer.value=completo?JSON.stringify(orden):'';}else{var orden=Array.prototype.map.call(board.querySelectorAll('[data-dnd-order] [data-dnd-card]'),function(c){return c.getAttribute('data-dnd-id');});answer.value=orden.length?JSON.stringify(orden):'';}}else{var espacios={};board.querySelectorAll('[data-dnd-blank]').forEach(function(z){var c=z.querySelector('[data-dnd-card]');if(c){espacios[z.getAttribute('data-dnd-blank')]=c.getAttribute('data-dnd-id');}});if(Object.keys(espacios).length){answer.value=JSON.stringify(espacios);}else{var c=board.querySelector('[data-dnd-single] [data-dnd-card]');answer.value=c?JSON.stringify(c.getAttribute('data-dnd-id')):'';}}answer.dispatchEvent(new Event('change',{bubbles:true})); }
      board.addEventListener('pointerdown',function(event){var card=event.target.closest('[data-dnd-card]');if(!card||event.target.closest('audio')){return;}dragged=card;card.classList.add('is-dragging');event.preventDefault();});
      document.addEventListener('pointerup',function(event){if(!dragged||!board.contains(dragged)){return;}var target=document.elementFromPoint(event.clientX,event.clientY);var zone=target&&target.closest('[data-dnd-zone]');if(zone&&board.contains(zone)){var mode=board.getAttribute('data-dnd-mode');if(mode==='arrastrar_pares'||mode==='arrastrar_completar'||(mode==='arrastrar_orden'&&zone.hasAttribute('data-dnd-order-slot'))){var old=zone.querySelector('[data-dnd-card]');if(old&&old!==dragged){var palette=board.querySelector('[data-dnd-palette]');if(palette){palette.appendChild(old);}}zone.appendChild(dragged);}else{var ref=target.closest('[data-dnd-card]');if(ref&&ref!==dragged){zone.insertBefore(dragged,ref);}else{zone.appendChild(dragged);}}actualizar();}dragged.classList.remove('is-dragging');dragged=null;});
    });

    function updateAnsweredState() {
      questions.forEach(function (question, index) {
        question.querySelectorAll('.public-activity-flow__option').forEach(function (option) {
          option.classList.toggle('is-selected', option.querySelector('input').checked);
        });
        if (navigation[index]) {
          navigation[index].classList.toggle('is-answered', questionHasAnswer(question));
        }
      });
    }

    function showQuestion(index) {
      activeIndex = Math.max(0, Math.min(index, total - 1));
      questions.forEach(function (question, questionIndex) {
        question.classList.toggle('is-active', questionIndex === activeIndex);
        var validation = question.querySelector('[data-public-activity-validation]');
        if (validation) {
          validation.hidden = true;
        }
      });
      navigation.forEach(function (item, navigationIndex) {
        item.classList.toggle('is-active', navigationIndex === activeIndex);
        item.setAttribute('aria-current', navigationIndex === activeIndex ? 'step' : 'false');
      });

      var current = questions[activeIndex];
      current.querySelector('[data-public-activity-previous]').hidden = activeIndex === 0;
      current.querySelector('[data-public-activity-next]').hidden = activeIndex === total - 1;
      current.querySelector('[data-public-activity-submit]').hidden = activeIndex !== total - 1;

      var completed = activeIndex + 1;
      progress.style.width = ((completed / total) * 100) + '%';
      progressLabel.textContent = 'Pregunta ' + completed + ' de ' + total;
      progressContainer.setAttribute('aria-valuenow', String(completed));
    }

    navigation.forEach(function (item) {
      item.addEventListener('click', function (event) {
        event.preventDefault();
        showQuestion(Number(item.getAttribute('data-public-activity-nav')));
      });
    });

    questions.forEach(function (question, index) {
      question.querySelector('[data-public-activity-previous]').addEventListener('click', function () {
        showQuestion(index - 1);
      });
      question.querySelector('[data-public-activity-next]').addEventListener('click', function () {
        showQuestion(index + 1);
      });
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
      var validation = questions[unansweredIndex].querySelector('[data-public-activity-validation]');
      if (validation) {
        validation.hidden = false;
      }
    });

    updateAnsweredState();
    showQuestion(0);
  });
  </script>
</body>
</html>
