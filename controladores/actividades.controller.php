<?php
require_once('modelos/actividades.modelo.php');

class ControladorActividades
{
    public static function tiposDisponibles()
    {
        return [
            'multiple_choice' => 'Multiple choice',
            'verdadero_falso' => 'Verdadero / falso',
            'completar' => 'Completar espacios',
            'externa' => 'Recurso externo',
        ];
    }

    public static function visibilidadesDisponibles()
    {
        return [
            'privada' => 'Privada del curso',
            'publica' => 'Publica',
            'oculta' => 'Oculta por enlace',
        ];
    }

    public static function estadosDisponibles()
    {
        return [
            'BORRADOR' => 'Borrador',
            'PUBLICADA' => 'Publicada',
        ];
    }

    public static function crtListarActividades()
    {
        $busqueda = trim((string) ($_GET['q'] ?? ''));

        return ModeloActividades::mdlListarParaUsuario(
            (int) ($_SESSION['usuario']['id'] ?? 0),
            ControladorPermisos::rolActual(),
            $busqueda
        );
    }

    public static function crtListarPublicas()
    {
        return ModeloActividades::mdlListarPublicas();
    }

    public static function crtBuscarActividadPorId($idActividad)
    {
        return ModeloActividades::mdlBuscarPorId((int) $idActividad);
    }

    public static function crtBuscarActividadPorSlug($slug)
    {
        return ModeloActividades::mdlBuscarPorSlug(self::limpiarSlug($slug));
    }

    public static function crtPreguntasActividad($idActividad)
    {
        return ModeloActividades::mdlPreguntasConOpciones((int) $idActividad);
    }

    public static function crtIntentosActividad($idActividad)
    {
        return ModeloActividades::mdlIntentosActividad((int) $idActividad);
    }

    public static function crtIntentosUsadosUsuario($idActividad, $idUsuario)
    {
        return ModeloActividades::mdlContarIntentosUsuario((int) $idActividad, (int) $idUsuario);
    }

    public static function intentosAgotados(array $actividad, $idUsuario = null)
    {
        $idUsuario = $idUsuario !== null ? (int) $idUsuario : (int) ($_SESSION['usuario']['id'] ?? 0);
        $intentosPermitidos = (int) ($actividad['intentosPermitidos'] ?? 1);

        if ($idUsuario <= 0 || $intentosPermitidos <= 0) {
            return false;
        }

        return self::crtIntentosUsadosUsuario((int) ($actividad['idActividad'] ?? 0), $idUsuario) >= $intentosPermitidos;
    }

    public static function puedeGestionarActividad(?array $actividad = null)
    {
        if (ControladorPermisos::esAdministrador()) {
            return true;
        }

        if (!ControladorPermisos::esDocente()) {
            return false;
        }

        if ($actividad === null) {
            return true;
        }

        $idUsuario = (int) ($_SESSION['usuario']['id'] ?? 0);
        return (int) ($actividad['id_autor'] ?? 0) === $idUsuario
            || (int) ($actividad['docente'] ?? 0) === $idUsuario
            || (int) ($actividad['tutor'] ?? 0) === $idUsuario;
    }

    public static function puedeResolverActividad(array $actividad, $publica = false)
    {
        if (($actividad['estadoActividad'] ?? '') !== 'PUBLICADA') {
            return self::puedeGestionarActividad($actividad);
        }

        $visibilidad = (string) ($actividad['visibilidad'] ?? 'privada');
        if ($visibilidad === 'publica' || $visibilidad === 'oculta') {
            if ($publica && empty($_SESSION['logueado']) && (int) ($actividad['permiteVisitantes'] ?? 1) !== 1) {
                return false;
            }
            return true;
        }

        if ($publica || empty($_SESSION['logueado'])) {
            return false;
        }

        if (ControladorPermisos::esAdministrador() || self::puedeGestionarActividad($actividad)) {
            return true;
        }

        if (!ControladorPermisos::esEstudiante()) {
            return false;
        }

        return ControladorCursos::crtEstudianteInscriptoCurso(
            (int) ($_SESSION['usuario']['id'] ?? 0),
            (int) ($actividad['id_curso'] ?? 0)
        );
    }

