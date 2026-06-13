<?php
ControladorAuth::crtRecuperarPassword();
$forgotError = $_SESSION['forgot_error'] ?? '';
$forgotSuccess = $_SESSION['forgot_success'] ?? '';
$forgotTempPassword = $_SESSION['forgot_temp_password'] ?? '';
unset($_SESSION['forgot_error'], $_SESSION['forgot_success'], $_SESSION['forgot_temp_password']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/png" sizes="16x16" href="/img/favicon-16x16.png?v=2">
  <link rel="shortcut icon" type="image/png" href="/img/favicon-16x16.png?v=2">
  <meta name="msapplication-TileColor" content="#ffffff">
  <meta name="theme-color" content="#ffffff">
  <title>Classroom | Recuperar contraseña</title>
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
        <p class="login-card-subtitle mb-0">Ingresá tu correo y te generaremos una contraseña temporal para volver a entrar.</p>
      </div>

      <form action="index.php?r=forgot" method="post" autocomplete="off">
        <div class="input-group mb-3">
          <input type="email" class="form-control" name="forgot_email" placeholder="Correo electrónico" required>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-envelope"></span>
            </div>
          </div>
        </div>
        <button type="submit" class="btn auth-cta text-white btn-block">Generar contraseña temporal</button>
      </form>

      <?php if ($forgotSuccess !== '' && $forgotTempPassword !== ''): ?>
        <div class="mt-4 p-3 rounded-lg border bg-light">
          <h2 class="h6 mb-2">Contraseña temporal</h2>
          <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap: .75rem;">
            <code class="badge badge-dark px-3 py-2"><?php echo htmlspecialchars($forgotTempPassword, ENT_QUOTES, 'UTF-8'); ?></code>
            <small class="text-muted">Usala para ingresar y luego actualizala desde tu perfil.</small>
          </div>
        </div>
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
<?php if ($forgotSuccess !== ''): ?>
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      Toastify({
        text: <?php echo json_encode($forgotSuccess, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
        duration: 4500,
        close: true,
        gravity: "top",
        position: "right",
        style: {
          background: "linear-gradient(135deg, #16a34a, #22c55e)"
        }
      }).showToast();
    });
  </script>
<?php endif; ?>
</body>
</html>
