<?php
ControladorAuth::crtRecuperarPassword();
$forgotError = $_SESSION['forgot_error'] ?? '';
unset($_SESSION['forgot_error'], $_SESSION['forgot_success'], $_SESSION['forgot_temp_password']);
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
        <p class="login-card-subtitle mb-0">La recuperación automática está temporalmente deshabilitada por seguridad.</p>
      </div>

      <div class="alert alert-warning" role="alert">
        Para recuperar el acceso, contactá al soporte de tu institución. No se generan ni se muestran contraseñas temporales desde esta página.
      </div>

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