    public static function crtProcesarAcciones()
    {
        $accion = trim((string) ($_POST['accion_actividad'] ?? ''));
        if ($accion === '') {
            return null;
        }

        if ($accion === 'guardar_actividad') {
            return self::guardarActividad();
        }

        if ($accion === 'responder_actividad') {
            return self::registrarIntento();
        }

        if ($accion === 'eliminar_actividad') {
            return self::eliminarActividad();
        }

        return null;
    }

    private static function eliminarActividad()
    {
        $idActividad = (int) ($_POST['idActividad'] ?? 0);
        $actividad = $idActividad > 0 ? self::crtBuscarActividadPorId($idActividad) : null;

        if (!$actividad || !self::puedeGestionarActividad($actividad)) {
            $_SESSION['error_message'] = 'No tenes permisos para eliminar esta actividad.';
            return 'denied';
        }

        $respuesta = ModeloActividades::mdlEliminarActividad($idActividad);
        if ($respuesta !== 'ok') {
            $_SESSION['error_message'] = 'No se pudo eliminar la actividad.';
            return 'error';
        }

        unset($_SESSION['actividad_resultado_' . $idActividad]);
        $_SESSION['success_message'] = 'Actividad eliminada permanentemente.';
        self::redirigir('index.php?r=listado-actividades');
    }

    private static function guardarActividad()
    {
        $idActividad = (int) ($_POST['idActividad'] ?? 0);
        $actividadActual = $idActividad > 0 ? self::crtBuscarActividadPorId($idActividad) : null;

        if (!self::puedeGestionarActividad($actividadActual)) {
            $_SESSION['error_message'] = 'No tenes permisos para guardar esta actividad.';
            return 'denied';
        }

        $tipo = self::valorPermitido($_POST['tipoActividad'] ?? '', array_keys(self::tiposDisponibles()), 'multiple_choice');
        $visibilidad = self::valorPermitido($_POST['visibilidad'] ?? '', array_keys(self::visibilidadesDisponibles()), 'privada');
        $estado = self::valorPermitido($_POST['estadoActividad'] ?? '', array_keys(self::estadosDisponibles()), 'BORRADOR');
        $titulo = trim((string) ($_POST['tituloActividad'] ?? ''));

        if ($titulo === '') {
            $_SESSION['error_message'] = 'Completa el titulo de la actividad.';
            return 'error';
        }

        $idSeccion = (int) ($_POST['id_seccion'] ?? 0);
        $seccion = $idSeccion > 0 ? ControladorLecciones::crtBuscarSeccionPorId($idSeccion) : null;
        $idCurso = $seccion ? (int) ($seccion['id_curso'] ?? 0) : 0;

        if ($visibilidad === 'privada' && (!$seccion || $idCurso <= 0)) {
            $_SESSION['error_message'] = 'Las actividades privadas deben estar asociadas a una materia.';
            return 'error';
        }

        if ($seccion && !ControladorPermisos::esAdministrador()) {
            $idUsuario = (int) ($_SESSION['usuario']['id'] ?? 0);
            if (!ControladorLecciones::crtSeccionAsignadaDocente($idSeccion, $idUsuario)) {
                $_SESSION['error_message'] = 'No podes crear actividades en una materia donde no estas asignado.';
                return 'denied';
            }
        }

        $preguntas = self::normalizarPreguntas($tipo);
        if ($tipo !== 'externa' && empty($preguntas)) {
            $_SESSION['error_message'] = 'Agrega al menos una pregunta.';
            return 'error';
        }

        $puntajeMaximo = 0;
        foreach ($preguntas as $pregunta) {
            $puntajeMaximo += (float) $pregunta['puntaje'];
        }

        $slug = self::slugUnico($titulo, $idActividad);

        $datos = [
            'tituloActividad' => $titulo,
            'slug' => $slug,
            'descripcionActividad' => trim((string) ($_POST['descripcionActividad'] ?? '')),
            'tipoActividad' => $tipo,
            'visibilidad' => $visibilidad,
            'estadoActividad' => $estado,
            'id_curso' => $idCurso,
            'id_seccion' => $idSeccion,
            'id_autor' => (int) ($_SESSION['usuario']['id'] ?? 0),
            'puntajeMaximo' => $puntajeMaximo,
            'intentosPermitidos' => max(0, (int) ($_POST['intentosPermitidos'] ?? 1)),
            'permiteVisitantes' => isset($_POST['permiteVisitantes']) ? 1 : 0,
            'recursoExternoUrl' => trim((string) ($_POST['recursoExternoUrl'] ?? '')),
            'recursoExternoEmbed' => trim((string) ($_POST['recursoExternoEmbed'] ?? '')),
        ];

        $respuesta = $idActividad > 0
            ? ModeloActividades::mdlActualizarActividad($idActividad, $datos, $preguntas)
            : ModeloActividades::mdlGuardarActividad($datos, $preguntas);

        if ($respuesta === 'error') {
            $_SESSION['error_message'] = 'No se pudo guardar la actividad.';
            return 'error';
        }

        $_SESSION['success_message'] = 'Actividad guardada correctamente.';
        $idDestino = $idActividad > 0 ? $idActividad : (int) $respuesta;
        self::redirigir('index.php?r=editar-actividad&idActividad=' . $idDestino);
    }

