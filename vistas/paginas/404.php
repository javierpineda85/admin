<?php $accesoDenegado = http_response_code() === 403; ?>
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1><?php echo $accesoDenegado ? 'Acceso denegado' : '404 Página no encontrada'; ?></h1>
          </div>
          <div class="col-sm-6">

          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="error-page">
        <h2 class="headline text-warning"> <?php echo $accesoDenegado ? '403' : '404'; ?></h2>

        <div class="error-content">
          <h3><i class="fas fa-exclamation-triangle text-warning"></i> <?php echo $accesoDenegado ? 'No tenés acceso a este recurso.' : 'Oops! Página no encontrada.'; ?></h3>

          <p>
            <?php echo $accesoDenegado
              ? 'El recurso no pertenece a tu institución activa o tu cuenta no tiene el permiso necesario.'
              : 'No podemos encontrar la página o recurso al que intentas acceder. Si consideras que es un error, por favor contacta al administrador.'; ?>
           
          </p>


        </div>
        <!-- /.error-content -->
      </div>
      <!-- /.error-page -->
