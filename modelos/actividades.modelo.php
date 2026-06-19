<?php
require_once('conexion.php');

class ModeloActividades
{
    private static $tablasPreparadas = false;

    private static function prepararTablas()
    {
        if (self::$tablasPreparadas) {
            return;
        }

        $pdo = Conexion::conectar();

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS actividades (
                idActividad INT NOT NULL AUTO_INCREMENT,
                tituloActividad VARCHAR(160) NOT NULL,
                slug VARCHAR(190) NOT NULL,
                descripcionActividad TEXT NULL,
                tipoActividad VARCHAR(30) NOT NULL DEFAULT 'multiple_choice',
                visibilidad VARCHAR(20) NOT NULL DEFAULT 'privada',
                estadoActividad VARCHAR(15) NOT NULL DEFAULT 'BORRADOR',
                id_curso INT NULL,
                id_seccion INT NULL,
                id_autor INT NOT NULL,
                puntajeMaximo DECIMAL(6,2) NOT NULL DEFAULT 0,
                intentosPermitidos INT NOT NULL DEFAULT 1,
                permiteVisitantes TINYINT(1) NOT NULL DEFAULT 1,
                esPlantilla TINYINT(1) NOT NULL DEFAULT 0,
                alcancePlantilla VARCHAR(20) NOT NULL DEFAULT 'personal',
                destacadaPublica TINYINT(1) NOT NULL DEFAULT 0,
                id_actividad_origen INT NULL,
                recursoExternoUrl VARCHAR(255) NULL,
                recursoExternoEmbed TEXT NULL,
                fechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                fechaActualizacion DATETIME NULL,
                PRIMARY KEY (idActividad),
                UNIQUE KEY uq_actividades_slug (slug),
                KEY idx_actividades_contexto (id_curso, id_seccion, visibilidad, estadoActividad),
                KEY idx_actividades_autor (id_autor)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS actividades_preguntas (
                idPregunta INT NOT NULL AUTO_INCREMENT,
                id_actividad INT NOT NULL,
                tipoPregunta VARCHAR(30) NOT NULL,
                textoPregunta TEXT NOT NULL,
                codigoBase MEDIUMTEXT NULL,
                lenguajeCodigo VARCHAR(30) NOT NULL DEFAULT 'plaintext',
                variantesCodigo TEXT NULL,
                respuestaCorrecta TEXT NULL,
                puntaje DECIMAL(6,2) NOT NULL DEFAULT 1,
                orden INT NOT NULL DEFAULT 1,
                pista TEXT NULL,
                explicacionError TEXT NULL,
                PRIMARY KEY (idPregunta),
                KEY idx_preguntas_actividad (id_actividad, orden)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS actividades_opciones (
                idOpcion INT NOT NULL AUTO_INCREMENT,
                id_pregunta INT NOT NULL,
                textoOpcion TEXT NOT NULL,
                esCorrecta TINYINT(1) NOT NULL DEFAULT 0,
                orden INT NOT NULL DEFAULT 1,
                PRIMARY KEY (idOpcion),
                KEY idx_opciones_pregunta (id_pregunta, orden)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS actividades_intentos (
                idIntento INT NOT NULL AUTO_INCREMENT,
                id_actividad INT NOT NULL,
                id_usuario INT NULL,
                nombreVisitante VARCHAR(120) NULL,
                emailVisitante VARCHAR(120) NULL,
                puntaje DECIMAL(6,2) NOT NULL DEFAULT 0,
                estadoIntento VARCHAR(15) NOT NULL DEFAULT 'ENTREGADO',
                fechaInicio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                fechaEntrega DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                ipVisitante VARCHAR(45) NULL,
                PRIMARY KEY (idIntento),
                KEY idx_intentos_actividad (id_actividad, fechaEntrega),
                KEY idx_intentos_usuario (id_usuario)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS actividades_respuestas (
                idRespuesta INT NOT NULL AUTO_INCREMENT,
                id_intento INT NOT NULL,
                id_pregunta INT NOT NULL,
                id_opcion INT NULL,
                textoRespuesta TEXT NULL,
                esCorrecta TINYINT(1) NOT NULL DEFAULT 0,
                puntajeObtenido DECIMAL(6,2) NOT NULL DEFAULT 0,
                PRIMARY KEY (idRespuesta),
                KEY idx_respuestas_intento (id_intento, id_pregunta)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        self::agregarColumnaSiFalta($pdo, 'actividades', 'intentosPermitidos', "ALTER TABLE actividades ADD COLUMN intentosPermitidos INT NOT NULL DEFAULT 1 AFTER puntajeMaximo");
        self::agregarColumnaSiFalta($pdo, 'actividades', 'esPlantilla', "ALTER TABLE actividades ADD COLUMN esPlantilla TINYINT(1) NOT NULL DEFAULT 0 AFTER permiteVisitantes");
        self::agregarColumnaSiFalta($pdo, 'actividades', 'alcancePlantilla', "ALTER TABLE actividades ADD COLUMN alcancePlantilla VARCHAR(20) NOT NULL DEFAULT 'personal' AFTER esPlantilla");
        self::agregarColumnaSiFalta($pdo, 'actividades', 'destacadaPublica', "ALTER TABLE actividades ADD COLUMN destacadaPublica TINYINT(1) NOT NULL DEFAULT 0 AFTER alcancePlantilla");
        self::agregarColumnaSiFalta($pdo, 'actividades', 'id_actividad_origen', "ALTER TABLE actividades ADD COLUMN id_actividad_origen INT NULL AFTER esPlantilla");
        self::agregarColumnaSiFalta($pdo, 'actividades_preguntas', 'codigoBase', "ALTER TABLE actividades_preguntas ADD COLUMN codigoBase MEDIUMTEXT NULL AFTER textoPregunta");
        self::agregarColumnaSiFalta($pdo, 'actividades_preguntas', 'lenguajeCodigo', "ALTER TABLE actividades_preguntas ADD COLUMN lenguajeCodigo VARCHAR(30) NOT NULL DEFAULT 'plaintext' AFTER codigoBase");
        self::agregarColumnaSiFalta($pdo, 'actividades_preguntas', 'variantesCodigo', "ALTER TABLE actividades_preguntas ADD COLUMN variantesCodigo TEXT NULL AFTER lenguajeCodigo");
        self::agregarColumnaSiFalta($pdo, 'actividades_preguntas', 'explicacionError', "ALTER TABLE actividades_preguntas ADD COLUMN explicacionError TEXT NULL AFTER pista");

        self::$tablasPreparadas = true;
    }

    private static function agregarColumnaSiFalta(PDO $pdo, $tabla, $columna, $sql)
    {
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM $tabla LIKE " . $pdo->quote($columna));
            if (!$stmt || !$stmt->fetch(PDO::FETCH_ASSOC)) {
                $pdo->exec($sql);
            }
        } catch (Exception $e) {
            // Si la base no permite ALTER, el SQL versionado deja documentado el cambio requerido.
        }
    }

    private static function condicionesBusqueda($busqueda, $prefijo = 'busqueda')
    {
        $busqueda = trim((string) $busqueda);
        if ($busqueda === '') {
            return ['', []];
        }

        $like = '%' . $busqueda . '%';

        return [
            "(
                a.tituloActividad LIKE :{$prefijo}Titulo
                OR a.descripcionActividad LIKE :{$prefijo}Descripcion
                OR a.slug LIKE :{$prefijo}Slug
                OR COALESCE(s.tituloSeccion, '') LIKE :{$prefijo}Seccion
                OR COALESCE(c.nombreCurso, '') LIKE :{$prefijo}Curso
            )",
            [
                ":{$prefijo}Titulo" => $like,
                ":{$prefijo}Descripcion" => $like,
                ":{$prefijo}Slug" => $like,
                ":{$prefijo}Seccion" => $like,
                ":{$prefijo}Curso" => $like,
            ],
        ];
    }

    private static function condicionesTipo($tipo, $prefijo = 'tipoFiltro')
    {
        $tipo = trim((string) $tipo);
        if ($tipo === '') {
            return ['', []];
        }

        return [
            "a.tipoActividad = :{$prefijo}",
            [
                ":{$prefijo}" => $tipo,
            ],
        ];
    }

    private static function condicionesValorExacto($columna, $valor, $prefijo)
    {
        $valor = trim((string) $valor);
        if ($valor === '') {
            return ['', []];
        }

        return [
            "$columna = :{$prefijo}",
            [
                ":{$prefijo}" => $valor,
            ],
        ];
    }

    private static function condicionesBooleanas($columna, $valor, $prefijo)
    {
        if ($valor === '' || $valor === null) {
            return ['', []];
        }

        return [
            "$columna = :{$prefijo}",
            [
                ":{$prefijo}" => (int) $valor,
            ],
        ];
    }

    public static function mdlListarParaUsuario($idUsuario, $rol, $busqueda = '', $tipo = '', $visibilidad = '', $estado = '', $soloDestacadas = '')
    {
        self::prepararTablas();

        $rol = strtoupper((string) $rol);
        $pdo = Conexion::conectar();
        list($condicionBusqueda, $parametrosBusqueda) = self::condicionesBusqueda($busqueda);
        list($condicionTipo, $parametrosTipo) = self::condicionesTipo($tipo);
        list($condicionVisibilidad, $parametrosVisibilidad) = self::condicionesValorExacto('a.visibilidad', $visibilidad, 'visibilidadFiltro');
        list($condicionEstado, $parametrosEstado) = self::condicionesValorExacto('a.estadoActividad', $estado, 'estadoFiltro');
        list($condicionDestacada, $parametrosDestacada) = self::condicionesBooleanas('COALESCE(a.destacadaPublica, 0)', $soloDestacadas, 'destacadaFiltro');
        $parametrosComunes = array_merge($parametrosBusqueda, $parametrosTipo, $parametrosVisibilidad, $parametrosEstado, $parametrosDestacada);

        if ($rol === 'ADMINISTRADOR') {
            $sql = "
                SELECT a.*, s.tituloSeccion, c.nombreCurso, u.nombreUsuario, u.apellidoUsuario,
                       COALESCE(i.totalIntentos, 0) AS totalIntentos
                FROM actividades a
                LEFT JOIN secciones s ON s.idSeccion = a.id_seccion
                LEFT JOIN cursos c ON c.idCurso = a.id_curso
                LEFT JOIN usuarios u ON u.idUsuario = a.id_autor
                LEFT JOIN (
                    SELECT id_actividad, COUNT(*) AS totalIntentos
                    FROM actividades_intentos
                    GROUP BY id_actividad
                ) i ON i.id_actividad = a.idActividad
                WHERE COALESCE(a.esPlantilla, 0) = 0
            ";
            if ($condicionBusqueda !== '') {
                $sql .= " AND $condicionBusqueda";
            }
            if ($condicionTipo !== '') {
                $sql .= " AND $condicionTipo";
            }
            if ($condicionVisibilidad !== '') {
                $sql .= " AND $condicionVisibilidad";
            }
            if ($condicionEstado !== '') {
                $sql .= " AND $condicionEstado";
            }
            if ($condicionDestacada !== '') {
                $sql .= " AND $condicionDestacada";
            }
            $sql .= " ORDER BY a.fechaCreacion DESC";
            $stmt = $pdo->prepare($sql);
            foreach ($parametrosComunes as $clave => $valor) {
                $stmt->bindValue($clave, $valor, PDO::PARAM_STR);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($rol === 'DOCENTE') {
            $sql = "
                SELECT a.*, s.tituloSeccion, c.nombreCurso, u.nombreUsuario, u.apellidoUsuario,
                       COALESCE(i.totalIntentos, 0) AS totalIntentos
                FROM actividades a
                LEFT JOIN secciones s ON s.idSeccion = a.id_seccion
                LEFT JOIN cursos c ON c.idCurso = a.id_curso
                LEFT JOIN usuarios u ON u.idUsuario = a.id_autor
                LEFT JOIN (
                    SELECT id_actividad, COUNT(*) AS totalIntentos
                    FROM actividades_intentos
                    GROUP BY id_actividad
                ) i ON i.id_actividad = a.idActividad
                WHERE (
                    a.id_autor = :idUsuario
                    OR s.docente = :idUsuarioDocente
                    OR s.tutor = :idUsuarioTutor
                )
                  AND COALESCE(a.esPlantilla, 0) = 0
            ";
            if ($condicionBusqueda !== '') {
                $sql .= " AND $condicionBusqueda";
            }
            if ($condicionTipo !== '') {
                $sql .= " AND $condicionTipo";
            }
            if ($condicionVisibilidad !== '') {
                $sql .= " AND $condicionVisibilidad";
            }
            if ($condicionEstado !== '') {
                $sql .= " AND $condicionEstado";
            }
            if ($condicionDestacada !== '') {
                $sql .= " AND $condicionDestacada";
            }
            $sql .= " ORDER BY a.fechaCreacion DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':idUsuario', (int) $idUsuario, PDO::PARAM_INT);
            $stmt->bindValue(':idUsuarioDocente', (int) $idUsuario, PDO::PARAM_INT);
            $stmt->bindValue(':idUsuarioTutor', (int) $idUsuario, PDO::PARAM_INT);
            foreach ($parametrosComunes as $clave => $valor) {
                $stmt->bindValue($clave, $valor, PDO::PARAM_STR);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $sql = "
            SELECT DISTINCT a.*, s.tituloSeccion, c.nombreCurso, u.nombreUsuario, u.apellidoUsuario,
                   COALESCE(i.totalIntentos, 0) AS totalIntentos
            FROM actividades a
            LEFT JOIN secciones s ON s.idSeccion = a.id_seccion
            LEFT JOIN cursos c ON c.idCurso = a.id_curso
            LEFT JOIN usuarios u ON u.idUsuario = a.id_autor
            LEFT JOIN asignacioncursos ac ON ac.id_seccion = a.id_curso
            LEFT JOIN (
                SELECT id_actividad, COUNT(*) AS totalIntentos
                FROM actividades_intentos
                GROUP BY id_actividad
            ) i ON i.id_actividad = a.idActividad
            WHERE a.estadoActividad = 'PUBLICADA'
              AND COALESCE(a.esPlantilla, 0) = 0
              AND (
                a.visibilidad IN ('publica', 'oculta')
                OR (a.visibilidad = 'privada' AND ac.id_estudiante = :idUsuario)
              )
        ";
        if ($condicionBusqueda !== '') {
            $sql .= " AND $condicionBusqueda";
        }
        if ($condicionTipo !== '') {
            $sql .= " AND $condicionTipo";
        }
        if ($condicionVisibilidad !== '') {
            $sql .= " AND $condicionVisibilidad";
        }
        if ($condicionEstado !== '') {
            $sql .= " AND $condicionEstado";
        }
        if ($condicionDestacada !== '') {
            $sql .= " AND $condicionDestacada";
        }
        $sql .= " ORDER BY a.fechaCreacion DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':idUsuario', (int) $idUsuario, PDO::PARAM_INT);
        foreach ($parametrosComunes as $clave => $valor) {
            $stmt->bindValue($clave, $valor, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlListarBancoParaUsuario($idUsuario, $rol, $busqueda = '', $tipo = '', $alcance = '', $estado = '')
    {
        self::prepararTablas();

        $rol = strtoupper((string) $rol);
        $pdo = Conexion::conectar();
        list($condicionBusqueda, $parametrosBusqueda) = self::condicionesBusqueda($busqueda, 'bancoBusqueda');
        list($condicionTipo, $parametrosTipo) = self::condicionesTipo($tipo, 'bancoTipo');
        list($condicionAlcance, $parametrosAlcance) = self::condicionesValorExacto('a.alcancePlantilla', $alcance, 'bancoAlcance');
        list($condicionEstado, $parametrosEstado) = self::condicionesValorExacto('a.estadoActividad', $estado, 'bancoEstado');
        $parametrosComunes = array_merge($parametrosBusqueda, $parametrosTipo, $parametrosAlcance, $parametrosEstado);

        if ($rol === 'ESTUDIANTE') {
            return [];
        }

        if ($rol === 'ADMINISTRADOR') {
            $sql = "
                SELECT a.*, s.tituloSeccion, c.nombreCurso, u.nombreUsuario, u.apellidoUsuario
                FROM actividades a
                LEFT JOIN secciones s ON s.idSeccion = a.id_seccion
                LEFT JOIN cursos c ON c.idCurso = a.id_curso
                LEFT JOIN usuarios u ON u.idUsuario = a.id_autor
                WHERE COALESCE(a.esPlantilla, 0) = 1
            ";
            if ($condicionBusqueda !== '') {
                $sql .= " AND $condicionBusqueda";
            }
            if ($condicionTipo !== '') {
                $sql .= " AND $condicionTipo";
            }
            if ($condicionAlcance !== '') {
                $sql .= " AND $condicionAlcance";
            }
            if ($condicionEstado !== '') {
                $sql .= " AND $condicionEstado";
            }
            $sql .= " ORDER BY COALESCE(a.fechaActualizacion, a.fechaCreacion) DESC";
            $stmt = $pdo->prepare($sql);
            foreach ($parametrosComunes as $clave => $valor) {
                $stmt->bindValue($clave, $valor, PDO::PARAM_STR);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $sql = "
            SELECT a.*, s.tituloSeccion, c.nombreCurso, u.nombreUsuario, u.apellidoUsuario
            FROM actividades a
            LEFT JOIN secciones s ON s.idSeccion = a.id_seccion
            LEFT JOIN cursos c ON c.idCurso = a.id_curso
            LEFT JOIN usuarios u ON u.idUsuario = a.id_autor
            WHERE COALESCE(a.esPlantilla, 0) = 1
              AND (
                a.id_autor = :idUsuario
                OR s.docente = :idUsuarioDocente
                OR s.tutor = :idUsuarioTutor
              )
        ";
        if ($condicionBusqueda !== '') {
            $sql .= " AND $condicionBusqueda";
        }
        if ($condicionTipo !== '') {
            $sql .= " AND $condicionTipo";
        }
        if ($condicionAlcance !== '') {
            $sql .= " AND $condicionAlcance";
        }
        if ($condicionEstado !== '') {
            $sql .= " AND $condicionEstado";
        }
        $sql .= " ORDER BY COALESCE(a.fechaActualizacion, a.fechaCreacion) DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':idUsuario', (int) $idUsuario, PDO::PARAM_INT);
        $stmt->bindValue(':idUsuarioDocente', (int) $idUsuario, PDO::PARAM_INT);
        $stmt->bindValue(':idUsuarioTutor', (int) $idUsuario, PDO::PARAM_INT);
        foreach ($parametrosComunes as $clave => $valor) {
            $stmt->bindValue($clave, $valor, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlListarPublicas()
    {
        self::prepararTablas();

        $stmt = Conexion::conectar()->prepare("
            SELECT a.*, s.tituloSeccion, c.nombreCurso
            FROM actividades a
            LEFT JOIN secciones s ON s.idSeccion = a.id_seccion
            LEFT JOIN cursos c ON c.idCurso = a.id_curso
            WHERE a.estadoActividad = 'PUBLICADA'
              AND COALESCE(a.esPlantilla, 0) = 0
              AND a.visibilidad = 'publica'
            ORDER BY a.fechaCreacion DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlBuscarPorId($idActividad)
    {
        self::prepararTablas();

        $stmt = Conexion::conectar()->prepare("
            SELECT a.*, s.tituloSeccion, c.nombreCurso, s.docente, s.tutor
            FROM actividades a
            LEFT JOIN secciones s ON s.idSeccion = a.id_seccion
            LEFT JOIN cursos c ON c.idCurso = a.id_curso
            WHERE a.idActividad = :idActividad
            LIMIT 1
        ");
        $stmt->bindValue(':idActividad', (int) $idActividad, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function mdlBuscarPorSlug($slug)
    {
        self::prepararTablas();

        $stmt = Conexion::conectar()->prepare("
            SELECT a.*, s.tituloSeccion, c.nombreCurso, s.docente, s.tutor
            FROM actividades a
            LEFT JOIN secciones s ON s.idSeccion = a.id_seccion
            LEFT JOIN cursos c ON c.idCurso = a.id_curso
            WHERE a.slug = :slug
            LIMIT 1
        ");
        $stmt->bindValue(':slug', (string) $slug, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function mdlPreguntasConOpciones($idActividad)
    {
        self::prepararTablas();

        $stmt = Conexion::conectar()->prepare("
            SELECT *
            FROM actividades_preguntas
            WHERE id_actividad = :idActividad
            ORDER BY orden ASC, idPregunta ASC
        ");
        $stmt->bindValue(':idActividad', (int) $idActividad, PDO::PARAM_INT);
        $stmt->execute();
        $preguntas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($preguntas as &$pregunta) {
            $opciones = Conexion::conectar()->prepare("
                SELECT *
                FROM actividades_opciones
                WHERE id_pregunta = :idPregunta
                ORDER BY orden ASC, idOpcion ASC
            ");
            $opciones->bindValue(':idPregunta', (int) $pregunta['idPregunta'], PDO::PARAM_INT);
            $opciones->execute();
            $pregunta['opciones'] = $opciones->fetchAll(PDO::FETCH_ASSOC);
        }

        return $preguntas;
    }

    public static function mdlSlugExiste($slug, $excluirId = 0)
    {
        self::prepararTablas();

        $stmt = Conexion::conectar()->prepare("
            SELECT COUNT(*) AS total
            FROM actividades
            WHERE slug = :slug
              AND idActividad <> :excluirId
        ");
        $stmt->bindValue(':slug', (string) $slug, PDO::PARAM_STR);
        $stmt->bindValue(':excluirId', (int) $excluirId, PDO::PARAM_INT);
        $stmt->execute();
        return (int) (($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0)) > 0;
    }

    public static function mdlGuardarActividad($datos, $preguntas)
    {
        self::prepararTablas();

        $pdo = Conexion::conectar();
        $stmt = $pdo->prepare("
            INSERT INTO actividades (
                tituloActividad, slug, descripcionActividad, tipoActividad, visibilidad,
                estadoActividad, id_curso, id_seccion, id_autor, puntajeMaximo,
                intentosPermitidos, permiteVisitantes, esPlantilla, alcancePlantilla,
                destacadaPublica, id_actividad_origen,
                recursoExternoUrl, recursoExternoEmbed
            ) VALUES (
                :tituloActividad, :slug, :descripcionActividad, :tipoActividad, :visibilidad,
                :estadoActividad, :id_curso, :id_seccion, :id_autor, :puntajeMaximo,
                :intentosPermitidos, :permiteVisitantes, :esPlantilla, :alcancePlantilla,
                :destacadaPublica, :id_actividad_origen,
                :recursoExternoUrl, :recursoExternoEmbed
            )
        ");

        self::bindActividad($stmt, $datos, true);
        if (!$stmt->execute()) {
            return 'error';
        }

        $idActividad = (int) $pdo->lastInsertId();
        self::guardarPreguntas($idActividad, $preguntas);
        return $idActividad;
    }

    public static function mdlActualizarActividad($idActividad, $datos, $preguntas)
    {
        self::prepararTablas();

        $stmt = Conexion::conectar()->prepare("
            UPDATE actividades
            SET tituloActividad = :tituloActividad,
                slug = :slug,
                descripcionActividad = :descripcionActividad,
                tipoActividad = :tipoActividad,
                visibilidad = :visibilidad,
                estadoActividad = :estadoActividad,
                id_curso = :id_curso,
                id_seccion = :id_seccion,
                puntajeMaximo = :puntajeMaximo,
                intentosPermitidos = :intentosPermitidos,
                permiteVisitantes = :permiteVisitantes,
                esPlantilla = :esPlantilla,
                alcancePlantilla = :alcancePlantilla,
                destacadaPublica = :destacadaPublica,
                id_actividad_origen = :id_actividad_origen,
                recursoExternoUrl = :recursoExternoUrl,
                recursoExternoEmbed = :recursoExternoEmbed,
                fechaActualizacion = NOW()
            WHERE idActividad = :idActividad
        ");

        self::bindActividad($stmt, $datos, false);
        $stmt->bindValue(':idActividad', (int) $idActividad, PDO::PARAM_INT);

        if (!$stmt->execute()) {
            return 'error';
        }

        self::eliminarPreguntasActividad($idActividad);
        self::guardarPreguntas($idActividad, $preguntas);
        return 'ok';
    }

    private static function bindActividad(PDOStatement $stmt, array $datos, $incluirAutor)
    {
        $stmt->bindValue(':tituloActividad', $datos['tituloActividad'], PDO::PARAM_STR);
        $stmt->bindValue(':slug', $datos['slug'], PDO::PARAM_STR);
        $stmt->bindValue(':descripcionActividad', $datos['descripcionActividad'], PDO::PARAM_STR);
        $stmt->bindValue(':tipoActividad', $datos['tipoActividad'], PDO::PARAM_STR);
        $stmt->bindValue(':visibilidad', $datos['visibilidad'], PDO::PARAM_STR);
        $stmt->bindValue(':estadoActividad', $datos['estadoActividad'], PDO::PARAM_STR);
        $stmt->bindValue(':id_curso', $datos['id_curso'] ?: null, $datos['id_curso'] ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':id_seccion', $datos['id_seccion'] ?: null, $datos['id_seccion'] ? PDO::PARAM_INT : PDO::PARAM_NULL);
        if ($incluirAutor) {
            $stmt->bindValue(':id_autor', (int) $datos['id_autor'], PDO::PARAM_INT);
        }
        $stmt->bindValue(':puntajeMaximo', (string) $datos['puntajeMaximo'], PDO::PARAM_STR);
        $stmt->bindValue(':intentosPermitidos', (int) $datos['intentosPermitidos'], PDO::PARAM_INT);
        $stmt->bindValue(':permiteVisitantes', (int) $datos['permiteVisitantes'], PDO::PARAM_INT);
        $stmt->bindValue(':esPlantilla', (int) ($datos['esPlantilla'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':alcancePlantilla', (string) ($datos['alcancePlantilla'] ?? 'personal'), PDO::PARAM_STR);
        $stmt->bindValue(':destacadaPublica', (int) ($datos['destacadaPublica'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':id_actividad_origen', !empty($datos['id_actividad_origen']) ? (int) $datos['id_actividad_origen'] : null, !empty($datos['id_actividad_origen']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':recursoExternoUrl', $datos['recursoExternoUrl'], PDO::PARAM_STR);
        $stmt->bindValue(':recursoExternoEmbed', $datos['recursoExternoEmbed'], PDO::PARAM_STR);
    }

    public static function mdlActualizarMetadatosActividad($idActividad, array $cambios)
    {
        self::prepararTablas();

        $mapa = [
            'esPlantilla' => 'esPlantilla',
            'alcancePlantilla' => 'alcancePlantilla',
            'destacadaPublica' => 'destacadaPublica',
            'estadoActividad' => 'estadoActividad',
            'visibilidad' => 'visibilidad',
        ];

        $sets = [];
        $parametros = [':idActividad' => (int) $idActividad];
        foreach ($mapa as $clave => $columna) {
            if (!array_key_exists($clave, $cambios)) {
                continue;
            }
            $sets[] = "$columna = :$clave";
            $parametros[":$clave"] = $cambios[$clave];
        }

        if (empty($sets)) {
            return 'ok';
        }

        $sql = "UPDATE actividades SET " . implode(', ', $sets) . ", fechaActualizacion = NOW() WHERE idActividad = :idActividad";
        $stmt = Conexion::conectar()->prepare($sql);
        foreach ($parametros as $clave => $valor) {
            if (is_int($valor)) {
                $stmt->bindValue($clave, $valor, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($clave, $valor, PDO::PARAM_STR);
            }
        }

        return $stmt->execute() ? 'ok' : 'error';
    }

    private static function guardarPreguntas($idActividad, array $preguntas)
    {
        foreach ($preguntas as $orden => $pregunta) {
            $stmt = Conexion::conectar()->prepare("
                INSERT INTO actividades_preguntas (
                    id_actividad, tipoPregunta, textoPregunta, codigoBase, lenguajeCodigo, variantesCodigo, respuestaCorrecta, puntaje, orden, pista, explicacionError
                ) VALUES (
                    :id_actividad, :tipoPregunta, :textoPregunta, :codigoBase, :lenguajeCodigo, :variantesCodigo, :respuestaCorrecta, :puntaje, :orden, :pista, :explicacionError
                )
            ");
            $stmt->bindValue(':id_actividad', (int) $idActividad, PDO::PARAM_INT);
            $stmt->bindValue(':tipoPregunta', $pregunta['tipoPregunta'], PDO::PARAM_STR);
            $stmt->bindValue(':textoPregunta', $pregunta['textoPregunta'], PDO::PARAM_STR);
            $stmt->bindValue(':codigoBase', $pregunta['codigoBase'] ?? null, !empty($pregunta['codigoBase']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':lenguajeCodigo', $pregunta['lenguajeCodigo'] ?? 'plaintext', PDO::PARAM_STR);
            $stmt->bindValue(':variantesCodigo', $pregunta['variantesCodigo'] ?? null, !empty($pregunta['variantesCodigo']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':respuestaCorrecta', $pregunta['respuestaCorrecta'], PDO::PARAM_STR);
            $stmt->bindValue(':puntaje', (string) $pregunta['puntaje'], PDO::PARAM_STR);
            $stmt->bindValue(':orden', (int) ($orden + 1), PDO::PARAM_INT);
            $stmt->bindValue(':pista', $pregunta['pista'], PDO::PARAM_STR);
            $stmt->bindValue(':explicacionError', $pregunta['explicacionError'], PDO::PARAM_STR);
            $stmt->execute();

            $idPregunta = (int) Conexion::conectar()->lastInsertId();
            foreach (($pregunta['opciones'] ?? []) as $ordenOpcion => $opcion) {
                $stmtOpcion = Conexion::conectar()->prepare("
                    INSERT INTO actividades_opciones (id_pregunta, textoOpcion, esCorrecta, orden)
                    VALUES (:id_pregunta, :textoOpcion, :esCorrecta, :orden)
                ");
                $stmtOpcion->bindValue(':id_pregunta', $idPregunta, PDO::PARAM_INT);
                $stmtOpcion->bindValue(':textoOpcion', $opcion['textoOpcion'], PDO::PARAM_STR);
                $stmtOpcion->bindValue(':esCorrecta', (int) $opcion['esCorrecta'], PDO::PARAM_INT);
                $stmtOpcion->bindValue(':orden', (int) ($ordenOpcion + 1), PDO::PARAM_INT);
                $stmtOpcion->execute();
            }
        }
    }

    private static function eliminarPreguntasActividad($idActividad)
    {
        $stmtIds = Conexion::conectar()->prepare("
            SELECT idPregunta
            FROM actividades_preguntas
            WHERE id_actividad = :idActividad
        ");
        $stmtIds->bindValue(':idActividad', (int) $idActividad, PDO::PARAM_INT);
        $stmtIds->execute();
        $ids = array_map('intval', array_column($stmtIds->fetchAll(PDO::FETCH_ASSOC), 'idPregunta'));

        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmtOpciones = Conexion::conectar()->prepare("DELETE FROM actividades_opciones WHERE id_pregunta IN ($placeholders)");
            foreach ($ids as $index => $idPregunta) {
                $stmtOpciones->bindValue($index + 1, $idPregunta, PDO::PARAM_INT);
            }
            $stmtOpciones->execute();
        }

        $stmt = Conexion::conectar()->prepare("DELETE FROM actividades_preguntas WHERE id_actividad = :idActividad");
        $stmt->bindValue(':idActividad', (int) $idActividad, PDO::PARAM_INT);
        $stmt->execute();
    }

    public static function mdlEliminarActividad($idActividad)
    {
        self::prepararTablas();

        $pdo = Conexion::conectar();

        try {
            $stmt = $pdo->prepare("
                DELETE r
                FROM actividades_respuestas r
                INNER JOIN actividades_intentos i ON i.idIntento = r.id_intento
                WHERE i.id_actividad = :idActividad
            ");
            $stmt->bindValue(':idActividad', (int) $idActividad, PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $pdo->prepare("DELETE FROM actividades_intentos WHERE id_actividad = :idActividad");
            $stmt->bindValue(':idActividad', (int) $idActividad, PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $pdo->prepare("
                DELETE o
                FROM actividades_opciones o
                INNER JOIN actividades_preguntas p ON p.idPregunta = o.id_pregunta
                WHERE p.id_actividad = :idActividad
            ");
            $stmt->bindValue(':idActividad', (int) $idActividad, PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $pdo->prepare("DELETE FROM actividades_preguntas WHERE id_actividad = :idActividad");
            $stmt->bindValue(':idActividad', (int) $idActividad, PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $pdo->prepare("DELETE FROM actividades WHERE idActividad = :idActividad");
            $stmt->bindValue(':idActividad', (int) $idActividad, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() === 1 ? 'ok' : 'error';
        } catch (Throwable $e) {
            return 'error';
        }
    }

    public static function mdlRegistrarIntento($datosIntento, array $respuestas)
    {
        self::prepararTablas();

        $stmt = Conexion::conectar()->prepare("
            INSERT INTO actividades_intentos (
                id_actividad, id_usuario, nombreVisitante, emailVisitante, puntaje, estadoIntento, ipVisitante
            ) VALUES (
                :id_actividad, :id_usuario, :nombreVisitante, :emailVisitante, :puntaje, :estadoIntento, :ipVisitante
            )
        ");
        $stmt->bindValue(':id_actividad', (int) $datosIntento['id_actividad'], PDO::PARAM_INT);
        $stmt->bindValue(':id_usuario', $datosIntento['id_usuario'] ?: null, $datosIntento['id_usuario'] ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':nombreVisitante', $datosIntento['nombreVisitante'], PDO::PARAM_STR);
        $stmt->bindValue(':emailVisitante', $datosIntento['emailVisitante'], PDO::PARAM_STR);
        $stmt->bindValue(':puntaje', (string) $datosIntento['puntaje'], PDO::PARAM_STR);
        $stmt->bindValue(':estadoIntento', $datosIntento['estadoIntento'], PDO::PARAM_STR);
        $stmt->bindValue(':ipVisitante', $datosIntento['ipVisitante'], PDO::PARAM_STR);

        if (!$stmt->execute()) {
            return 0;
        }

        $idIntento = (int) Conexion::conectar()->lastInsertId();
        foreach ($respuestas as $respuesta) {
            $stmtRespuesta = Conexion::conectar()->prepare("
                INSERT INTO actividades_respuestas (
                    id_intento, id_pregunta, id_opcion, textoRespuesta, esCorrecta, puntajeObtenido
                ) VALUES (
                    :id_intento, :id_pregunta, :id_opcion, :textoRespuesta, :esCorrecta, :puntajeObtenido
                )
            ");
            $stmtRespuesta->bindValue(':id_intento', $idIntento, PDO::PARAM_INT);
            $stmtRespuesta->bindValue(':id_pregunta', (int) $respuesta['id_pregunta'], PDO::PARAM_INT);
            $stmtRespuesta->bindValue(':id_opcion', $respuesta['id_opcion'] ?: null, $respuesta['id_opcion'] ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmtRespuesta->bindValue(':textoRespuesta', $respuesta['textoRespuesta'], PDO::PARAM_STR);
            $stmtRespuesta->bindValue(':esCorrecta', (int) $respuesta['esCorrecta'], PDO::PARAM_INT);
            $stmtRespuesta->bindValue(':puntajeObtenido', (string) $respuesta['puntajeObtenido'], PDO::PARAM_STR);
            $stmtRespuesta->execute();
        }

        return $idIntento;
    }

    public static function mdlContarIntentosUsuario($idActividad, $idUsuario)
    {
        self::prepararTablas();

        $stmt = Conexion::conectar()->prepare("
            SELECT COUNT(*) AS total
            FROM actividades_intentos
            WHERE id_actividad = :idActividad
              AND id_usuario = :idUsuario
        ");
        $stmt->bindValue(':idActividad', (int) $idActividad, PDO::PARAM_INT);
        $stmt->bindValue(':idUsuario', (int) $idUsuario, PDO::PARAM_INT);
        $stmt->execute();
        return (int) (($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0));
    }

    public static function mdlIntentosActividad($idActividad)
    {
        self::prepararTablas();

        $stmt = Conexion::conectar()->prepare("
            SELECT i.*, u.nombreUsuario, u.apellidoUsuario, u.email
            FROM actividades_intentos i
            LEFT JOIN usuarios u ON u.idUsuario = i.id_usuario
            WHERE i.id_actividad = :idActividad
            ORDER BY i.fechaEntrega DESC
        ");
        $stmt->bindValue(':idActividad', (int) $idActividad, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
