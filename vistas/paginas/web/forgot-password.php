<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Classroom | Recuperar contraseña</title>
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
        <span class="auth-pill">Soporte</span>
        <span class="auth-pill">Acceso seguro</span>
      </div>
      <div class="mb-4">
        <h1 class="login-card-title mb-2">Recuperar contraseña</h1>
        <p class="login-card-subtitle mb-0">Te enviaremos un enlace o credencial temporal para volver a ingresar.</p>
      </div>

      <form action="#" method="post">
        <div class="input-group mb-3">
          <input type="email" class="form-control" placeholder="Correo electrónico" required>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-envelope"></span>
            </div>
          </div>
        </div>
        <button type="submit" class="btn auth-cta text-white btn-block">Solicitar recuperación</button>
      </form>

      <p class="mt-3 mb-1">
        <a href="index.php?r=login">Volver al inicio de sesión</a>
      </p>
    </div>
  </div>
</div>

<script src="./plugins/jquery/jquery.min.js"></script>
<script src="./plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="./js/adminlte.min.js"></script>
</body>
</html>
