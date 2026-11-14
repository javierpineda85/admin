<template id="preguntaTemplate">
  <div class="card mb-3 actividad-pregunta-card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <strong>Pregunta __NUMERO__</strong>
      <button type="button" class="btn btn-outline-danger btn-sm eliminar-pregunta">
        <i class="fas fa-trash"></i>
      </button>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-10"><div class="form-group">
          <label>Enunciado</label>
          <textarea name="preguntaTexto[__INDEX__]" class="form-control" rows="2" placeholder="Ejemplo: La etiqueta ____ se usa para crear un enlace."></textarea>
        </div></div>
        <div class="col-md-2"><div class="form-group">
          <label>Puntaje</label>
          <input type="number" name="puntajePregunta[__INDEX__]" class="form-control" min="0" step="0.5" value="1">
        </div></div>
      </div>
      <div class="form-group bloque-codigo">
        <div class="row">
          <div class="col-lg-4">
            <label>Lenguaje</label>
            <select name="lenguajeCodigo[__INDEX__]" class="form-control">
              <?php foreach ($lenguajesCodigo as $clave => $label): ?>
                <option value="<?php echo $e($clave); ?>" <?php echo $clave === 'plaintext' ? 'selected' : ''; ?>><?php echo $e($label); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <label class="mt-3">Código con error</label>
        <div class="code-editor-shell">
          <div class="code-editor-shell__header">
            <span>Fragmento para revisar</span>
            <small>Se muestra con numeración de líneas</small>
          </div>
          <textarea name="codigoBase[__INDEX__]" class="form-control code-editor-textarea" rows="10" placeholder="Pegá acá el fragmento de código que el estudiante debe revisar."></textarea>
        </div>
        <small class="form-text text-muted">Usá este bloque para el código original. Debajo definí cómo se acepta la corrección.</small>
      </div>
      <div class="row">
        <div class="col-md-12">
          <div class="form-group respuesta-simple">
            <label>Respuesta correcta</label>
            <input type="text" name="respuestaCorrecta[__INDEX__]" class="form-control">
          </div>
          <div class="form-group respuesta-completar">
            <label>Palabra correcta</label>
            <input type="text" name="respuestaCorrecta[__INDEX__]" class="form-control" placeholder="Ejemplo: a">
          </div>
          <div class="form-group respuesta-codigo">
            <label>Error o corrección esperada</label>
            <input type="text" name="respuestaCorrecta[__INDEX__]" class="form-control" placeholder="Ejemplo: falta COMMIT o falta cerrar una etiqueta">
            <small class="form-text text-muted">La corrección acepta redacciones equivalentes y compara palabras clave.</small>
          </div>
          <div class="form-group respuesta-vf">
            <label>Respuesta correcta</label>
            <select name="respuestaCorrecta[__INDEX__]" class="form-control">
              <option value="verdadero">Verdadero</option>
              <option value="falso">Falso</option>
            </select>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-6"><div class="form-group">
          <label>Pista opcional</label>
          <input type="text" name="pistaPregunta[__INDEX__]" class="form-control">
        </div></div>
        <div class="col-md-6"><div class="form-group">
          <label>Explicación si se equivoca</label>
          <textarea name="explicacionError[__INDEX__]" class="form-control" rows="2" placeholder="Opcional: explica por qué esa respuesta sería incorrecta."></textarea>
        </div></div>
      </div>
      <div class="form-group bloque-codigo">
        <label>Variantes válidas de respuesta</label>
        <textarea name="variantesCodigo[__INDEX__]" class="form-control" rows="3" placeholder="Una variante por línea. Ejemplo:&#10;falta commit&#10;no se confirma la transacción"></textarea>
        <small class="form-text text-muted">Sirve para aceptar distintas maneras correctas de describir el mismo error.</small>
      </div>
      <div class="opciones-multiple">
        <label>Opciones</label>
        <div class="row opciones-multiple__grid"><div class="col-md-3"><div class="input-group mb-2">
          <div class="input-group-prepend">
            <div class="input-group-text">
              <input type="radio" name="opcionCorrecta[__INDEX__]" value="0" checked>
            </div>
          </div>
          <input type="text" name="opciones[__INDEX__][0]" class="form-control" placeholder="Opción 1">
        </div></div><div class="col-md-3"><div class="input-group mb-2">
          <div class="input-group-prepend">
            <div class="input-group-text">
              <input type="radio" name="opcionCorrecta[__INDEX__]" value="1">
            </div>
          </div>
          <input type="text" name="opciones[__INDEX__][1]" class="form-control" placeholder="Opción 2">
        </div></div><div class="col-md-3"><div class="input-group mb-2">
          <div class="input-group-prepend">
            <div class="input-group-text">
              <input type="radio" name="opcionCorrecta[__INDEX__]" value="2">
            </div>
          </div>
          <input type="text" name="opciones[__INDEX__][2]" class="form-control" placeholder="Opción 3">
        </div></div><div class="col-md-3"><div class="input-group mb-2">
          <div class="input-group-prepend">
            <div class="input-group-text">
              <input type="radio" name="opcionCorrecta[__INDEX__]" value="3">
            </div>
          </div>
          <input type="text" name="opciones[__INDEX__][3]" class="form-control" placeholder="Opción 4">
        </div></div></div>
      </div>
      <div class="opciones-completar">
        <label>Palabras incorrectas</label>
        <input type="text" name="opciones[__INDEX__][0]" class="form-control mb-2" placeholder="Distractor 1">
        <input type="text" name="opciones[__INDEX__][1]" class="form-control mb-2" placeholder="Distractor 2">
        <input type="text" name="opciones[__INDEX__][2]" class="form-control mb-2" placeholder="Distractor 3">
      </div>
      <div class="opciones-arrastre" data-dnd-editor></div>
    </div>
  </div>
</template>
