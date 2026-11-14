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
    var formularioActividad = document.querySelector('[data-actividad-form]');
    var preguntasPayload = document.getElementById('preguntasPayload');

    function esArrastre(valor) {
      return ['arrastrar_pares', 'arrastrar_orden', 'arrastrar_completar'].indexOf(valor) !== -1;
    }

    function indicePregunta(card) {
      var input = card.querySelector('[name^="preguntaTexto["]');
      var match = input && input.name.match(/preguntaTexto\[(\d+)\]/);
      return match ? match[1] : '0';
    }

    function campoTarjeta(indice, clave, lado, etiqueta, tipoArchivo) {
      var nombre = tipoArchivo === 'imagen' ? 'arrastreImagen' : 'arrastreAudio';
      var aceptar = tipoArchivo === 'imagen' ? 'image/jpeg,image/png,image/gif,image/webp' : 'audio/mpeg,audio/mp3,audio/wav,audio/ogg,audio/webm,audio/mp4';
      var identificador = 'dnd_' + tipoArchivo + '_' + indice + '_' + String(clave).replace(/[^a-z0-9_-]/ig, '') + '_' + lado;
      return '<div class="dnd-upload"><label class="small mb-1">' + etiqueta + '</label><input type="file" class="dnd-file-input" data-dnd-file data-dnd-' + tipoArchivo + ' id="' + identificador + '" name="' + nombre + '[' + indice + '][' + clave + '_' + lado + ']" accept="' + aceptar + '"><label class="btn btn-outline-secondary btn-sm dnd-file-button" for="' + identificador + '"><i class="fas fa-folder-open mr-1"></i>Seleccionar archivo</label><span class="dnd-file-name" data-dnd-file-name>Sin archivo seleccionado</span></div>';
    }

    function tarjetaConfig(indice, clave, lado, datos) {
      datos = datos || {};
      var texto = String(datos.textoOpcion || datos.texto || '');
      var imagen = String(datos.imagenOpcion || datos.imagen || '');
      var audio = String(datos.audioOpcion || datos.audio || '');
      return '<div class="border rounded p-2 mb-2" data-dnd-card-config data-dnd-key="' + clave + '" data-dnd-side="' + lado + '" data-dnd-image-existing="' + imagen.replace(/&/g, '&amp;').replace(/"/g, '&quot;') + '" data-dnd-audio-existing="' + audio.replace(/&/g, '&amp;').replace(/"/g, '&quot;') + '"><div class="form-group mb-2"><label class="small mb-1">Título de tarjeta</label><input type="text" class="form-control form-control-sm" data-dnd-text value="' + texto.replace(/&/g, '&amp;').replace(/"/g, '&quot;') + '" placeholder="Texto opcional"></div>' + campoTarjeta(indice, clave, lado, 'Imagen opcional', 'imagen') + campoTarjeta(indice, clave, lado, 'Audio opcional', 'audio') + '<button type="button" class="btn btn-outline-secondary btn-sm" data-record-audio><i class="fas fa-microphone mr-1"></i>Grabar audio</button></div>';
    }

    function renderEditorArrastre(card, valor) {
      var editor = card.querySelector('[data-dnd-editor]');
      if (!editor || !esArrastre(valor)) { return; }
      if (editor.dataset.mode === valor) { return; }
      var indice = indicePregunta(card);
      var existentes = [];
      try { existentes = JSON.parse(editor.getAttribute('data-dnd-existing') || '[]'); } catch (error) { existentes = []; }
      var claseFilas = valor === 'arrastrar_completar' || valor === 'arrastrar_orden' ? ' class="row dnd-card-grid"' : '';
      var html = '<div class="alert alert-light border mb-3"><i class="fas fa-hand-pointer mr-1"></i>Configurá de 2 a 8 tarjetas. Podés usar texto, imagen, audio subido o audio grabado.</div><div data-dnd-rows' + claseFilas + '>';
      if (valor === 'arrastrar_pares') {
        var origenes = existentes.filter(function (item) { return item.tipoArrastre === 'origen'; });
        var destinos = existentes.filter(function (item) { return item.tipoArrastre === 'destino'; });
        var totalPares = Math.max(2, origenes.length, destinos.length);
        for (var par = 0; par < totalPares; par += 1) {
          html += '<div class="row border rounded p-2 mb-2" data-dnd-row data-dnd-key="' + (par + 1) + '"><div class="col-md-6"><strong class="small d-block mb-1">Origen ' + (par + 1) + '</strong>' + tarjetaConfig(indice, par + 1, 'origen', origenes[par]) + '</div><div class="col-md-6"><strong class="small d-block mb-1">Destino correcto ' + (par + 1) + '</strong>' + tarjetaConfig(indice, par + 1, 'destino', destinos[par]) + '</div><div class="col-12 text-right"><button type="button" class="btn btn-link text-danger btn-sm" data-dnd-remove>Quitar par</button></div></div>';
        }
      } else {
        var tarjetas = existentes.length ? existentes : [{}, {}];
        tarjetas.forEach(function (tarjeta, posicion) {
          var correcto = valor === 'arrastrar_completar' && Number(tarjeta.esCorrecta || 0) === 1;
          var encabezado = valor === 'arrastrar_orden' ? '<strong class="small d-block mb-2">Paso ' + (posicion + 1) + ' de la secuencia</strong>' : '';
          var claseTarjeta = valor === 'arrastrar_completar' ? 'col-md-3 mb-3' : 'col-md-6 mb-3';
          html += '<div class="' + claseTarjeta + '" data-dnd-row data-dnd-key="' + (posicion + 1) + '"><div class="border rounded p-2 h-100">' + encabezado + tarjetaConfig(indice, posicion + 1, valor === 'arrastrar_orden' ? 'orden' : 'completar', tarjeta) + (valor === 'arrastrar_completar' ? '<label class="small d-block"><input type="checkbox" data-dnd-correct ' + (correcto || (!existentes.length && posicion === 0) ? 'checked' : '') + '> Respuesta correcta para un espacio</label>' : '') + '<div class="text-right"><button type="button" class="btn btn-link text-danger btn-sm" data-dnd-remove>Quitar tarjeta</button></div></div></div>';
        });
      }
      if (valor === 'arrastrar_completar') { html = html.replace('Configurá de 2 a 8 tarjetas. Podés usar texto, imagen, audio subido o audio grabado.', 'Marcá cada espacio del enunciado como <code>__</code> o <code>[[respuesta]]</code>. Podés crear varios espacios y conservar los saltos de línea; marcá una tarjeta correcta por cada espacio, en el mismo orden.'); }
      html += '</div><button type="button" class="btn btn-outline-primary btn-sm" data-dnd-add><i class="fas fa-plus mr-1"></i>Agregar tarjeta</button>';
      editor.innerHTML = html;
      editor.dataset.mode = valor;
    }

    function actualizarEditoresArrastre() {
      preguntasContainer.querySelectorAll('.actividad-pregunta-card').forEach(function (card) { renderEditorArrastre(card, tipo.value); });
      document.querySelectorAll('[data-dnd-editor]').forEach(function (editor) { editor.style.display = esArrastre(tipo.value) ? '' : 'none'; });
    }

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
      actualizarEditoresArrastre();
    }

    function valorCampoActivo(card, prefijoNombre) {
      var campos = card.querySelectorAll('[name^="' + prefijoNombre + '["]');
      for (var indice = 0; indice < campos.length; indice += 1) {
        if (!campos[indice].disabled) {
          return campos[indice].value;
        }
      }
      return '';
    }

    function serializarPreguntas() {
      if (tipo.value === 'externa') {
        return [];
      }

      return Array.prototype.map.call(
        preguntasContainer.querySelectorAll('.actividad-pregunta-card'),
        function (card) {
          var opciones = Array.prototype.map.call(
            card.querySelectorAll('[name^="opciones["]:not([disabled])'),
            function (campo) {
              return campo.value;
            }
          );
          var opcionCorrecta = card.querySelector('[name^="opcionCorrecta["]:checked:not([disabled])');
          var tarjetasArrastre = Array.prototype.map.call(card.querySelectorAll('[data-dnd-card-config]'), function (tarjeta) {
            var imagen = tarjeta.querySelector('[data-dnd-imagen]');
            var audio = tarjeta.querySelector('[data-dnd-audio]');
            return {
              texto: (tarjeta.querySelector('[data-dnd-text]') || {}).value || '',
              lado: tarjeta.getAttribute('data-dnd-side') || '',
              clave: tarjeta.getAttribute('data-dnd-key') || 0,
              correcta: !!((tarjeta.closest('[data-dnd-row]') || tarjeta).querySelector('[data-dnd-correct]:checked')),
              imagen: tarjeta.getAttribute('data-dnd-image-existing') || '',
              audio: tarjeta.getAttribute('data-dnd-audio-existing') || '',
              tieneNuevaImagen: !!(imagen && imagen.files && imagen.files.length),
              tieneNuevoAudio: !!(audio && audio.files && audio.files.length)
            };
          });

          return {
            textoPregunta: valorCampoActivo(card, 'preguntaTexto'),
            respuestaCorrecta: valorCampoActivo(card, 'respuestaCorrecta'),
            puntaje: valorCampoActivo(card, 'puntajePregunta'),
            pista: valorCampoActivo(card, 'pistaPregunta'),
            explicacionError: valorCampoActivo(card, 'explicacionError'),
            codigoBase: valorCampoActivo(card, 'codigoBase'),
            lenguajeCodigo: valorCampoActivo(card, 'lenguajeCodigo'),
            variantesCodigo: valorCampoActivo(card, 'variantesCodigo'),
            opciones: opciones,
            opcionCorrecta: opcionCorrecta ? opcionCorrecta.value : 0,
            tarjetasArrastre: tarjetasArrastre
          };
        }
      );
    }

    if (formularioActividad && preguntasPayload) {
      formularioActividad.addEventListener('submit', function () {
        preguntasPayload.value = JSON.stringify(serializarPreguntas());

        var camposPreguntas = preguntasContainer.querySelectorAll('[name]');
        camposPreguntas.forEach(function (campo) {
          if (campo.type !== 'file') { campo.disabled = true; }
        });

        window.setTimeout(function () {
          camposPreguntas.forEach(function (campo) {
            campo.disabled = false;
          });
          actualizarFormulario();
        }, 0);
      });
    }

    tipo.addEventListener('change', actualizarFormulario);
    preguntasContainer.addEventListener('click', function (event) {
      var agregar = event.target.closest('[data-dnd-add]');
      var quitar = event.target.closest('[data-dnd-remove]');
      if (!agregar && !quitar) { return; }
      var editor = event.target.closest('[data-dnd-editor]');
      if (!editor) { return; }
      var rows = editor.querySelector('[data-dnd-rows]');
      if (quitar) { if (rows.querySelectorAll('[data-dnd-row]').length > 2) { quitar.closest('[data-dnd-row]').remove(); } return; }
      var maxFilas = tipo.value === 'arrastrar_pares' ? 4 : 8;
      if (rows.querySelectorAll('[data-dnd-row]').length >= maxFilas) { return; }
      var modelo = rows.querySelector('[data-dnd-row]');
      if (!modelo) { return; }
      var nuevo = modelo.cloneNode(true);
      var nuevaClave = rows.querySelectorAll('[data-dnd-row]').length + 1;
      nuevo.setAttribute('data-dnd-key', String(nuevaClave));
      nuevo.querySelectorAll('[data-dnd-card-config]').forEach(function (tarjeta) {
        var lado = tarjeta.getAttribute('data-dnd-side') || '';
        tarjeta.setAttribute('data-dnd-key', String(nuevaClave));
        tarjeta.setAttribute('data-dnd-image-existing', ''); tarjeta.setAttribute('data-dnd-audio-existing', '');
        tarjeta.querySelectorAll('input[type="text"], input[type="file"]').forEach(function (campo) { campo.value = ''; if (campo.name) { campo.name = campo.name.replace(/\[\d+\]\[[^\]]+\]/, '[' + indicePregunta(editor.closest('.actividad-pregunta-card')) + '][' + nuevaClave + '_' + lado + ']'); } });
      });
      nuevo.querySelectorAll('[data-dnd-correct]').forEach(function (campo) { campo.checked = false; });
      rows.appendChild(nuevo);
    });
    document.addEventListener('click', function (event) {
      var boton = event.target.closest('[data-record-audio]');
      if (!boton || !navigator.mediaDevices || !window.MediaRecorder) { return; }
      if (boton.dataset.recording === '1' && boton._recorder) { boton._recorder.stop(); return; }
      var tarjeta = boton.closest('[data-dnd-card-config]'); var input = tarjeta && tarjeta.querySelector('[data-dnd-audio]');
      if (!input) { return; }
      navigator.mediaDevices.getUserMedia({ audio: true }).then(function (stream) {
        var recorder = new MediaRecorder(stream); var partes = [];
        boton.dataset.recording = '1'; boton.textContent = 'Grabando… tocá para finalizar'; boton._recorder = recorder;
        recorder.ondataavailable = function (evento) { if (evento.data.size) { partes.push(evento.data); } };
        recorder.onstop = function () { var archivo = new File([new Blob(partes, { type: recorder.mimeType || 'audio/webm' })], 'grabacion.webm', { type: recorder.mimeType || 'audio/webm' }); var transferencia = new DataTransfer(); transferencia.items.add(archivo); input.files = transferencia.files; stream.getTracks().forEach(function (pista) { pista.stop(); }); delete boton.dataset.recording; boton._recorder = null; boton.innerHTML = '<i class="fas fa-microphone mr-1"></i>Audio grabado'; };
        recorder.start();
      }).catch(function () { alert('No se pudo acceder al micrófono. Podés subir un audio.'); });
    });
    document.addEventListener('change', function (event) {
      var archivo = event.target.closest('[data-dnd-file]');
      if (!archivo) { return; }
      var etiqueta = archivo.closest('.dnd-upload').querySelector('[data-dnd-file-name]');
      if (etiqueta) { etiqueta.textContent = archivo.files && archivo.files.length ? archivo.files[0].name : 'Sin archivo seleccionado'; }
    });
    renumerarPreguntas();
    actualizarFormulario();
  });
</script>
