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
  <link rel="stylesheet" href="./css/classroom-theme.css">
</head>
<body class="hold-transition login-page classroom-auth">
<div class="login-box">
  <div class="login-logo">
    <a href="#"><b>Class</b>room</a>
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
</body>
</html>
