<?php
$registro = ControladorPerfiles::crtEditarPerfil();
$perfilActual = ControladorUsuarios::crtUsuarioActual();
$perfilDatos = $perfilActual ? ModeloPerfiles::mdlObtenerPerfilPorUsuario((int) ($_SESSION['usuario']['id'] ?? 0)) : [];
$nombreCompleto = trim((string) (($perfilActual['nombreUsuario'] ?? '') . ' ' . ($perfilActual['apellidoUsuario'] ?? '')));
$puedeCambiarClave = (int) ($_SESSION['usuario']['id'] ?? 0) > 0
  && (int) ($_SESSION['usuario']['id'] ?? 0) === (int) ($perfilActual['idUsuario'] ?? 0);
$imagenPerfil = ControladorUsuarios::rutaImagenUsuario($perfilActual['imgUsuario'] ?? '', 'user2-160x160.jpg');
$e = static function ($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
};
?>

<section class="content page-fade">
  <div class="container-fluid">
    <div class="profile-hero mb-4">
      <div class="profile-hero__content">
        <div class="profile-hero__avatar">
          <img src="./img/<?php echo $e($imagenPerfil); ?>" alt="Foto de perfil">
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
                <div class="classroom-file profile-upload">
                  <input type="file" class="classroom-file__input profile-upload__input profile-file-input" id="perfilImgUsuario" name="imgUsuario" accept="image/*">
                  <label class="classroom-file__button profile-upload__button" for="perfilImgUsuario">
                    <i class="fas fa-image mr-2"></i>Seleccionar foto
                  </label>
                  <span class="classroom-file__name profile-file-name">Ningún archivo seleccionado</span>
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
                <label>DNI</label>
                <input type="text" class="form-control" name="dniPerfil" value="<?php echo $e($perfilDatos['dniPerfil'] ?? ''); ?>" maxlength="8">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Teléfono</label>
                <input type="text" class="form-control" name="telefonoPerfil" value="<?php echo $e($perfilDatos['telefonoPerfil'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Provincia</label>
                <input type="text" class="form-control" name="provinciaPerfil" value="<?php echo $e($perfilDatos['provinciaPerfil'] ?? ''); ?>">
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

          <?php if ($puedeCambiarClave): ?>
            <div class="profile-security-box mt-4">
              <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <div>
                  <h4 class="section-title mb-1">Cambiar contraseña</h4>
                  <p class="text-muted mb-0">Solo se muestra cuando editas tu propio perfil.</p>
                </div>
                <span class="badge badge-light border px-3 py-2">Seguridad</span>
              </div>
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Contraseña actual</label>
                    <input type="password" class="form-control" name="passActual" autocomplete="current-password" placeholder="Ingresá tu contraseña actual">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Nueva contraseña</label>
                    <input type="password" class="form-control" name="passNueva" autocomplete="new-password" minlength="8" placeholder="Mínimo 8 caracteres">
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Confirmar nueva contraseña</label>
                    <input type="password" class="form-control" name="passNuevaConfirmar" autocomplete="new-password" minlength="8" placeholder="Repetí la nueva contraseña">
                  </div>
                </div>
              </div>
            </div>
          <?php endif; ?>

          <div class="d-flex justify-content-end gap-2">
            <a href="index.php?r=perfil-usuario" class="btn btn-light border">Volver</a>
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>
