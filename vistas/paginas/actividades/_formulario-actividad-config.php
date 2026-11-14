<?php
$borradorFormulario = ControladorActividades::crtConsumirBorradorFormulario((int) ($actividad['idActividad'] ?? 0));
if (is_array($borradorFormulario)) {
  $actividad = array_merge((array) $actividad, (array) ($borradorFormulario['actividad'] ?? []));
  $preguntas = (array) ($borradorFormulario['preguntas'] ?? []);
}
$esEdicion = !empty($actividad['idActividad']);
$tipoActual = (string) ($actividad['tipoActividad'] ?? 'multiple_choice');
$visibilidadActual = (string) ($actividad['visibilidad'] ?? 'privada');
$estadoActual = (string) ($actividad['estadoActividad'] ?? 'BORRADOR');
$idUsuarioActual = (int) ($_SESSION['usuario']['id'] ?? 0);
$materias = ControladorPermisos::esAdministrador()
  ? ControladorMaterias::crtListarMateriasGestion()
  : ControladorMaterias::crtBuscarMateriasPorDocente($idUsuarioActual);

if (empty($preguntas)) {
  $preguntas = [[
    'textoPregunta' => '',
    'respuestaCorrecta' => '',
    'codigoBase' => '',
    'lenguajeCodigo' => 'plaintext',
    'variantesCodigo' => '',
    'puntaje' => 1,
    'pista' => '',
    'explicacionError' => '',
    'opciones' => [
      ['textoOpcion' => '', 'esCorrecta' => 1],
      ['textoOpcion' => '', 'esCorrecta' => 0],
      ['textoOpcion' => '', 'esCorrecta' => 0],
      ['textoOpcion' => '', 'esCorrecta' => 0],
    ],
  ]];
}

$e = static function ($valor) {
  return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
};
$tipos = ControladorActividades::tiposDisponibles();
if ($esEdicion && !isset($tipos[$tipoActual])) {
  $tipos = [$tipoActual => $tipoActual === 'verdadero_falso' ? 'Verdadero o falso (actividad anterior)' : 'Completar los espacios (actividad anterior)'] + $tipos;
}
$lenguajesCodigo = ControladorActividades::lenguajesCodigoDisponibles();
$visibilidades = ControladorActividades::visibilidadesDisponibles();
$estados = ControladorActividades::estadosDisponibles();
