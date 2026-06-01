<!-- jQuery -->
<script src="./plugins/jquery/jquery.min.js"></script>
<!-- jQuery UI 1.11.4 -->
<script src="./plugins/jquery-ui/jquery-ui.min.js"></script>
<script>
  $.widget.bridge('uibutton', $.ui.button)
</script>
<!-- Bootstrap 4 -->
<script src="./plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- ChartJS -->
<script src="./plugins/chart.js/Chart.min.js"></script>
<!-- Sparkline -->
<script src="./plugins/sparklines/sparkline.js"></script>
<!-- JQVMap -->
<script src="./plugins/jqvmap/jquery.vmap.min.js"></script>
<script src="./plugins/jqvmap/maps/jquery.vmap.usa.js"></script>
<!-- jQuery Knob Chart -->
<script src="./plugins/jquery-knob/jquery.knob.min.js"></script>
<!-- daterangepicker -->
<script src="./plugins/moment/moment.min.js"></script>
<script src="./plugins/daterangepicker/daterangepicker.js"></script>
<!-- Tempusdominus Bootstrap 4 -->
<script src="./plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
<!-- Summernote -->
<script src="./plugins/summernote/summernote-bs4.min.js"></script>
<!-- overlayScrollbars -->
<script src="./plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<!-- DataTables  & Plugins -->
<script src="./plugins/datatables/jquery.dataTables.min.js"></script>
<script src="./plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="./plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="./plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="./plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="./plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<script src="./plugins/jszip/jszip.min.js"></script>
<script src="./plugins/pdfmake/pdfmake.min.js"></script>
<script src="./plugins/pdfmake/vfs_fonts.js"></script>
<script src="./plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="./plugins/datatables-buttons/js/buttons.print.min.js"></script>
<script src="./plugins/datatables-buttons/js/buttons.colVis.min.js"></script>
<!-- AdminLTE App -->
<script src="./js/adminlte.min.js"></script>
<!-- Bootstrap4 Duallistbox -->
<script src="./plugins/bootstrap4-duallistbox/jquery.bootstrap-duallistbox.js"></script>
<script src="./plugins/bootstrap4-duallistbox/jquery.bootstrap-duallistbox.min.js"></script>
<!-- Bootstrap Switch -->
<script src="./plugins/bootstrap-switch/js/bootstrap-switch.min.js"></script>

<?php
$flashToasts = [];

if (isset($_SESSION['success_message']) && trim((string) $_SESSION['success_message']) !== '') {
  $flashToasts[] = [
    'type' => 'success',
    'message' => (string) $_SESSION['success_message'],
  ];
  unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message']) && trim((string) $_SESSION['error_message']) !== '') {
  $flashToasts[] = [
    'type' => 'error',
    'message' => (string) $_SESSION['error_message'],
  ];
  unset($_SESSION['error_message']);
}

if (!empty($flashToasts)) {
  echo '<script>
    document.addEventListener("DOMContentLoaded", function () {
      var toasts = ' . json_encode($flashToasts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';
      toasts.forEach(function (toast) {
        Toastify({
          text: toast.message,
          duration: 3500,
          close: true,
          gravity: "top",
          position: "right",
          style: {
            background: toast.type === "success"
              ? "linear-gradient(135deg, #16a34a, #22c55e)"
              : "linear-gradient(135deg, #dc2626, #ef4444)"
          }
        }).showToast();
      });
    });
  </script>';
}
?>

<script>
  $(function() {
    $("#example1").DataTable({
      "responsive": true,
      "lengthChange": false,
      "autoWidth": false,
      "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
    }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
    $('#example2').DataTable({
      "paging": true,
      "lengthChange": false,
      "searching": false,
      "ordering": true,
      "info": true,
      "autoWidth": false,
      "responsive": true,
    });
  });

  function ocultar() {
    $e = document.getElementById('ocultar');
    $e.classList.toggle('d-none');
  }

  $("#editar-perfil").submit(function (event) {
    var fnac = $('#fnac').val();
    var domicilioPerfil = $('#domicilioPerfil').val();
    var errores = [];

    if ($.trim(fnac) === '') {
        errores.push("Debe completar la fecha de nacimiento");
    }
    if ($.trim(domicilioPerfil) === '') {
        errores.push("Debe completar el domiclio");
    }

    if (errores.length > 0) {
        Toastify({
            text: errores.join(" / "),
            duration: 3000,
            close: true,
            gravity: "top",
            position: "right",
            style: {
              background: "linear-gradient(135deg, #dc2626, #ef4444)"
            }
        }).showToast();

        event.preventDefault();
    }
  });

  $(document).ready(function() {
      $('#inputDNI').on('input', function() {
        var valor = $(this).val();
        var input2 = $('#inputPass');
        var caracteresRestantes = $('#caracteresRestantes');

        if (valor.length > 8) {
          valor = valor.slice(0, 8);
        }

        input2.val(valor);

        var restantes = 8 - valor.length;
        caracteresRestantes.text('Caracteres restantes: ' + restantes);
      });

      if ($('#contenidoMensaje').length) {
        $('#contenidoMensaje').summernote({
          height: 220,
          placeholder: 'Escribí el mensaje y dale formato si hace falta...',
          toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline', 'clear']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['insert', ['link', 'picture', 'table']],
            ['view', ['fullscreen', 'codeview', 'help']]
          ]
        });
      }
    });
</script>
