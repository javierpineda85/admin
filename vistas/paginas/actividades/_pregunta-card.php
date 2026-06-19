<?php
$opciones = $pregunta['opciones'] ?? [];
$lenguajesCodigo = $lenguajesCodigo ?? ControladorActividades::lenguajesCodigoDisponibles();
while (count($opciones) < 4) {
  $opciones[] = ['textoOpcion' => '', 'esCorrecta' => 0];
}
$correcta = 0;
foreach ($opciones as $orden => $opcion) {
  if ((int) ($opcion['esCorrecta'] ?? 0) === 1) {
    $correcta = $orden;
    break;
  }
}
$distractores = array_values(array_filter($opciones, static function ($opcion) {
  return (int) ($opcion['esCorrecta'] ?? 0) !== 1;
}));
while (count($distractores) < 3) {
  $distractores[] = ['textoOpcion' => '', 'esCorrecta' => 0];
}
?>
<div class="card mb-3 actividad-pregunta-card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <strong>Pregunta <?php echo (int) ($indice + 1); ?></strong>
    <button type="button" class="btn btn-outline-danger btn-sm eliminar-pregunta">
      <i class="fas fa-trash"></i>
    </button>
  </div>
  <div class="card-body">
    <div class="form-group">
      <label>Enunciado</label>
      <textarea name="preguntaTexto[<?php echo (int) $indice; ?>]" class="form-control" rows="2" placeholder="Ejemplo: La etiqueta ____ se usa para crear un enlace."><?php echo $e($pregunta['textoPregunta'] ?? ''); ?></textarea>
    </div>

    <div class="form-group bloque-codigo">
      <div class="row">
        <div class="col-lg-4">
          <label>Lenguaje</label>
          <select name="lenguajeCodigo[<?php echo (int) $indice; ?>]" class="form-control">
            <?php foreach ($lenguajesCodigo as $clave => $label): ?>
              <option value="<?php echo $e($clave); ?>" <?php echo ($pregunta['lenguajeCodigo'] ?? 'plaintext') === $clave ? 'selected' : ''; ?>><?php echo $e($label); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <label class="mt-3">Codigo con error</label>
      <div class="code-editor-shell">
        <div class="code-editor-shell__header">
          <span>Fragmento para revisar</span>
          <small>Se muestra con numeracion de lineas</small>
        </div>
        <textarea name="codigoBase[<?php echo (int) $indice; ?>]" class="form-control code-editor-textarea" rows="10" placeholder="Pega aca el fragmento de codigo que el estudiante debe revisar."><?php echo $e($pregunta['codigoBase'] ?? ''); ?></textarea>
      </div>
      <small class="form-text text-muted">Usa este bloque para el codigo original. Debajo define como se acepta la correccion.</small>
    </div>

    <div class="row">
      <div class="col-md-8">
        <div class="form-group respuesta-simple">
          <label>Respuesta correcta</label>
          <input type="text" name="respuestaCorrecta[<?php echo (int) $indice; ?>]" class="form-control" value="<?php echo $e($pregunta['respuestaCorrecta'] ?? ''); ?>">
        </div>
        <div class="form-group respuesta-completar">
          <label>Palabra correcta</label>
          <input type="text" name="respuestaCorrecta[<?php echo (int) $indice; ?>]" class="form-control" value="<?php echo $e($pregunta['respuestaCorrecta'] ?? ''); ?>" placeholder="Ejemplo: a">
        </div>
        <div class="form-group respuesta-codigo">
          <label>Error o correccion esperada</label>
          <input type="text" name="respuestaCorrecta[<?php echo (int) $indice; ?>]" class="form-control" value="<?php echo $e($pregunta['respuestaCorrecta'] ?? ''); ?>" placeholder="Ejemplo: falta COMMIT o falta cerrar una etiqueta">
          <small class="form-text text-muted">La correccion acepta redacciones equivalentes y compara palabras clave.</small>
        </div>
        <div class="form-group respuesta-vf">
          <label>Respuesta correcta</label>
          <select name="respuestaCorrecta[<?php echo (int) $indice; ?>]" class="form-control">
            <option value="verdadero" <?php echo ($pregunta['respuestaCorrecta'] ?? '') === 'verdadero' ? 'selected' : ''; ?>>Verdadero</option>
            <option value="falso" <?php echo ($pregunta['respuestaCorrecta'] ?? '') === 'falso' ? 'selected' : ''; ?>>Falso</option>
          </select>
        </div>
      </div>
      <div class="col-md-4">
        <div class="form-group">
          <label>Puntaje</label>
          <input type="number" name="puntajePregunta[<?php echo (int) $indice; ?>]" class="form-control" min="0" step="0.5" value="<?php echo $e($pregunta['puntaje'] ?? 1); ?>">
        </div>
      </div>
    </div>

    <div class="form-group">
      <label>Pista opcional</label>
      <input type="text" name="pistaPregunta[<?php echo (int) $indice; ?>]" class="form-control" value="<?php echo $e($pregunta['pista'] ?? ''); ?>">
    </div>

    <div class="form-group">
      <label>Explicacion si se equivoca</label>
      <textarea name="explicacionError[<?php echo (int) $indice; ?>]" class="form-control" rows="2" placeholder="Opcional: explica por que esa respuesta seria incorrecta."><?php echo $e($pregunta['explicacionError'] ?? ''); ?></textarea>
    </div>

    <div class="form-group bloque-codigo">
      <label>Variantes validas de respuesta</label>
      <textarea name="variantesCodigo[<?php echo (int) $indice; ?>]" class="form-control" rows="3" placeholder="Una variante por linea. Ejemplo:&#10;falta commit&#10;no se confirma la transaccion"><?php echo $e($pregunta['variantesCodigo'] ?? ''); ?></textarea>
      <small class="form-text text-muted">Sirve para aceptar distintas maneras correctas de describir el mismo error.</small>
    </div>

    <div class="opciones-multiple">
      <label>Opciones</label>
      <?php foreach ($opciones as $orden => $opcion): ?>
        <div class="input-group mb-2">
          <div class="input-group-prepend">
            <div class="input-group-text">
              <input type="radio" name="opcionCorrecta[<?php echo (int) $indice; ?>]" value="<?php echo (int) $orden; ?>" <?php echo $correcta === $orden ? 'checked' : ''; ?>>
            </div>
          </div>
          <input type="text" name="opciones[<?php echo (int) $indice; ?>][<?php echo (int) $orden; ?>]" class="form-control" value="<?php echo $e($opcion['textoOpcion'] ?? ''); ?>" placeholder="Opcion <?php echo (int) ($orden + 1); ?>">
        </div>
      <?php endforeach; ?>
    </div>

    <div class="opciones-completar">
      <label>Palabras incorrectas</label>
      <?php foreach (array_slice($distractores, 0, 3) as $orden => $opcion): ?>
        <input type="text" name="opciones[<?php echo (int) $indice; ?>][<?php echo (int) $orden; ?>]" class="form-control mb-2" value="<?php echo $e($opcion['textoOpcion'] ?? ''); ?>" placeholder="Distractor <?php echo (int) ($orden + 1); ?>">
      <?php endforeach; ?>
    </div>
  </div>
</div>
