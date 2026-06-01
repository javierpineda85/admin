<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Classroom | Recuperar contraseña</title>
  <link rel="stylesheet" href="./plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="./plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <link rel="stylesheet" href="./css/adminlte.min.css">
  <style>
    body.login-page {
      background: radial-gradient(circle at top left, #22c55e 0%, #14532d 45%, #052e16 100%);
    }
    .login-box {
      width: 420px;
      max-width: calc(100vw - 2rem);
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
      <p class="login-box-msg">Recuperar contraseña</p>

      <form action="#" method="post">
        <div class="input-group mb-3">
          <input type="email" class="form-control" placeholder="Correo electrónico" required>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-envelope"></span>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-12">
            <button type="submit" class="btn btn-success btn-block">Solicitar nueva contraseña</button>
          </div>
        </div>
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
