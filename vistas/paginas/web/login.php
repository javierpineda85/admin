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
  <title>Classroom | Iniciar sesión</title>
  <link rel="stylesheet" href="./plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="./plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <link rel="stylesheet" href="./css/adminlte.min.css">
  <style>
    body.login-page {
      background: radial-gradient(circle at top left, #2e86de 0%, #1b1f3a 50%, #0f172a 100%);
    }
    .login-box {
      width: 420px;
      max-width: calc(100vw - 2rem);
    }
    .login-logo a {
      color: #fff;
      font-weight: 700;
      letter-spacing: .5px;
    }
    .login-card-body {
      border-radius: 18px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, .25);
    }
  </style>
</head>
<body class="hold-transition login-page">
<div class="login-box">
  <div class="login-logo">
    <a href="#"><b>Class</b>room</a>
  </div>
  <div class="card">
    <div class="card-body login-card-body">
      <p class="login-box-msg">Ingresá a tu aula virtual</p>

      <?php if ($loginError !== ''): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?php echo htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8'); ?>
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      <?php endif; ?>

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
          <input type="password" name="login_pass" class="form-control" placeholder="Contraseña" required>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-8">
            <p class="mb-0 text-muted small">Acceso para estudiantes, docentes y administradores.</p>
          </div>
          <div class="col-4">
            <button type="submit" class="btn btn-primary btn-block">Entrar</button>
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
</body>
</html>
