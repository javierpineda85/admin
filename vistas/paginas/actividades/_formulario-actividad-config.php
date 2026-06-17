<?php
$esEdicion = !empty($actividad);
$tipoActual = (string) ($actividad['tipoActividad'] ?? 'multiple_choice');
$visibilidadActual = (string) ($actividad['visibilidad'] ?? 'privada');
$estadoActual = (string) ($actividad['estadoActividad'] ?? 'BORRADOR');
$idUsuarioActual = (int) ($_SESSION['usuario']['id'] ?? 0);
$materias = ControladorPermisos::esAdministrador()
  ? (new Conexion())->consultas("
      SELECT s.idSeccion, s.tituloSeccion, s.id_curso, c.nombreCurso
      FROM secciones s
      INNER JOIN cursos c ON c.idCurso = s.id_curso
      ORDER BY c.nombreCurso ASC, s.tituloSeccion ASC
    ")
  : ControladorMaterias::crtBuscarMateriasPorDocente($idUsuarioActual);

if (empty($preguntas)) {
  $preguntas = [[
    'textoPregunta' => '',
    'respuestaCorrecta' => '',
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
$visibilidades = ControladorActividades::visibilidadesDisponibles();
$estados = ControladorActividades::estadosDisponibles();
