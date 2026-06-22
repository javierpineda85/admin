<?php
ControladorAuth::crtIniciarSesion();
$loginError = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
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
  <title>Campus | Iniciar sesión</title>
  <link rel="stylesheet" href="./plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="./plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <link rel="stylesheet" href="./css/adminlte.min.css">
  <link rel="stylesheet" href="./css/classroom-theme.css?v=20260611-identity-headers">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
</head>
<body class="hold-transition login-page classroom-auth">
<div class="login-box">
  <div class="login-logo">
    <a href="index.php">
      <img src="img/logo con texto blanco.png" alt="Classroom">
    </a>
  </div>
  <div class="card">
    <div class="card-body login-card-body">
      <div class="auth-pills">
        <span class="auth-pill">Estudiantes</span>
        <span class="auth-pill">Docentes</span>
        <span class="auth-pill">Administradores</span>
      </div>

      <div class="mb-4">
        <h1 class="login-card-title mb-2">Ingresá a tu aula virtual</h1>
        <p class="login-card-subtitle mb-0">Una experiencia más clara para aprender, enseñar y administrar sin fricción.</p>
      </div>

      <form action="index.php?r=login" method="post" autocomplete="off">
        <div class="input-group mb-3">
          <input type="email" name="login_email" class="form-control" placeholder="Correo electrónico" required>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-envelope"></span>
            </div>
          </div>
        </div>
        <div class="input-group mb-3">
          <input type="password" id="loginPassword" name="login_pass" class="form-control" placeholder="Contraseña" required>
          <div class="input-group-append">
            <button type="button" id="toggleLoginPassword" class="input-group-text border-left-0" aria-label="Mostrar contrasena" aria-pressed="false">
              <span class="fas fa-eye" aria-hidden="true"></span>
            </button>
          </div>
        </div>
        <div class="row align-items-center">
          <div class="col-8">
            <p class="mb-0 text-muted small">Acceso para estudiantes, docentes y administradores.</p>
          </div>
          <div class="col-4">
            <button type="submit" class="btn auth-cta text-white btn-block">Entrar</button>
          </div>
        </div>
      </form>

      <p class="mt-3 mb-1">
        <a href="index.php?r=forgot">Olvidé mi contraseña</a>
      </p>
    </div>
  </div>
</div>

<script src="./plugins/jquery/jquery.min.js"></script>
<script src="./plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="./js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<script src="./js/pwa-install.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var password = document.getElementById('loginPassword');
    var toggle = document.getElementById('toggleLoginPassword');

    if (!password || !toggle) {
      return;
    }

    toggle.addEventListener('click', function () {
      var mostrar = password.type === 'password';
      password.type = mostrar ? 'text' : 'password';
      toggle.setAttribute('aria-pressed', mostrar ? 'true' : 'false');
      toggle.setAttribute('aria-label', mostrar ? 'Ocultar contrasena' : 'Mostrar contrasena');

      var icono = toggle.querySelector('span');
      if (icono) {
        icono.classList.toggle('fa-eye', !mostrar);
        icono.classList.toggle('fa-eye-slash', mostrar);
      }
    });
  });
</script>
<?php if ($loginError !== ''): ?>
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      Toastify({
        text: <?php echo json_encode($loginError, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
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