    private static function registrarIntento()
    {
        $idActividad = (int) ($_POST['idActividad'] ?? 0);
        $actividad = self::crtBuscarActividadPorId($idActividad);
        $esPublica = empty($_SESSION['logueado']);

        if (!$actividad || !self::puedeResolverActividad($actividad, $esPublica)) {
            $_SESSION['error_message'] = 'No tenes acceso a esta actividad.';
            return 'denied';
        }

        if (($actividad['tipoActividad'] ?? '') === 'externa') {
            $_SESSION['error_message'] = 'Esta actividad externa no registra correccion automatica.';
            return 'error';
        }

        $preguntas = self::crtPreguntasActividad($idActividad);
        $respuestasPost = $_POST['respuesta'] ?? [];
        $total = 0;
        $respuestas = [];
        $detalleResultado = [];
        $idUsuario = !empty($_SESSION['logueado']) ? (int) ($_SESSION['usuario']['id'] ?? 0) : 0;
        $intentosPermitidos = (int) ($actividad['intentosPermitidos'] ?? 1);

        if ($idUsuario > 0 && $intentosPermitidos > 0) {
            $intentosUsados = ModeloActividades::mdlContarIntentosUsuario($idActividad, $idUsuario);
            if ($intentosUsados >= $intentosPermitidos) {
                $_SESSION['error_message'] = 'Ya usaste los intentos permitidos para esta actividad.';
                return 'denied';
            }
        }

        foreach ($preguntas as $pregunta) {
            $resultado = self::corregirPregunta($pregunta, $respuestasPost[$pregunta['idPregunta']] ?? null);
            $total += (float) $resultado['puntajeObtenido'];
            $respuestas[] = $resultado;
            $detalleResultado[] = [
                'pregunta' => (string) ($pregunta['textoPregunta'] ?? ''),
                'respuesta' => (string) ($resultado['textoRespuesta'] ?? ''),
                'correcta' => (int) ($resultado['esCorrecta'] ?? 0) === 1,
                'explicacionError' => (string) ($pregunta['explicacionError'] ?? ''),
            ];
        }

        $idIntento = ModeloActividades::mdlRegistrarIntento([
            'id_actividad' => $idActividad,
            'id_usuario' => $idUsuario,
            'nombreVisitante' => $idUsuario > 0 ? '' : trim((string) ($_POST['nombreVisitante'] ?? '')),
            'emailVisitante' => $idUsuario > 0 ? '' : trim((string) ($_POST['emailVisitante'] ?? '')),
            'puntaje' => $total,
            'estadoIntento' => 'ENTREGADO',
            'ipVisitante' => $_SERVER['REMOTE_ADDR'] ?? '',
        ], $respuestas);

        if ($idIntento <= 0) {
            $_SESSION['error_message'] = 'No se pudo registrar el intento.';
            return 'error';
        }

        $_SESSION['success_message'] = 'Actividad entregada. Puntaje: ' . $total . ' / ' . (float) ($actividad['puntajeMaximo'] ?? 0);
        $_SESSION['actividad_resultado_' . $idActividad] = [
            'puntaje' => $total,
            'puntajeMaximo' => (float) ($actividad['puntajeMaximo'] ?? 0),
            'detalle' => $detalleResultado,
        ];
        $destino = !empty($_SESSION['logueado'])
            ? 'index.php?r=ver-actividad&idActividad=' . $idActividad
            : 'index.php?r=actividad-publica&slug=' . urlencode((string) $actividad['slug']);
        self::redirigir($destino);
    }

