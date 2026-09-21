<?php
ControladorAuth::crtRecuperarPassword();
$forgotError = $_SESSION['forgot_error'] ?? '';
$forgotSuccess = $_SESSION['forgot_success'] ?? '';
$recuperacionLocalDisponible = ControladorAuth::recuperacionLocalDisponible();
$tokenRecuperacion = strtolower(trim((string) ($_GET['token'] ?? $_POST['token'] ?? '')));
$tokenValido = $recuperacionLocalDisponible && $forgotSuccess === '' && $tokenRecuperacion !== ''
  ? ControladorAuth::tokenRecuperacionValido($tokenRecuperacion)
  : false;
unset($_SESSION['forgot_error'], $_SESSION['forgot_success']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/png" sizes="16x16" href="/img/favicon-16x16.png?v=2">
  <link rel="shortcut icon" type="image/png" href="/img/favicon-16x16.png?v=2">
  <link rel="manifest" href="./manifest.webmanifest">
  <link rel="apple-touch-icon" href="./pwa/icons/icon-192.png">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-title" content="Campus">
  <meta name="msapplication-TileColor" content="#ffffff">
  <meta name="theme-color" content="#59249b">
  <title>Campus | Recuperar contraseña</title>
  <link rel="stylesheet" href="./plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="./plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <link rel="stylesheet" href="./css/adminlte.min.css">
  <link rel="stylesheet" href="./css/classroom-theme.css?v=20260611-identity-headers">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
</head>
<body class="hold-transition login-page classroom-auth">
<div class="login-box">
  <div class="login-logo">
    <a href="index.php?r=login">
      <img src="img/logo con texto blanco.png" alt="Classroom">
    </a>
  </div>
  <div class="card">
    <div class="card-body login-card-body">
      <div class="auth-pills">
        <span class="auth-pill">Soporte</span>
        <span class="auth-pill">Acceso seguro</span>
      </div>
      <div class="mb-4">
        <h1 class="login-card-title mb-2">Recuperar contraseña</h1>
        <p class="login-card-subtitle mb-0">
          <?php echo $tokenValido ? 'Elegí una contraseña nueva para tu cuenta.' : ($recuperacionLocalDisponible ? 'Te enviaremos un enlace seguro si el correo corresponde a una cuenta local activa.' : 'La recuperación de esta cuenta se gestiona desde MenteMotion.'); ?>
        </p>
      </div>

      <?php if (!$recuperacionLocalDisponible): ?>
        <div class="alert alert-info" role="status">Usá la recuperación de contraseña de <a href="https://mentemotion.com/wp-login.php?action=lostpassword" rel="noopener noreferrer">mentemotion.com</a>.</div>
      <?php elseif ($forgotSuccess !== ''): ?>
        <div class="alert alert-success" role="status"><?php echo htmlspecialchars($forgotSuccess, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php elseif ($tokenRecuperacion !== '' && !$tokenValido): ?>
        <div class="alert alert-danger" role="alert">El enlace no es válido, ya fue utilizado o venció.</div>
      <?php endif; ?>

      <?php if ($recuperacionLocalDisponible && $tokenValido): ?>
        <form action="index.php?r=forgot" method="post" autocomplete="off">
          <input type="hidden" name="accion_restablecer_password" value="1">
          <input type="hidden" name="recuperacion_csrf" value="<?php echo htmlspecialchars(ControladorAuth::csrfRecuperacion(), ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="token" value="<?php echo htmlspecialchars($tokenRecuperacion, ENT_QUOTES, 'UTF-8'); ?>">
          <div class="form-group">
            <label for="passwordNueva">Nueva contraseña</label>
            <input id="passwordNueva" type="password" class="form-control" name="password_nueva" minlength="8" maxlength="72" autocomplete="new-password" required>
            <small class="form-text text-muted">Entre 8 y 72 caracteres, con letras y números.</small>
          </div>
          <div class="form-group">
            <label for="passwordConfirmacion">Confirmar contraseña</label>
            <input id="passwordConfirmacion" type="password" class="form-control" name="password_confirmacion" minlength="8" maxlength="72" autocomplete="new-password" required>
          </div>
          <button type="submit" class="btn auth-cta text-white btn-block">Guardar contraseña</button>
        </form>
      <?php elseif ($recuperacionLocalDisponible && $forgotSuccess === ''): ?>
        <form action="index.php?r=forgot" method="post" autocomplete="off">
          <input type="hidden" name="accion_solicitar_recuperacion" value="1">
          <input type="hidden" name="recuperacion_csrf" value="<?php echo htmlspecialchars(ControladorAuth::csrfRecuperacion(), ENT_QUOTES, 'UTF-8'); ?>">
          <div class="input-group mb-3">
            <input type="email" class="form-control" name="forgot_email" maxlength="254" placeholder="Correo electrónico" autocomplete="email" required>
            <div class="input-group-append"><div class="input-group-text"><span class="fas fa-envelope"></span></div></div>
          </div>
          <button type="submit" class="btn auth-cta text-white btn-block">Enviar enlace</button>
        </form>
      <?php endif; ?>

      <p class="mt-3 mb-1">
        <a href="index.php?r=login">Volver al inicio de sesión</a>
      </p>
    </div>
  </div>
</div>

<script src="./plugins/jquery/jquery.min.js"></script>
<script src="./plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="./js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<?php if ($forgotError !== ''): ?>
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      Toastify({
        text: <?php echo json_encode($forgotError, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
        duration: 3500,
        close: true,
        gravity: "top",
        position: "right",
        style: {
          background: "linear-gradient(135deg, #dc2626, #ef4444)"
        }
      }).showToast();
    });
  </script>
<?php endif; ?>
</body>
</html>
