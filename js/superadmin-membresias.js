document.addEventListener('DOMContentLoaded', function () {
  'use strict';
  var modal = document.getElementById('modalAgregarMembresias');
  if (!modal) return;
  var formulario = document.getElementById('formAgregarMembresias');
  var buscar = document.getElementById('buscarUsuarioMembresia');
  var origen = document.getElementById('origenUsuarioMembresia');
  var todos = document.getElementById('seleccionarUsuariosVisibles');
  var guardar = document.getElementById('guardarMembresiasSeleccionadas');
  var error = document.getElementById('errorSeleccionMembresias');
  var roles = Array.prototype.slice.call(formulario.querySelectorAll('[name="roles[]"]'));
  function normalizar(texto) {
    return texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
  }
  var filas = Array.prototype.map.call(document.querySelectorAll('#tablaUsuariosMembresia tbody tr'), function (fila) {
    return {elemento: fila, casilla: fila.querySelector('[name="usuarios[]"]'),
      instituciones: fila.dataset.instituciones.split(','),
      texto: normalizar(fila.cells[1].textContent + ' ' + fila.cells[2].textContent)};
  });
  function actualizar() {
    var texto = normalizar(buscar.value);
    var visibles = 0, seleccionados = 0, ocultos = 0, elegibles = 0, elegidosVisibles = 0;
    filas.forEach(function (fila) {
      var visible = fila.texto.indexOf(texto) !== -1 && (!origen.value || fila.instituciones.indexOf(origen.value) !== -1);
      fila.elemento.hidden = !visible;
      if (visible) visibles++;
      if (fila.casilla.checked) { seleccionados++; if (!visible) ocultos++; }
      if (visible && !fila.casilla.disabled) { elegibles++; if (fila.casilla.checked) elegidosVisibles++; }
    });
    todos.disabled = elegibles === 0;
    todos.checked = elegibles > 0 && elegidosVisibles === elegibles;
    todos.indeterminate = elegidosVisibles > 0 && elegidosVisibles < elegibles;
    document.getElementById('resumenUsuariosSeleccionados').textContent = visibles + ' visibles · ' + seleccionados + ' seleccionados' + (ocultos ? ' (' + ocultos + ' fuera del filtro)' : '');
    document.getElementById('sinUsuariosMembresia').hidden = visibles !== 0;
    guardar.disabled = !seleccionados || seleccionados > 200 || !roles.some(function (rol) { return rol.checked; });
    error.hidden = seleccionados <= 200;
    error.textContent = seleccionados > 200 ? 'Seleccioná como máximo 200 usuarios por operación.' : '';
  }
  // El panel utiliza los modales de Bootstrap 4, que emiten eventos jQuery.
  window.jQuery(modal).on('show.bs.modal', function (evento) {
    var boton = evento.relatedTarget;
    formulario.reset();
    var destino = boton ? boton.dataset.institucionId : '';
    document.getElementById('destinoMembresias').value = destino;
    document.getElementById('nombreDestinoMembresias').textContent = boton ? boton.dataset.institucionNombre : '';
    filas.forEach(function (fila) {
      var existe = fila.instituciones.indexOf(destino) !== -1;
      fila.casilla.checked = false;
      fila.casilla.disabled = !destino || existe;
      fila.elemento.querySelector('.estado-destino').textContent = existe ? 'Ya tiene membresía' : 'Disponible';
    });
    actualizar();
  });
  buscar.addEventListener('input', actualizar);
  origen.addEventListener('change', actualizar);
  formulario.addEventListener('change', function (evento) {
    if (evento.target === todos) {
      filas.forEach(function (fila) { if (!fila.elemento.hidden && !fila.casilla.disabled) fila.casilla.checked = todos.checked; });
    }
    actualizar();
  });
  document.getElementById('limpiarUsuariosSeleccionados').addEventListener('click', function () {
    filas.forEach(function (fila) { fila.casilla.checked = false; });
    actualizar();
  });
  formulario.addEventListener('submit', function (evento) {
    actualizar();
    if (guardar.disabled) { evento.preventDefault(); return; }
    guardar.disabled = true;
  });
});