    private static function corregirPregunta(array $pregunta, $respuesta)
    {
        $tipo = (string) ($pregunta['tipoPregunta'] ?? '');
        $puntaje = (float) ($pregunta['puntaje'] ?? 0);
        $idPregunta = (int) ($pregunta['idPregunta'] ?? 0);
        $idOpcion = 0;
        $textoRespuesta = '';
        $correcta = false;

        if ($tipo === 'multiple_choice' || ($tipo === 'completar' && !empty($pregunta['opciones']))) {
            $idOpcion = (int) $respuesta;
            foreach (($pregunta['opciones'] ?? []) as $opcion) {
                if ((int) $opcion['idOpcion'] === $idOpcion) {
                    $textoRespuesta = (string) $opcion['textoOpcion'];
                    $correcta = (int) $opcion['esCorrecta'] === 1;
                    break;
                }
            }
        } else {
            $textoRespuesta = trim((string) $respuesta);
            $correcta = self::normalizarTexto($textoRespuesta) === self::normalizarTexto((string) ($pregunta['respuestaCorrecta'] ?? ''));
        }

        return [
            'id_pregunta' => $idPregunta,
            'id_opcion' => $idOpcion,
            'textoRespuesta' => $textoRespuesta,
            'esCorrecta' => $correcta ? 1 : 0,
            'puntajeObtenido' => $correcta ? $puntaje : 0,
        ];
    }

