<?php
if (($_SESSION['logueado'] ?? false) === true) {
  $rutaDestino = ControladorInstitucion::activo() ? ControladorInstitucion::rutaDestino() : '';
  $destino = $rutaDestino === ''
    ? 'index.php'
    : ($rutaDestino === 'superadmin' ? 'superadmin' : 'index.php?r=' . rawurlencode($rutaDestino));
  header('Location: ' . $destino, true, 303);
  exit;
}
ControladorAuth::crtRegistrarCuenta();
$registroError = $_SESSION['registro_error'] ?? '';
unset($_SESSION['registro_error']);
$registroDisponible = ControladorAuth::registroDisponible();
$e = static fn($valor) => htmlspecialchars((string)$valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/png" sizes="16x16" href="/img/favicon-16x16.png?v=2">
  <meta name="theme-color" content="#59249b">
  <title>Campus | Crear cuenta</title>
  <link rel="stylesheet" href="./plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="./css/adminlte.min.css">
  <link rel="stylesheet" href="./css/classroom-theme.css?v=20260611-identity-headers">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
</head>
<body class="hold-transition login-page classroom-auth">
<div class="login-box" style="width:min(430px,calc(100% - 2rem))">
  <div class="login-logo"><a href="index.php?r=login"><img src="img/logo con texto blanco.png" alt="Campus MenteMotion"></a></div>
  <div class="card"><div class="card-body login-card-body">
    <div class="mb-4">
      <h1 class="login-card-title mb-2">Creá tu cuenta</h1>
      <p class="login-card-subtitle mb-0">Registrate en Campus. Luego la institución podrá habilitar tu acceso académico.</p>
    </div>
    <?php if ($registroDisponible): ?>
      <form action="index.php?r=registro" method="post" autocomplete="off" id="formRegistro">
        <input type="hidden" name="accion_registro" value="crear_cuenta">
        <input type="hidden" name="registro_csrf" value="<?= $e(ControladorAuth::csrfRegistro()) ?>">
        <div aria-hidden="true" style="position:absolute;left:-10000px;width:1px;height:1px;overflow:hidden">
          <label for="registroWebsite">Sitio web</label><input id="registroWebsite" name="website" tabindex="-1" autocomplete="off">
        </div>
        <div class="row">
          <div class="col-sm-6"><div class="input-group mb-3">
            <input name="registro_nombre" class="form-control" maxlength="20" placeholder="Nombre" autocomplete="given-name" required value="<?= $e($_POST['registro_nombre'] ?? '') ?>">
          </div></div>
          <div class="col-sm-6"><div class="input-group mb-3">
            <input name="registro_apellido" class="form-control" maxlength="20" placeholder="Apellido" autocomplete="family-name" required value="<?= $e($_POST['registro_apellido'] ?? '') ?>">
          </div></div>
        </div>
        <div class="input-group mb-3">
          <input type="email" name="registro_email" class="form-control" maxlength="50" placeholder="Correo electrónico" autocomplete="email" required value="<?= $e($_POST['registro_email'] ?? '') ?>">
          <div class="input-group-append"><div class="input-group-text"><span class="fas fa-envelope"></span></div></div>
        </div>
        <div class="input-group mb-3">
          <input type="password" id="registroPassword" name="registro_password" class="form-control" minlength="8" maxlength="72" placeholder="Contraseña" autocomplete="new-password" required>
          <div class="input-group-append"><button type="button" class="input-group-text border-left-0 toggle-registro-password" data-target="registroPassword" aria-label="Mostrar contraseña" aria-pressed="false"><span class="fas fa-eye"></span></button></div>
        </div>
        <div class="input-group mb-2">
          <input type="password" id="registroPasswordConfirmacion" name="registro_password_confirmacion" class="form-control" minlength="8" maxlength="72" placeholder="Repetir contraseña" autocomplete="new-password" required>
          <div class="input-group-append"><button type="button" class="input-group-text border-left-0 toggle-registro-password" data-target="registroPasswordConfirmacion" aria-label="Mostrar contraseña" aria-pressed="false"><span class="fas fa-eye"></span></button></div>
        </div>
        <p class="text-muted small mb-3">Usá entre 8 y 72 caracteres, con letras y números.</p>
        <button type="submit" class="btn auth-cta text-white btn-block">Crear cuenta</button>
      </form>
    <?php else: ?>
      <div class="alert alert-info">El registro directo no está disponible. Creá tu cuenta desde MenteMotion.</div>
    <?php endif; ?>
    <p class="mt-3 mb-0 text-center"><a href="index.php?r=login">Ya tengo cuenta</a></p>
  </div></div>
</div>
<script src="./plugins/jquery/jquery.min.js"></script>
<script src="./plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.toggle-registro-password').forEach(function (boton) {
    boton.addEventListener('click', function () {
      var campo = document.getElementById(this.dataset.target);
      if (!campo) return;
      var mostrar = campo.type === 'password';
      campo.type = mostrar ? 'text' : 'password';
      this.setAttribute('aria-pressed', mostrar ? 'true' : 'false');
      this.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
      var icono = this.querySelector('span');
      if (icono) { icono.classList.toggle('fa-eye', !mostrar); icono.classList.toggle('fa-eye-slash', mostrar); }
    });
  });
});
</script>
<?php if ($registroError !== ''): ?>
<script>document.addEventListener('DOMContentLoaded',function(){Toastify({text:<?= json_encode($registroError, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,duration:4500,close:true,gravity:'top',position:'right',style:{background:'linear-gradient(135deg,#dc2626,#ef4444)'}}).showToast();});</script>
<?php endif; ?>
</body>
</html>
