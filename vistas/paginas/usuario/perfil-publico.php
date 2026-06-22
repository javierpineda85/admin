<?php
$idUsuarioPerfil = (int) ($_GET['idUsuario'] ?? 0);
$idSeccion = (int) ($_GET['idSeccion'] ?? 0);
$puedeVerPerfil = ControladorUsuarios::crtPuedeVerPerfilEnSeccion($idUsuarioPerfil, $idSeccion);
$seccion = $puedeVerPerfil ? ControladorLecciones::crtBuscarSeccionPorId($idSeccion) : null;
$usuario = $puedeVerPerfil ? ControladorUsuarios::crtUsuarioCompleto($idUsuarioPerfil) : null;
$idUsuarioActual = (int) ($_SESSION['usuario']['id'] ?? 0);
$e = static function ($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
};

if (!$usuario || !$seccion):
?>
  <section class="content page-fade">
    <div class="container-fluid">
      <div class="empty-state">
        <i class="fas fa-user-lock"></i>
        <h4>Perfil no disponible</h4>
        <p class="mb-3">Solo podés ver perfiles de personas que comparten esta materia con vos.</p>
        <a href="index.php?r=listado-cursos" class="btn btn-primary">Volver a mis cursos</a>
      </div>
    </div>
  </section>
<?php
  return;
endif;

$nombreCompleto = trim((string) ($usuario['nombreUsuario'] ?? '') . ' ' . (string) ($usuario['apellidoUsuario'] ?? ''));
$imagenUsuario = ControladorUsuarios::rutaImagenUsuario($usuario['imgUsuario'] ?? '', 'user2-160x160.jpg');
$sobreMi = trim((string) ($usuario['contenidoPerfil'] ?? ''));
$esPerfilPropio = $idUsuarioPerfil === $idUsuarioActual;
?>

<section class="content page-fade">
  <div class="container-fluid">
    <div class="profile-hero mb-4">
      <div class="profile-hero__content">
        <div class="profile-hero__avatar">
          <img src="./img/<?php echo $e($imagenUsuario); ?>" alt="Foto de perfil de <?php echo $e($nombreCompleto); ?>">
        </div>
        <div class="profile-hero__copy">
          <span class="profile-kicker">Perfil de la comunidad</span>
          <h1 class="profile-title mb-2"><?php echo $e($nombreCompleto !== '' ? $nombreCompleto : 'Usuario'); ?></h1>
          <p class="profile-lead mb-3">
            <?php echo $e($usuario['rol'] ?? 'Integrante'); ?> · <?php echo $e($seccion['tituloSeccion'] ?? 'Materia'); ?>
          </p>
          <div class="d-flex flex-wrap">
            <a href="index.php?r=detalle-seccion&idSeccion=<?php echo $idSeccion; ?>#personas" class="btn btn-light border mr-2 mb-2">
              <i class="fas fa-arrow-left mr-1"></i>Volver a personas
            </a>
            <?php if ($esPerfilPropio): ?>
              <a href="index.php?r=editar-perfil" class="btn btn-light border mb-2">
                <i class="fas fa-user-edit mr-1"></i>Editar mi perfil
              </a>
            <?php else: ?>
              <a href="index.php?r=nuevo-mensaje&id_destinatario=<?php echo $idUsuarioPerfil; ?>" class="btn btn-light border mb-2">
                <i class="fas fa-paper-plane mr-1"></i>Enviar mensaje
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="card glass-card">
          <div class="card-header">
            <h3 class="card-title mb-0">Sobre mí</h3>
          </div>
          <div class="card-body">
            <div class="profile-about">
              <?php if ($sobreMi !== ''): ?>
                <?php echo nl2br($e($sobreMi)); ?>
              <?php else: ?>
                <span class="text-muted">Esta persona todavía no agregó una presentación.</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
