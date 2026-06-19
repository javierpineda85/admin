<?php
$actividad = $actividad ?? [];
$esBanco = !empty($esBanco);
$puedeCrear = !empty($puedeCrear);
$e = $e ?? static function ($valor) {
  return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
};
$tipoLabels = $tipoLabels ?? ControladorActividades::tiposDisponibles();
$visibilidadLabels = $visibilidadLabels ?? ControladorActividades::visibilidadesDisponibles();
$idUsuarioActual = (int) ($idUsuarioActual ?? ($_SESSION['usuario']['id'] ?? 0));
$publicUrl = 'index.php?r=actividad-publica&slug=' . rawurlencode((string) ($actividad['slug'] ?? ''));
$publicUrlAbsoluta = 'https://mentemotion.com/admin/' . $publicUrl;
$embedPublico = '<iframe src="' . $publicUrlAbsoluta . '" width="100%" height="720" style="border:0;" loading="lazy"></iframe>';
$intentosPermitidos = (int) ($actividad['intentosPermitidos'] ?? 1);
$intentosUsados = $idUsuarioActual > 0
  ? ControladorActividades::crtIntentosUsadosUsuario((int) ($actividad['idActividad'] ?? 0), $idUsuarioActual)
  : 0;
$intentosAgotados = ControladorActividades::intentosAgotados($actividad, $idUsuarioActual);
$estado = (string) ($actividad['estadoActividad'] ?? 'BORRADOR');
$origen = (int) ($actividad['id_actividad_origen'] ?? 0);
$alcancePlantilla = (string) ($actividad['alcancePlantilla'] ?? 'personal');
$destacadaPublica = (int) ($actividad['destacadaPublica'] ?? 0) === 1;
$rutaRetorno = $_SERVER['REQUEST_URI'] ?? ($esBanco ? 'index.php?r=banco-actividades' : 'index.php?r=listado-actividades');
?>
<article class="activity-card">
  <div class="activity-card__top theme-<?php echo (int) (($index ?? 0) % 4); ?>">
    <h3><?php echo $e($actividad['tituloActividad'] ?? 'Actividad'); ?></h3>
    <small><?php echo $e($actividad['nombreCurso'] ?? 'Sin curso'); ?> · <?php echo $e($actividad['tituloSeccion'] ?? 'Sin materia'); ?></small>
  </div>
  <div class="activity-card__body">
    <div class="activity-card__meta">
      <span class="activity-chip activity-chip--tipo"><i class="fas fa-tasks"></i><?php echo $e($tipoLabels[$actividad['tipoActividad'] ?? ''] ?? ($actividad['tipoActividad'] ?? '')); ?></span>
      <?php if ($esBanco): ?>
        <span class="activity-chip"><i class="fas fa-layer-group"></i>Plantilla</span>
        <span class="activity-chip"><i class="fas fa-user-tag"></i><?php echo $e(ucfirst($alcancePlantilla)); ?></span>
      <?php else: ?>
        <span class="activity-chip"><i class="fas fa-eye"></i><?php echo $e($visibilidadLabels[$actividad['visibilidad'] ?? ''] ?? ($actividad['visibilidad'] ?? '')); ?></span>
      <?php endif; ?>
      <span class="activity-chip"><i class="fas fa-star"></i><?php echo (float) ($actividad['puntajeMaximo'] ?? 0); ?> pts</span>
      <?php if ($destacadaPublica): ?>
        <span class="activity-chip activity-chip--destacada"><i class="fas fa-bolt"></i>Destacada</span>
      <?php endif; ?>
    </div>

    <p class="text-muted mb-2"><?php echo $e($actividad['descripcionActividad'] ?? 'Sin descripcion cargada.'); ?></p>

    <div class="d-flex justify-content-between align-items-center">
      <div>
        <span class="badge <?php echo $estado === 'PUBLICADA' ? 'badge-success' : 'badge-secondary'; ?>">
          <?php echo $e($estado); ?>
        </span>
        <?php if ($origen > 0): ?>
          <small class="text-muted d-block mt-1">Origen: #<?php echo $origen; ?></small>
        <?php endif; ?>
      </div>
      <?php if ($puedeCrear): ?>
        <?php if ($esBanco): ?>
          <small class="text-muted">Lista para reutilizar</small>
        <?php else: ?>
          <small class="text-muted">Total intentos: <?php echo (int) ($actividad['totalIntentos'] ?? 0); ?></small>
        <?php endif; ?>
      <?php else: ?>
        <small class="<?php echo $intentosAgotados ? 'text-danger' : 'text-muted'; ?>">
          Intentos: <?php echo (int) $intentosUsados; ?> / <?php echo $intentosPermitidos > 0 ? (int) $intentosPermitidos : 'sin limite'; ?>
        </small>
      <?php endif; ?>
    </div>
  </div>
  <div class="activity-card__footer">
    <small class="text-muted">
      <?php
      if ($esBanco) {
          echo 'Plantilla reutilizable';
      } else {
          echo $intentosPermitidos > 0 ? 'Limite: ' . (int) $intentosPermitidos . ' por estudiante' : 'Intentos sin limite';
      }
      ?>
    </small>
    <div class="activity-actions">
      <a href="index.php?r=ver-actividad&idActividad=<?php echo (int) ($actividad['idActividad'] ?? 0); ?>" class="btn btn-info btn-sm" title="Abrir">
        <i class="far fa-eye"></i>
      </a>

      <?php if ($puedeCrear): ?>
        <div class="dropdown">
          <button type="button" class="btn btn-outline-secondary btn-sm activity-menu-button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Mas acciones">
            <i class="fas fa-ellipsis-v"></i>
          </button>
          <div class="dropdown-menu dropdown-menu-right shadow-sm">
            <?php if ($esBanco): ?>
              <form method="post" class="dropdown-item-form">
                <input type="hidden" name="accion_actividad" value="usar_plantilla">
                <input type="hidden" name="idActividad" value="<?php echo (int) ($actividad['idActividad'] ?? 0); ?>">
                <button type="submit" class="dropdown-item"><i class="fas fa-clone mr-2"></i>Usar plantilla</button>
              </form>
              <form method="post" class="dropdown-item-form">
                <input type="hidden" name="accion_actividad" value="alternar_alcance_plantilla">
                <input type="hidden" name="idActividad" value="<?php echo (int) ($actividad['idActividad'] ?? 0); ?>">
                <button type="submit" class="dropdown-item"><i class="fas fa-exchange-alt mr-2"></i><?php echo $alcancePlantilla === 'institucional' ? 'Pasar a personal' : 'Pasar a institucional'; ?></button>
              </form>
              <form method="post" class="dropdown-item-form">
                <input type="hidden" name="accion_actividad" value="sacar_del_banco">
                <input type="hidden" name="idActividad" value="<?php echo (int) ($actividad['idActividad'] ?? 0); ?>">
                <button type="submit" class="dropdown-item"><i class="fas fa-briefcase mr-2"></i>Mover a trabajo</button>
              </form>
            <?php else: ?>
              <a class="dropdown-item" href="index.php?r=resultados-actividad&idActividad=<?php echo (int) ($actividad['idActividad'] ?? 0); ?>"><i class="fas fa-chart-bar mr-2"></i>Resultados</a>
            <?php endif; ?>

            <a class="dropdown-item" href="index.php?r=editar-actividad&idActividad=<?php echo (int) ($actividad['idActividad'] ?? 0); ?>"><i class="fas fa-edit mr-2"></i>Editar</a>

            <form method="post" class="dropdown-item-form">
              <input type="hidden" name="accion_actividad" value="duplicar_actividad">
              <input type="hidden" name="idActividad" value="<?php echo (int) ($actividad['idActividad'] ?? 0); ?>">
              <button type="submit" class="dropdown-item"><i class="far fa-copy mr-2"></i>Duplicar</button>
            </form>

            <?php if (!$esBanco): ?>
              <form method="post" class="dropdown-item-form">
                <input type="hidden" name="accion_actividad" value="guardar_como_plantilla">
                <input type="hidden" name="idActividad" value="<?php echo (int) ($actividad['idActividad'] ?? 0); ?>">
                <button type="submit" class="dropdown-item"><i class="fas fa-bookmark mr-2"></i>Guardar como plantilla</button>
              </form>
            <?php endif; ?>

            <?php if (!$esBanco && in_array(($actividad['visibilidad'] ?? ''), ['publica', 'oculta'], true)): ?>
              <form method="post" class="dropdown-item-form">
                <input type="hidden" name="accion_actividad" value="alternar_destacada_publica">
                <input type="hidden" name="idActividad" value="<?php echo (int) ($actividad['idActividad'] ?? 0); ?>">
                <input type="hidden" name="ruta_retorno" value="<?php echo $e($rutaRetorno); ?>">
                <button type="submit" class="dropdown-item"><i class="fas fa-bolt mr-2"></i><?php echo $destacadaPublica ? 'Quitar destacado' : 'Marcar destacada'; ?></button>
              </form>
              <a class="dropdown-item" href="<?php echo $e($publicUrl); ?>" target="_blank"><i class="fas fa-external-link-alt mr-2"></i>Abrir publica</a>
              <button type="button" class="dropdown-item activity-copy" data-copy-text="<?php echo $e($publicUrlAbsoluta); ?>"><i class="fas fa-link mr-2"></i>Copiar enlace</button>
              <button type="button" class="dropdown-item activity-copy" data-copy-text="<?php echo $e($embedPublico); ?>"><i class="fas fa-code mr-2"></i>Copiar embed</button>
            <?php endif; ?>

            <div class="dropdown-divider"></div>
            <button type="button" class="dropdown-item text-danger eliminar-actividad" data-toggle="modal" data-target="#eliminarActividadModal" data-id="<?php echo (int) ($actividad['idActividad'] ?? 0); ?>" data-titulo="<?php echo $e($actividad['tituloActividad'] ?? ''); ?>">
              <i class="fas fa-trash mr-2"></i>Eliminar
            </button>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</article>
