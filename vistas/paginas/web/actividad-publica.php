<?php
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

$renderIframe = static function ($embed, $url) use ($e) {
  $src = '';
  if (preg_match('/<iframe[^>]+src=["\']([^"\']+)["\']/i', (string) $embed, $match)) {
    $src = $match[1];
  } elseif (preg_match('/^https?:\/\//i', (string) $url)) {
    $src = (string) $url;
  }

  if ($src === '') {
    return '<p class="muted">No hay recurso externo cargado.</p>';
  }

  return '<iframe class="activity-frame" src="' . $e($src) . '" allowfullscreen loading="lazy"></iframe>';
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
    body { margin: 0; font-family: Arial, sans-serif; color: var(--ink); background: var(--bg); }
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
    input[type="text"], input[type="email"] { width:100%; min-height:42px; border:1px solid var(--line); border-radius:6px; padding:8px 10px; }
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
              <span class="pill"><?php echo $e($item['tipoActividad']); ?></span>
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
      ?>
      <section class="hero">
        <span class="kicker"><?php echo $e($actividad['nombreCurso'] ?? 'Actividad publica'); ?></span>
        <h1><?php echo $e($actividad['tituloActividad']); ?></h1>
        <p class="lead"><?php echo $e($actividad['descripcionActividad'] ?? ''); ?></p>
        <div class="meta">
          <span class="pill"><?php echo $e($actividad['tipoActividad']); ?></span>
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
          <?php if (!empty($actividad['recursoExternoUrl'])): ?>
            <p><a class="btn" href="<?php echo $e($actividad['recursoExternoUrl']); ?>" target="_blank">Abrir en nueva pestana</a></p>
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
                  <?php if (!empty($itemResultado['explicacionError'])): ?>
                    <p class="muted"><?php echo $e($itemResultado['explicacionError']); ?></p>
                  <?php endif; ?>
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
        <form method="post" class="card">
          <input type="hidden" name="accion_actividad" value="responder_actividad">
          <input type="hidden" name="idActividad" value="<?php echo (int) $actividad['idActividad']; ?>">
          <?php if (empty($_SESSION['logueado'])): ?>
            <div class="question">
              <h3>Tus datos</h3>
              <label>Nombre
                <input type="text" name="nombreVisitante" placeholder="Opcional">
              </label>
              <label>Email
                <input type="email" name="emailVisitante" placeholder="Opcional">
              </label>
            </div>
          <?php endif; ?>

          <?php foreach ($preguntas as $index => $pregunta): ?>
            <div class="question">
              <h3><?php echo (int) ($index + 1); ?>. <?php echo $e($pregunta['textoPregunta']); ?></h3>
              <?php if (!empty($pregunta['pista'])): ?><p class="muted">Pista: <?php echo $e($pregunta['pista']); ?></p><?php endif; ?>
              <?php if (($pregunta['tipoPregunta'] ?? '') === 'multiple_choice'): ?>
                <?php foreach ($pregunta['opciones'] as $opcion): ?>
                  <label>
                    <input type="radio" name="respuesta[<?php echo (int) $pregunta['idPregunta']; ?>]" value="<?php echo (int) $opcion['idOpcion']; ?>" required>
                    <?php echo $e($opcion['textoOpcion']); ?>
                  </label>
                <?php endforeach; ?>
              <?php elseif (($pregunta['tipoPregunta'] ?? '') === 'completar' && !empty($pregunta['opciones'])): ?>
                <?php
                  $opcionesCompletar = $pregunta['opciones'];
                  shuffle($opcionesCompletar);
                ?>
                <p class="muted">Elegi la palabra que completa el espacio.</p>
                <?php foreach ($opcionesCompletar as $opcion): ?>
                  <label>
                    <input type="radio" name="respuesta[<?php echo (int) $pregunta['idPregunta']; ?>]" value="<?php echo (int) $opcion['idOpcion']; ?>" required>
                    <?php echo $e($opcion['textoOpcion']); ?>
                  </label>
                <?php endforeach; ?>
              <?php elseif (($pregunta['tipoPregunta'] ?? '') === 'verdadero_falso'): ?>
                <label><input type="radio" name="respuesta[<?php echo (int) $pregunta['idPregunta']; ?>]" value="verdadero" required> Verdadero</label>
                <label><input type="radio" name="respuesta[<?php echo (int) $pregunta['idPregunta']; ?>]" value="falso" required> Falso</label>
              <?php else: ?>
                <input type="text" name="respuesta[<?php echo (int) $pregunta['idPregunta']; ?>]" required>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>

          <button class="btn" type="submit">Entregar actividad</button>
        </form>
        <?php endif; ?>
      <?php endif; ?>
    <?php endif; ?>
  </main>
</body>
</html>
