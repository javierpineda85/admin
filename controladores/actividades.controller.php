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
            'codigo' => 'Detectar error en codigo',
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
        $tipo = trim((string) ($_GET['tipo'] ?? ''));
        $visibilidad = trim((string) ($_GET['visibilidad'] ?? ''));
        $estado = trim((string) ($_GET['estado'] ?? ''));
        $soloDestacadas = isset($_GET['destacadas']) && $_GET['destacadas'] !== '' ? 1 : '';

        return ModeloActividades::mdlListarParaUsuario(
            (int) ($_SESSION['usuario']['id'] ?? 0),
            ControladorPermisos::rolActual(),
            $busqueda,
            $tipo,
            $visibilidad,
            $estado,
            $soloDestacadas
        );
    }

    public static function crtListarBancoActividades()
    {
        $busqueda = trim((string) ($_GET['q'] ?? ''));
        $tipo = trim((string) ($_GET['tipo'] ?? ''));
        $alcance = trim((string) ($_GET['alcance'] ?? ''));
        $estado = trim((string) ($_GET['estado'] ?? ''));

        return ModeloActividades::mdlListarBancoParaUsuario(
            (int) ($_SESSION['usuario']['id'] ?? 0),
            ControladorPermisos::rolActual(),
            $busqueda,
            $tipo,
            $alcance,
            $estado
        );
    }

    public static function alcancesPlantillaDisponibles()
    {
        return [
            'personal' => 'Personal',
            'institucional' => 'Institucional',
        ];
    }

    public static function lenguajesCodigoDisponibles()
    {
        return [
            'plaintext' => 'Texto plano',
            'html' => 'HTML',
            'css' => 'CSS',
            'javascript' => 'JavaScript',
            'php' => 'PHP',
            'sql' => 'SQL',
            'python' => 'Python',
        ];
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

    public static function crtPanelResultadosActividad($idActividad)
    {
        $idActividad = (int) $idActividad;
        $intentos = ModeloActividades::mdlIntentosActividad($idActividad);
        $detallePlano = ModeloActividades::mdlDetalleIntentosActividad($idActividad);
        $metricasPreguntas = ModeloActividades::mdlMetricasPreguntasActividad($idActividad);
        $erroresFrecuentes = ModeloActividades::mdlErroresFrecuentesActividad($idActividad);

        $personas = [];
        $sumaPuntaje = 0.0;
        $mejorPuntaje = null;
        $menorPuntaje = null;
        $intentosPorPersona = [];

        foreach (array_reverse($intentos) as $intento) {
            $clavePersona = self::clavePersonaIntento($intento);
            if (!isset($intentosPorPersona[$clavePersona])) {
                $intentosPorPersona[$clavePersona] = 0;
            }
            $intentosPorPersona[$clavePersona]++;
        }

        $intentosAgrupados = [];
        $respuestasCorrectas = 0;
        $respuestasTotales = 0;

        foreach ($intentos as $intento) {
            $clavePersona = self::clavePersonaIntento($intento);
            $personas[$clavePersona] = true;

            $puntaje = (float) ($intento['puntaje'] ?? 0);
            $sumaPuntaje += $puntaje;
            $mejorPuntaje = $mejorPuntaje === null ? $puntaje : max($mejorPuntaje, $puntaje);
            $menorPuntaje = $menorPuntaje === null ? $puntaje : min($menorPuntaje, $puntaje);

            $intentosAgrupados[(int) $intento['idIntento']] = $intento + [
                'numeroIntentoPersona' => (int) ($intentosPorPersona[$clavePersona] ?? 1),
                'respuestas' => [],
            ];
            $intentosPorPersona[$clavePersona] = max(0, (int) ($intentosPorPersona[$clavePersona] ?? 1) - 1);
        }

        foreach ($detallePlano as $fila) {
            $idIntento = (int) ($fila['idIntento'] ?? 0);
            if ($idIntento <= 0 || !isset($intentosAgrupados[$idIntento]) || empty($fila['idRespuesta'])) {
                continue;
            }

            $esCorrecta = (int) ($fila['esCorrecta'] ?? 0) === 1;
            $respuestasTotales++;
            if ($esCorrecta) {
                $respuestasCorrectas++;
            }

            $intentosAgrupados[$idIntento]['respuestas'][] = [
                'idPregunta' => (int) ($fila['id_pregunta'] ?? 0),
                'pregunta' => (string) ($fila['textoPregunta'] ?? ''),
                'tipoPregunta' => (string) ($fila['tipoPregunta'] ?? ''),
                'textoRespuesta' => (string) ($fila['textoRespuesta'] ?? ''),
                'esCorrecta' => $esCorrecta,
                'puntajeObtenido' => (float) ($fila['puntajeObtenido'] ?? 0),
                'puntajePregunta' => (float) ($fila['puntajePregunta'] ?? 0),
                'respuestaCorrecta' => (string) ($fila['respuestaCorrecta'] ?? ''),
                'explicacionError' => (string) ($fila['explicacionError'] ?? ''),
                'lenguajeCodigo' => (string) ($fila['lenguajeCodigo'] ?? 'plaintext'),
            ];
        }

        $erroresPorPregunta = [];
        foreach ($erroresFrecuentes as $error) {
            $idPregunta = (int) ($error['idPregunta'] ?? 0);
            if ($idPregunta <= 0) {
                continue;
            }

            if (!isset($erroresPorPregunta[$idPregunta])) {
                $erroresPorPregunta[$idPregunta] = [];
            }

            if (count($erroresPorPregunta[$idPregunta]) < 5) {
                $erroresPorPregunta[$idPregunta][] = [
                    'textoRespuesta' => (string) ($error['textoRespuesta'] ?? ''),
                    'total' => (int) ($error['total'] ?? 0),
                ];
            }
        }

        foreach ($metricasPreguntas as &$metricaPregunta) {
            $totalRespuestasPregunta = (int) ($metricaPregunta['totalRespuestas'] ?? 0);
            $respuestasCorrectasPregunta = (int) ($metricaPregunta['respuestasCorrectas'] ?? 0);
            $respuestasIncorrectasPregunta = (int) ($metricaPregunta['respuestasIncorrectas'] ?? 0);
            $metricaPregunta['tasaAcierto'] = $totalRespuestasPregunta > 0
                ? round(($respuestasCorrectasPregunta / $totalRespuestasPregunta) * 100, 1)
                : 0.0;
            $metricaPregunta['tasaError'] = $totalRespuestasPregunta > 0
                ? round(($respuestasIncorrectasPregunta / $totalRespuestasPregunta) * 100, 1)
                : 0.0;
            $metricaPregunta['erroresFrecuentes'] = $erroresPorPregunta[(int) ($metricaPregunta['idPregunta'] ?? 0)] ?? [];
        }
        unset($metricaPregunta);

        $totalIntentos = count($intentos);

        return [
            'resumen' => [
                'totalIntentos' => $totalIntentos,
                'personasUnicas' => count($personas),
                'promedioPuntaje' => $totalIntentos > 0 ? round($sumaPuntaje / $totalIntentos, 2) : 0.0,
                'mejorPuntaje' => $mejorPuntaje !== null ? $mejorPuntaje : 0.0,
                'menorPuntaje' => $menorPuntaje !== null ? $menorPuntaje : 0.0,
                'respuestasTotales' => $respuestasTotales,
                'respuestasCorrectas' => $respuestasCorrectas,
                'precisionGlobal' => $respuestasTotales > 0 ? round(($respuestasCorrectas / $respuestasTotales) * 100, 1) : 0.0,
            ],
            'intentos' => array_values($intentosAgrupados),
            'preguntas' => $metricasPreguntas,
        ];
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

        if ($accion === 'duplicar_actividad') {
            return self::duplicarActividad(false);
        }

        if ($accion === 'guardar_como_plantilla') {
            return self::duplicarActividad(true);
        }

        if ($accion === 'usar_plantilla') {
            return self::usarPlantilla();
        }

        if ($accion === 'alternar_destacada_publica') {
            return self::alternarDestacadaPublica();
        }

        if ($accion === 'alternar_alcance_plantilla') {
            return self::alternarAlcancePlantilla();
        }

        if ($accion === 'sacar_del_banco') {
            return self::sacarDelBanco();
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
            'esPlantilla' => max(0, min(1, (int) ($actividadActual['esPlantilla'] ?? 0))),
            'id_actividad_origen' => (int) ($actividadActual['id_actividad_origen'] ?? 0),
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

    private static function duplicarActividad($comoPlantilla)
    {
        $idActividad = (int) ($_POST['idActividad'] ?? 0);
        $actividad = $idActividad > 0 ? self::crtBuscarActividadPorId($idActividad) : null;

        if (!$actividad || !self::puedeGestionarActividad($actividad)) {
            $_SESSION['error_message'] = 'No tenes permisos para reutilizar esta actividad.';
            return 'denied';
        }

        $preguntas = self::crtPreguntasActividad($idActividad);
        $tituloBase = trim((string) ($actividad['tituloActividad'] ?? 'Actividad'));
        if ($comoPlantilla) {
            $tituloBase = self::normalizarTituloPlantilla($tituloBase);
        } else {
            $tituloBase = self::normalizarTituloCopia($tituloBase);
        }

        $datos = self::datosActividadClonada($actividad, [
            'tituloActividad' => $tituloBase,
            'estadoActividad' => 'BORRADOR',
            'esPlantilla' => $comoPlantilla ? 1 : 0,
            'id_actividad_origen' => (int) ($actividad['idActividad'] ?? 0),
        ]);

        $idNuevaActividad = ModeloActividades::mdlGuardarActividad($datos, self::clonarPreguntas($preguntas));
        if ((int) $idNuevaActividad <= 0) {
            $_SESSION['error_message'] = 'No se pudo crear la copia de la actividad.';
            return 'error';
        }

        $_SESSION['success_message'] = $comoPlantilla
            ? 'La actividad se guardo como plantilla.'
            : 'Se creo una copia editable de la actividad.';

        self::redirigir($comoPlantilla
            ? 'index.php?r=banco-actividades'
            : 'index.php?r=editar-actividad&idActividad=' . (int) $idNuevaActividad);
    }

    private static function usarPlantilla()
    {
        $idActividad = (int) ($_POST['idActividad'] ?? 0);
        $actividad = $idActividad > 0 ? self::crtBuscarActividadPorId($idActividad) : null;

        if (!$actividad || !self::puedeGestionarActividad($actividad) || (int) ($actividad['esPlantilla'] ?? 0) !== 1) {
            $_SESSION['error_message'] = 'No tenes permisos para usar esta plantilla.';
            return 'denied';
        }

        $preguntas = self::crtPreguntasActividad($idActividad);
        $tituloBase = self::normalizarTituloDesdePlantilla((string) ($actividad['tituloActividad'] ?? 'Actividad'));
        $datos = self::datosActividadClonada($actividad, [
            'tituloActividad' => $tituloBase,
            'estadoActividad' => 'BORRADOR',
            'esPlantilla' => 0,
            'id_actividad_origen' => (int) ($actividad['idActividad'] ?? 0),
        ]);

        $idNuevaActividad = ModeloActividades::mdlGuardarActividad($datos, self::clonarPreguntas($preguntas));
        if ((int) $idNuevaActividad <= 0) {
            $_SESSION['error_message'] = 'No se pudo crear una actividad desde la plantilla.';
            return 'error';
        }

        $_SESSION['success_message'] = 'Se creo una nueva actividad a partir de la plantilla.';
        self::redirigir('index.php?r=editar-actividad&idActividad=' . (int) $idNuevaActividad);
    }

    private static function alternarDestacadaPublica()
    {
        $idActividad = (int) ($_POST['idActividad'] ?? 0);
        $actividad = $idActividad > 0 ? self::crtBuscarActividadPorId($idActividad) : null;

        if (!$actividad || !self::puedeGestionarActividad($actividad)) {
            $_SESSION['error_message'] = 'No tenes permisos para cambiar el destacado.';
            return 'denied';
        }

        if (!in_array((string) ($actividad['visibilidad'] ?? ''), ['publica', 'oculta'], true)) {
            $_SESSION['error_message'] = 'Solo las actividades publicas u ocultas pueden destacarse.';
            return 'error';
        }

        $nuevoValor = (int) ($actividad['destacadaPublica'] ?? 0) === 1 ? 0 : 1;
        $respuesta = ModeloActividades::mdlActualizarMetadatosActividad($idActividad, [
            'destacadaPublica' => $nuevoValor,
        ]);

        if ($respuesta !== 'ok') {
            $_SESSION['error_message'] = 'No se pudo actualizar el destacado.';
            return 'error';
        }

        $_SESSION['success_message'] = $nuevoValor === 1
            ? 'La actividad quedo marcada como destacada.'
            : 'La actividad dejo de estar destacada.';
        self::redirigir(self::rutaRetornoActividades());
    }

    private static function alternarAlcancePlantilla()
    {
        $idActividad = (int) ($_POST['idActividad'] ?? 0);
        $actividad = $idActividad > 0 ? self::crtBuscarActividadPorId($idActividad) : null;

        if (!$actividad || !self::puedeGestionarActividad($actividad) || (int) ($actividad['esPlantilla'] ?? 0) !== 1) {
            $_SESSION['error_message'] = 'No tenes permisos para cambiar el alcance de esta plantilla.';
            return 'denied';
        }

        $actual = (string) ($actividad['alcancePlantilla'] ?? 'personal');
        $nuevo = $actual === 'institucional' ? 'personal' : 'institucional';
        $respuesta = ModeloActividades::mdlActualizarMetadatosActividad($idActividad, [
            'alcancePlantilla' => $nuevo,
        ]);

        if ($respuesta !== 'ok') {
            $_SESSION['error_message'] = 'No se pudo actualizar el alcance de la plantilla.';
            return 'error';
        }

        $_SESSION['success_message'] = $nuevo === 'institucional'
            ? 'La plantilla ahora figura como institucional.'
            : 'La plantilla ahora figura como personal.';
        self::redirigir('index.php?r=banco-actividades');
    }

    private static function sacarDelBanco()
    {
        $idActividad = (int) ($_POST['idActividad'] ?? 0);
        $actividad = $idActividad > 0 ? self::crtBuscarActividadPorId($idActividad) : null;

        if (!$actividad || !self::puedeGestionarActividad($actividad) || (int) ($actividad['esPlantilla'] ?? 0) !== 1) {
            $_SESSION['error_message'] = 'No tenes permisos para mover esta plantilla.';
            return 'denied';
        }

        $respuesta = ModeloActividades::mdlActualizarMetadatosActividad($idActividad, [
            'esPlantilla' => 0,
            'estadoActividad' => 'BORRADOR',
            'destacadaPublica' => 0,
        ]);

        if ($respuesta !== 'ok') {
            $_SESSION['error_message'] = 'No se pudo mover la plantilla al listado de trabajo.';
            return 'error';
        }

        $_SESSION['success_message'] = 'La plantilla volvio al listado de actividades de trabajo.';
        self::redirigir('index.php?r=editar-actividad&idActividad=' . $idActividad);
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
            if ($tipo === 'codigo') {
                $correcta = self::coincideRespuestaCodigo($textoRespuesta, $pregunta);
            } else {
                $correcta = self::normalizarTexto($textoRespuesta) === self::normalizarTexto((string) ($pregunta['respuestaCorrecta'] ?? ''));
            }
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
        $codigos = $_POST['codigoBase'] ?? [];
        $lenguajesCodigo = $_POST['lenguajeCodigo'] ?? [];
        $variantesCodigo = $_POST['variantesCodigo'] ?? [];
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
                'codigoBase' => trim((string) ($codigos[$indice] ?? '')),
                'lenguajeCodigo' => self::valorPermitido($lenguajesCodigo[$indice] ?? 'plaintext', array_keys(self::lenguajesCodigoDisponibles()), 'plaintext'),
                'variantesCodigo' => trim((string) ($variantesCodigo[$indice] ?? '')),
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

            if ($tipo === 'codigo' && ($pregunta['codigoBase'] === '' || $pregunta['respuestaCorrecta'] === '')) {
                continue;
            }

            $preguntas[] = $pregunta;
        }

        return $preguntas;
    }

    private static function clonarPreguntas(array $preguntas)
    {
        $resultado = [];

        foreach ($preguntas as $pregunta) {
            $clon = [
                'tipoPregunta' => (string) ($pregunta['tipoPregunta'] ?? ''),
                'textoPregunta' => (string) ($pregunta['textoPregunta'] ?? ''),
                'respuestaCorrecta' => (string) ($pregunta['respuestaCorrecta'] ?? ''),
                'codigoBase' => (string) ($pregunta['codigoBase'] ?? ''),
                'lenguajeCodigo' => (string) ($pregunta['lenguajeCodigo'] ?? 'plaintext'),
                'variantesCodigo' => (string) ($pregunta['variantesCodigo'] ?? ''),
                'puntaje' => (float) ($pregunta['puntaje'] ?? 0),
                'pista' => (string) ($pregunta['pista'] ?? ''),
                'explicacionError' => (string) ($pregunta['explicacionError'] ?? ''),
                'opciones' => [],
            ];

            foreach (($pregunta['opciones'] ?? []) as $opcion) {
                $clon['opciones'][] = [
                    'textoOpcion' => (string) ($opcion['textoOpcion'] ?? ''),
                    'esCorrecta' => (int) ($opcion['esCorrecta'] ?? 0),
                ];
            }

            $resultado[] = $clon;
        }

        return $resultado;
    }

    private static function datosActividadClonada(array $actividad, array $override = [])
    {
        $titulo = trim((string) ($override['tituloActividad'] ?? $actividad['tituloActividad'] ?? 'Actividad'));

        $datos = [
            'tituloActividad' => $titulo,
            'slug' => self::slugUnico($titulo),
            'descripcionActividad' => trim((string) ($actividad['descripcionActividad'] ?? '')),
            'tipoActividad' => (string) ($actividad['tipoActividad'] ?? 'multiple_choice'),
            'visibilidad' => (string) ($actividad['visibilidad'] ?? 'privada'),
            'estadoActividad' => (string) ($override['estadoActividad'] ?? $actividad['estadoActividad'] ?? 'BORRADOR'),
            'id_curso' => (int) ($actividad['id_curso'] ?? 0),
            'id_seccion' => (int) ($actividad['id_seccion'] ?? 0),
            'id_autor' => (int) ($_SESSION['usuario']['id'] ?? 0),
            'puntajeMaximo' => (float) ($actividad['puntajeMaximo'] ?? 0),
            'intentosPermitidos' => (int) ($actividad['intentosPermitidos'] ?? 1),
            'permiteVisitantes' => (int) ($actividad['permiteVisitantes'] ?? 1),
            'esPlantilla' => (int) ($override['esPlantilla'] ?? 0),
            'alcancePlantilla' => (string) ($override['alcancePlantilla'] ?? $actividad['alcancePlantilla'] ?? 'personal'),
            'destacadaPublica' => (int) ($override['destacadaPublica'] ?? $actividad['destacadaPublica'] ?? 0),
            'id_actividad_origen' => (int) ($override['id_actividad_origen'] ?? 0),
            'recursoExternoUrl' => trim((string) ($actividad['recursoExternoUrl'] ?? '')),
            'recursoExternoEmbed' => trim((string) ($actividad['recursoExternoEmbed'] ?? '')),
        ];

        if (array_key_exists('visibilidad', $override)) {
            $datos['visibilidad'] = (string) $override['visibilidad'];
        }

        return $datos;
    }

    private static function valorPermitido($valor, array $permitidos, $fallback)
    {
        $valor = trim((string) $valor);
        return in_array($valor, $permitidos, true) ? $valor : $fallback;
    }

    private static function clavePersonaIntento(array $intento)
    {
        $idUsuario = (int) ($intento['id_usuario'] ?? 0);
        if ($idUsuario > 0) {
            return 'usuario:' . $idUsuario;
        }

        $email = trim((string) ($intento['emailVisitante'] ?? ''));
        if ($email !== '') {
            return 'visitante-email:' . strtolower($email);
        }

        $nombre = trim((string) ($intento['nombreVisitante'] ?? ''));
        if ($nombre !== '') {
            return 'visitante-nombre:' . self::normalizarTexto($nombre);
        }

        return 'intento:' . (int) ($intento['idIntento'] ?? 0);
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

    private static function normalizarTituloCopia($titulo)
    {
        $titulo = trim((string) $titulo);
        return $titulo === '' ? 'Actividad copia' : $titulo . ' (copia)';
    }

    private static function normalizarTituloPlantilla($titulo)
    {
        $titulo = trim((string) $titulo);
        if ($titulo === '') {
            return 'Plantilla';
        }

        if (preg_match('/plantilla/i', $titulo)) {
            return $titulo;
        }

        return $titulo . ' - plantilla';
    }

    private static function normalizarTituloDesdePlantilla($titulo)
    {
        $titulo = trim((string) $titulo);
        $titulo = preg_replace('/\s*-\s*plantilla\s*$/i', '', $titulo);
        return $titulo !== '' ? $titulo : 'Nueva actividad';
    }

    private static function rutaRetornoActividades()
    {
        $ruta = trim((string) ($_POST['ruta_retorno'] ?? ''));
        return $ruta !== '' ? $ruta : 'index.php?r=listado-actividades';
    }

    private static function normalizarTexto($valor)
    {
        $valor = strtolower(trim((string) $valor));
        $valor = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $valor);
        $valor = preg_replace('/\s+/', ' ', $valor);
        return $valor;
    }

    private static function coincideRespuestaCodigo($respuesta, array $pregunta)
    {
        $respuestaNormalizada = self::normalizarTextoComparacion($respuesta);
        if ($respuestaNormalizada === '') {
            return false;
        }

        foreach (self::candidatosRespuestaCodigo($pregunta) as $candidata) {
            $candidataNormalizada = self::normalizarTextoComparacion($candidata);
            if ($candidataNormalizada === '') {
                continue;
            }

            if ($respuestaNormalizada === $candidataNormalizada) {
                return true;
            }

            if (strlen($candidataNormalizada) >= 6 && (strpos($respuestaNormalizada, $candidataNormalizada) !== false || strpos($candidataNormalizada, $respuestaNormalizada) !== false)) {
                return true;
            }

            similar_text($respuestaNormalizada, $candidataNormalizada, $porcentaje);
            if ($porcentaje >= 78) {
                return true;
            }

            $palabrasClave = self::palabrasClaveCodigo($candidataNormalizada);
            if (!empty($palabrasClave) && self::cumpleCoberturaPalabrasClave($respuestaNormalizada, $palabrasClave)) {
                return true;
            }
        }

        return false;
    }

    private static function candidatosRespuestaCodigo(array $pregunta)
    {
        $candidatos = [];
        $principal = trim((string) ($pregunta['respuestaCorrecta'] ?? ''));
        if ($principal !== '') {
            $candidatos[] = $principal;
        }

        $variantes = preg_split('/\r\n|\r|\n/', (string) ($pregunta['variantesCodigo'] ?? ''));
        foreach ($variantes as $variante) {
            $variante = trim((string) $variante);
            if ($variante !== '') {
                $candidatos[] = $variante;
            }
        }

        return array_values(array_unique($candidatos));
    }

    private static function normalizarTextoComparacion($valor)
    {
        $valor = self::normalizarTexto($valor);
        $valor = preg_replace('/[^a-z0-9\s]/', ' ', $valor);
        $valor = preg_replace('/\s+/', ' ', trim((string) $valor));
        return $valor;
    }

    private static function palabrasClaveCodigo($texto)
    {
        $stopwords = [
            'el', 'la', 'los', 'las', 'un', 'una', 'unos', 'unas', 'de', 'del', 'al', 'y', 'o',
            'que', 'se', 'en', 'por', 'para', 'con', 'sin', 'es', 'esta', 'este', 'falta',
            'hay', 'usar', 'usa', 'debe', 'deberia', 'tiene', 'tener', 'error', 'codigo'
        ];

        $partes = preg_split('/\s+/', (string) $texto);
        $palabras = [];
        foreach ($partes as $parte) {
            $parte = trim((string) $parte);
            if ($parte === '' || strlen($parte) < 3 || in_array($parte, $stopwords, true)) {
                continue;
            }
            $palabras[] = $parte;
        }

        return array_values(array_unique($palabras));
    }

    private static function cumpleCoberturaPalabrasClave($respuestaNormalizada, array $palabrasClave)
    {
        $coincidencias = 0;
        foreach ($palabrasClave as $palabra) {
            if (preg_match('/(^|\s)' . preg_quote($palabra, '/') . '(\s|$)/', $respuestaNormalizada)) {
                $coincidencias++;
            }
        }

        $minimo = count($palabrasClave) <= 2 ? count($palabrasClave) : max(2, (int) ceil(count($palabrasClave) * 0.7));
        return $coincidencias >= $minimo;
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
