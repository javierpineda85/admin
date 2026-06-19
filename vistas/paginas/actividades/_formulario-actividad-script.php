<script>
  document.addEventListener('DOMContentLoaded', function () {
    document.body.style.cursor = 'default';
    document.querySelectorAll('textarea, input[type="text"], input[type="number"], input[type="url"]').forEach(function (campo) {
      campo.style.cursor = 'text';
    });
    document.querySelectorAll('button, .btn, select').forEach(function (control) {
      control.style.cursor = 'pointer';
    });

    var tipo = document.getElementById('tipoActividad');
    var preguntasContainer = document.getElementById('preguntasContainer');
    var agregarPregunta = document.getElementById('agregarPregunta');
    var preguntaTemplate = document.getElementById('preguntaTemplate');

    function renumerarPreguntas() {
      var cards = preguntasContainer.querySelectorAll('.actividad-pregunta-card');
      cards.forEach(function (card, index) {
        var titulo = card.querySelector('.card-header strong');
        var botonEliminar = card.querySelector('.eliminar-pregunta');
        if (titulo) {
          titulo.textContent = 'Pregunta ' + (index + 1);
        }
        if (botonEliminar) {
          botonEliminar.style.display = cards.length > 1 ? '' : 'none';
        }
      });
    }

    function indiceDisponible() {
      var mayor = -1;
      preguntasContainer.querySelectorAll('[name^="preguntaTexto["]').forEach(function (input) {
        var coincidencia = input.name.match(/preguntaTexto\[(\d+)\]/);
        if (coincidencia) {
          mayor = Math.max(mayor, parseInt(coincidencia[1], 10));
        }
      });
      return mayor + 1;
    }

    function vincularEliminar(card) {
      var boton = card.querySelector('.eliminar-pregunta');
      if (!boton) {
        return;
      }

      boton.addEventListener('click', function () {
        if (preguntasContainer.querySelectorAll('.actividad-pregunta-card').length <= 1) {
          return;
        }

        card.remove();
        renumerarPreguntas();
        actualizarFormulario();
      });
    }

    preguntasContainer.querySelectorAll('.actividad-pregunta-card').forEach(vincularEliminar);

    agregarPregunta.addEventListener('click', function () {
      var index = indiceDisponible();
      var html = preguntaTemplate.innerHTML
        .split('__INDEX__').join(String(index))
        .split('__NUMERO__').join(String(preguntasContainer.querySelectorAll('.actividad-pregunta-card').length + 1));
      var wrapper = document.createElement('div');
      wrapper.innerHTML = html.trim();
      var card = wrapper.firstElementChild;
      preguntasContainer.appendChild(card);
      vincularEliminar(card);
      renumerarPreguntas();
      actualizarFormulario();
    });

    function actualizarFormulario() {
      var valor = tipo.value;
      document.querySelectorAll('.actividad-externa').forEach(function (el) {
        el.style.display = valor === 'externa' ? '' : 'none';
      });
      document.querySelectorAll('.actividad-preguntas').forEach(function (el) {
        el.style.display = valor === 'externa' ? 'none' : '';
      });
      document.querySelectorAll('.opciones-multiple').forEach(function (el) {
        el.style.display = valor === 'multiple_choice' ? '' : 'none';
        el.querySelectorAll('input, select, textarea').forEach(function (input) {
          input.disabled = valor !== 'multiple_choice';
        });
      });
      document.querySelectorAll('.opciones-completar').forEach(function (el) {
        el.style.display = valor === 'completar' ? '' : 'none';
        el.querySelectorAll('input, select, textarea').forEach(function (input) {
          input.disabled = valor !== 'completar';
        });
      });
      document.querySelectorAll('.respuesta-simple').forEach(function (el) {
        el.style.display = 'none';
        el.querySelectorAll('input, select, textarea').forEach(function (input) {
          input.disabled = true;
        });
      });
      document.querySelectorAll('.respuesta-completar').forEach(function (el) {
        el.style.display = valor === 'completar' ? '' : 'none';
        el.querySelectorAll('input, select, textarea').forEach(function (input) {
          input.disabled = valor !== 'completar';
        });
      });
      document.querySelectorAll('.respuesta-codigo').forEach(function (el) {
        el.style.display = valor === 'codigo' ? '' : 'none';
        el.querySelectorAll('input, select, textarea').forEach(function (input) {
          input.disabled = valor !== 'codigo';
        });
      });
      document.querySelectorAll('.respuesta-vf').forEach(function (el) {
        el.style.display = valor === 'verdadero_falso' ? '' : 'none';
        el.querySelectorAll('input, select, textarea').forEach(function (input) {
          input.disabled = valor !== 'verdadero_falso';
        });
      });
      document.querySelectorAll('.bloque-codigo').forEach(function (el) {
        el.style.display = valor === 'codigo' ? '' : 'none';
        el.querySelectorAll('input, select, textarea').forEach(function (input) {
          input.disabled = valor !== 'codigo';
        });
      });
    }

    tipo.addEventListener('change', actualizarFormulario);
    renumerarPreguntas();
    actualizarFormulario();
  });
</script>
