<?php
// Solo se renderiza desde el controlador, antes del layout académico.
if (!isset($estado, $instituciones) || !class_exists('ControladorInstitucion', false)) {
    http_response_code(404);
    exit;
}
$e = static fn($valor) => htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$titulos = [
    'seleccionar-institucion' => 'Elegí tu institución',
    'sin-acceso-institucional' => 'Tu cuenta no tiene acceso institucional',
    'solicitud-invalida' => 'No se pudo seleccionar la institución',
    'institucion-no-disponible' => 'El acceso institucional no está disponible',
];
$titulo = $titulos[$estado] ?? 'Acceso institucional';
// Logos locales únicamente: no aceptar esquemas, traversal ni URL de terceros.
$logoSeguro = static function ($ruta) {
    return is_string($ruta) && preg_match('~^img/instituciones/[a-zA-Z0-9_-]+\.(png|jpe?g|webp|gif)$~i', $ruta)
        ? $ruta : '';
};
$iniciales = static function ($nombre) {
    $letras = '';
    foreach (array_slice(preg_split('/\s+/u', trim((string) $nombre)) ?: [], 0, 2) as $palabra) {
        $letras .= function_exists('mb_substr') ? mb_substr($palabra, 0, 1, 'UTF-8') : substr($palabra, 0, 1);
    }
    return function_exists('mb_strtoupper') ? mb_strtoupper($letras, 'UTF-8') : strtoupper($letras);
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $e($titulo) ?> · Campus MenteMotion</title>
  <link rel="stylesheet" href="css/adminlte.min.css">
  <link rel="stylesheet" href="css/classroom-theme.css">
  <link rel="stylesheet" href="css/instituciones.css?v=20260914-2">
</head>
<body class="login-page classroom-auth institution-page">
  <main class="institution-shell">
    <a class="institution-brand" href="index.php"><img src="img/logo con texto blanco.png" alt="MenteMotion Campus"></a>
    <section class="card institution-panel" aria-labelledby="institution-title">
      <div class="card-body">
        <p class="institution-eyebrow">CAMPUS · MENTEMOTION</p>
        <h1 id="institution-title"><?= $e($titulo) ?></h1>
        <?php if ($estado === 'seleccionar-institucion'): ?>
          <p class="text-muted">Ingresá al espacio donde querés estudiar, enseñar o administrar.</p>
          <div class="institution-grid">
          <?php foreach ($instituciones as $item): $logo = $logoSeguro($item['logo']); ?>
            <article class="institution-card">
              <?php if ($logo !== ''): ?>
                <img class="institution-logo" src="<?= $e($logo) ?>" alt="Logo de <?= $e($item['nombre']) ?>">
              <?php else: ?>
                <div class="institution-logo institution-placeholder" aria-hidden="true"><?= $e($iniciales($item['nombre'])) ?></div>
              <?php endif; ?>
              <h2><?= $e($item['nombre']) ?></h2>
              <p class="institution-roles"><?= $e($item['roles'] ? implode(' · ', $item['roles']) : 'Sin roles académicos asignados') ?></p>
              <form action="index.php?r=seleccionar-institucion" method="post">
                <input type="hidden" name="institucion_csrf" value="<?= $e(ControladorInstitucion::csrf()) ?>">
                <input type="hidden" name="institucion_version" value="<?= $e(ControladorInstitucion::version()) ?>">
                <input type="hidden" name="id_institucion" value="<?= (int) $item['idInstitucion'] ?>">
                <button type="submit" class="btn auth-cta text-white btn-block" aria-label="Ingresar a <?= $e($item['nombre']) ?>">Ingresar</button>
              </form>
            </article>
          <?php endforeach; ?>
          </div>
        <?php elseif ($estado === 'sin-acceso-institucional'): ?>
          <p>Tu identidad fue verificada, pero no tenés una membresía activa en una institución disponible. Contactá a la administración de tu institución para solicitar acceso.</p>
        <?php elseif ($estado === 'solicitud-invalida'): ?>
          <p>La solicitud venció o ya no tenés acceso a esa institución. Volvé a la selección para actualizar las opciones.</p>
          <a class="btn btn-outline-primary" href="index.php?r=seleccionar-institucion">Volver a la selección</a>
        <?php else: ?>
          <p>No pudimos validar el acceso en este momento. Intentá nuevamente más tarde.</p>
        <?php endif; ?>
        <div class="institution-footer"><a href="index.php?r=logout">Cerrar sesión</a></div>
      </div>
    </section>
  </main>
</body>
</html>
