<?php
$perfilActual = ControladorUsuarios::crtUsuarioActual();
$perfilDatos = $perfilActual ? ModeloPerfiles::mdlObtenerPerfilPorUsuario((int) ($_SESSION['usuario']['id'] ?? 0)) : [];
$registro = ControladorPerfiles::crtEditarPerfil();
$redirigir = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['success_message']));
$nombreCompleto = trim((string) (($perfilActual['nombreUsuario'] ?? '') . ' ' . ($perfilActual['apellidoUsuario'] ?? '')));
$e = static function ($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
};
?>

<?php if ($redirigir): ?>
  <script>
    setTimeout(function () {
      window.location.href = 'index.php?r=perfil-usuario';
    }, 650);
  </script>
<?php endif; ?>

<section class="content page-fade">
  <div class="container-fluid">
    <div class="profile-hero mb-4">
      <div class="profile-hero__content">
        <div class="profile-hero__avatar">
          <img src="./img/<?php echo $e($perfilActual['imgUsuario'] ?? 'user2-160x160.jpg'); ?>" alt="Foto de perfil">
        </div>
        <div class="profile-hero__copy">
          <span class="profile-kicker">Editar mi perfil</span>
          <h1 class="profile-title mb-2"><?php echo $e($nombreCompleto !== '' ? $nombreCompleto : 'Usuario'); ?></h1>
          <p class="profile-lead mb-0">Actualizá tu información personal y tu foto de perfil.</p>
        </div>
      </div>
    </div>

    <div class="card glass-card">
      <div class="card-header">
        <h3 class="card-title mb-0">Mis datos</h3>
      </div>
      <div class="card-body">
        <form action="" method="post" enctype="multipart/form-data">
          <input type="hidden" name="id_usuario" value="<?php echo (int) ($_SESSION['usuario']['id'] ?? 0); ?>">
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>Foto de perfil</label>
                <div class="profile-upload">
                  <input type="file" class="profile-upload__input profile-file-input" id="perfilImgUsuario" name="imgUsuario" accept="image/*">
                  <label class="profile-upload__button" for="perfilImgUsuario">
                    <i class="fas fa-image mr-2"></i>Seleccionar foto
                  </label>
                  <span class="profile-file-name">Ningún archivo seleccionado</span>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Domicilio</label>
                <input type="text" class="form-control" name="domicilioPerfil" value="<?php echo $e($perfilDatos['domicilioPerfil'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Fecha de nacimiento</label>
                <input type="date" class="form-control" name="fnacPerfil" value="<?php echo $e($perfilDatos['fnacPerfil'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-12">
              <div class="form-group">
                <label>Sobre mí</label>
                <textarea class="form-control" rows="4" name="contenidoPerfil"><?php echo $e($perfilDatos['contenidoPerfil'] ?? ''); ?></textarea>
              </div>
            </div>
          </div>

          <div class="d-flex justify-content-end gap-2">
            <a href="index.php?r=perfil-usuario" class="btn btn-light border">Volver</a>
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>