    private static function normalizarPreguntas($tipo)
    {
        if ($tipo === 'externa') {
            return [];
        }

        $textos = $_POST['preguntaTexto'] ?? [];
        $respuestas = $_POST['respuestaCorrecta'] ?? [];
        $puntajes = $_POST['puntajePregunta'] ?? [];
        $pistas = $_POST['pistaPregunta'] ?? [];
        $explicaciones = $_POST['explicacionError'] ?? [];
        $opciones = $_POST['opciones'] ?? [];
        $correctas = $_POST['opcionCorrecta'] ?? [];
        $preguntas = [];

        foreach ($textos as $indice => $texto) {
            $texto = trim((string) $texto);
            if ($texto === '') {
                continue;
            }

            $pregunta = [
                'tipoPregunta' => $tipo,
                'textoPregunta' => $texto,
                'respuestaCorrecta' => trim((string) ($respuestas[$indice] ?? '')),
                'puntaje' => max(0, (float) ($puntajes[$indice] ?? 1)),
                'pista' => trim((string) ($pistas[$indice] ?? '')),
                'explicacionError' => trim((string) ($explicaciones[$indice] ?? '')),
                'opciones' => [],
            ];

            if ($tipo === 'verdadero_falso') {
                $pregunta['respuestaCorrecta'] = self::valorPermitido($pregunta['respuestaCorrecta'], ['verdadero', 'falso'], 'verdadero');
            }

            if ($tipo === 'multiple_choice') {
                $opcionesPregunta = $opciones[$indice] ?? [];
                $correctaIndice = (int) ($correctas[$indice] ?? 0);
                foreach ($opcionesPregunta as $orden => $opcionTexto) {
                    $opcionTexto = trim((string) $opcionTexto);
                    if ($opcionTexto === '') {
                        continue;
                    }

                    $pregunta['opciones'][] = [
                        'textoOpcion' => $opcionTexto,
                        'esCorrecta' => (int) $orden === $correctaIndice ? 1 : 0,
                    ];
                }

                if (count($pregunta['opciones']) < 2) {
                    continue;
                }
            }

            if ($tipo === 'completar') {
                if ($pregunta['respuestaCorrecta'] === '') {
                    continue;
                }

                $pregunta['opciones'][] = [
                    'textoOpcion' => $pregunta['respuestaCorrecta'],
                    'esCorrecta' => 1,
                ];

                $opcionesPregunta = $opciones[$indice] ?? [];
                $respuestaNormalizada = self::normalizarTexto($pregunta['respuestaCorrecta']);
                foreach ($opcionesPregunta as $opcionTexto) {
                    $opcionTexto = trim((string) $opcionTexto);
                    if ($opcionTexto === '' || self::normalizarTexto($opcionTexto) === $respuestaNormalizada) {
                        continue;
                    }

                    $pregunta['opciones'][] = [
                        'textoOpcion' => $opcionTexto,
                        'esCorrecta' => 0,
                    ];
                }
            }

            $preguntas[] = $pregunta;
        }

        return $preguntas;
    }

    private static function valorPermitido($valor, array $permitidos, $fallback)
    {
        $valor = trim((string) $valor);
        return in_array($valor, $permitidos, true) ? $valor : $fallback;
    }

    private static function redirigir($url)
    {
        $url = (string) $url;

        if (!headers_sent()) {
            header('Location: ' . $url);
            exit;
        }

        $urlJson = json_encode($url, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $urlHtml = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        echo '<script>window.location.href = ' . $urlJson . ';</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . $urlHtml . '"></noscript>';
        exit;
    }

    private static function limpiarSlug($valor)
    {
        return preg_replace('/[^a-z0-9\-]/', '', self::normalizarSlugBase($valor));
    }

    private static function slugUnico($valor, $excluirId = 0)
    {
        $slug = self::normalizarSlugBase($valor);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        if ($slug === '') {
            $slug = 'actividad';
        }

        $base = substr($slug, 0, 170);
        $slug = $base;
        $contador = 2;
        while (ModeloActividades::mdlSlugExiste($slug, (int) $excluirId)) {
            $slug = $base . '-' . $contador;
            $contador++;
        }

        return $slug;
    }

    private static function normalizarTexto($valor)
    {
        $valor = strtolower(trim((string) $valor));
        $valor = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $valor);
        $valor = preg_replace('/\s+/', ' ', $valor);
        return $valor;
    }

    private static function normalizarSlugBase($valor)
    {
        $slug = trim((string) $valor);
        $slug = strtr($slug, [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ã' => 'a',
            'Á' => 'a', 'À' => 'a', 'Ä' => 'a', 'Â' => 'a', 'Ã' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'É' => 'e', 'È' => 'e', 'Ë' => 'e', 'Ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'Í' => 'i', 'Ì' => 'i', 'Ï' => 'i', 'Î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'õ' => 'o',
            'Ó' => 'o', 'Ò' => 'o', 'Ö' => 'o', 'Ô' => 'o', 'Õ' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'Ú' => 'u', 'Ù' => 'u', 'Ü' => 'u', 'Û' => 'u',
            'ñ' => 'n', 'Ñ' => 'n', 'ç' => 'c', 'Ç' => 'c',
        ]);

        $convertido = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug);
        if ($convertido !== false && $convertido !== '') {
            $slug = $convertido;
        }

        return strtolower($slug);
    }
}
