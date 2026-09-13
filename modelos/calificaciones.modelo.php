<?php
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/tenant.modelo.php';

class ModeloCalificaciones
{
    private static $tablasEvaluacionesPreparadas = false;
    private static $tablaCalificacionesPreparada = false;

    private static function prepararTablaCalificaciones()
    {
        if (self::$tablaCalificacionesPreparada) {
            return;
        }
        if (ModeloTenant::activo()) {
            self::$tablaCalificacionesPreparada = true;
            return;
        }
        $pdo = Conexion::conectar();
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM calificaciones LIKE 'fechaCalificacion'");
            if (!$stmt || !$stmt->fetch(PDO::FETCH_ASSOC)) {
                $pdo->exec('ALTER TABLE calificaciones ADD COLUMN fechaCalificacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER devolucion');
            }
            $stmt = $pdo->query("SHOW COLUMNS FROM calificaciones LIKE 'fechaActualizacion'");
            if (!$stmt || !$stmt->fetch(PDO::FETCH_ASSOC)) {
                $pdo->exec('ALTER TABLE calificaciones ADD COLUMN fechaActualizacion DATETIME NULL AFTER fechaCalificacion');
            }
        } catch (Exception $e) {
            // La migración versionada permite preparar instalaciones sin permisos DDL desde PHP.
        }
        self::$tablaCalificacionesPreparada = true;
    }

    private static function prepararTablasEvaluaciones()
    {
        if (self::$tablasEvaluacionesPreparadas) {
            return;
        }

        if (ModeloTenant::activo()) {
            self::$tablasEvaluacionesPreparadas = true;
            return;
        }

        $pdo = Conexion::conectar();
        $pdo->exec("CREATE TABLE IF NOT EXISTS ciclos_lectivos (idCicloLectivo INT NOT NULL AUTO_INCREMENT, nombre VARCHAR(80) NOT NULL, anio SMALLINT NOT NULL, fechaInicio DATE NULL, fechaFin DATE NULL, activo TINYINT(1) NOT NULL DEFAULT 1, PRIMARY KEY (idCicloLectivo), UNIQUE KEY uq_ciclo_anio (anio)) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE TABLE IF NOT EXISTS periodos_calificacion (idPeriodo INT NOT NULL AUTO_INCREMENT, id_ciclo INT NOT NULL, nombre VARCHAR(100) NOT NULL, tipo VARCHAR(30) NOT NULL DEFAULT 'REGULAR', orden INT NOT NULL DEFAULT 1, fechaInicio DATE NULL, fechaFin DATE NULL, estado VARCHAR(15) NOT NULL DEFAULT 'ABIERTO', fechaCierre DATETIME NULL, cerradoPor INT NULL, fechaReapertura DATETIME NULL, reabiertoPor INT NULL, motivoReapertura VARCHAR(255) NULL, PRIMARY KEY (idPeriodo), UNIQUE KEY uq_periodo_ciclo_nombre (id_ciclo, nombre)) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE TABLE IF NOT EXISTS instrumentos_evaluacion (idInstrumento INT NOT NULL AUTO_INCREMENT, nombre VARCHAR(100) NOT NULL, activo TINYINT(1) NOT NULL DEFAULT 1, orden INT NOT NULL DEFAULT 1, PRIMARY KEY (idInstrumento), UNIQUE KEY uq_instrumento_nombre (nombre)) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE TABLE IF NOT EXISTS cierres_periodo_calificaciones (idCierrePeriodo INT NOT NULL AUTO_INCREMENT, id_periodo INT NOT NULL, id_seccion INT NOT NULL, id_estudiante INT NOT NULL, promedioCalculado DECIMAL(5,2) NULL, calificacionCierre DECIMAL(5,2) NULL, confirmada TINYINT(1) NOT NULL DEFAULT 0, fechaActualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, actualizadoPor INT NULL, PRIMARY KEY (idCierrePeriodo), UNIQUE KEY uq_cierre_periodo_estudiante (id_periodo,id_seccion,id_estudiante), KEY idx_cierre_periodo_seccion (id_periodo,id_seccion)) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE TABLE IF NOT EXISTS periodos_seccion_estado (idPeriodoSeccion INT NOT NULL AUTO_INCREMENT,id_periodo INT NOT NULL,id_seccion INT NOT NULL,estado VARCHAR(15) NOT NULL DEFAULT 'ABIERTO',fechaCierre DATETIME NULL,cerradoPor INT NULL,fechaReapertura DATETIME NULL,reabiertoPor INT NULL,motivoReapertura VARCHAR(255) NULL,PRIMARY KEY(idPeriodoSeccion),UNIQUE KEY uq_periodo_seccion(id_periodo,id_seccion)) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("INSERT IGNORE INTO periodos_seccion_estado(id_periodo,id_seccion,estado,fechaCierre,cerradoPor,fechaReapertura,reabiertoPor,motivoReapertura) SELECT relaciones.id_periodo,relaciones.id_seccion,p.estado,p.fechaCierre,p.cerradoPor,p.fechaReapertura,p.reabiertoPor,p.motivoReapertura FROM (SELECT id_periodo,id_seccion FROM evaluaciones WHERE id_periodo IS NOT NULL UNION SELECT id_periodo,id_seccion FROM cierres_periodo_calificaciones) relaciones INNER JOIN periodos_calificacion p ON p.idPeriodo=relaciones.id_periodo");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS evaluaciones (
                idEvaluacion INT NOT NULL AUTO_INCREMENT,
                id_seccion INT NOT NULL,
                id_curso INT NOT NULL,
                id_autor INT NOT NULL,
                temaEvaluacion VARCHAR(180) NOT NULL,
                fechaEvaluacion DATE NOT NULL,
                fechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (idEvaluacion),
                KEY idx_evaluaciones_seccion (id_seccion, fechaEvaluacion),
                KEY idx_evaluaciones_autor (id_autor)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS evaluaciones_calificaciones (
                idEvaluacionCalificacion INT NOT NULL AUTO_INCREMENT,
                id_evaluacion INT NOT NULL,
                id_estudiante INT NOT NULL,
                calificacion DECIMAL(5,2) NOT NULL,
                devolucion TEXT NULL,
                fechaActualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (idEvaluacionCalificacion),
                UNIQUE KEY uq_evaluacion_estudiante (id_evaluacion, id_estudiante),
                KEY idx_evaluaciones_calificaciones_estudiante (id_estudiante)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        foreach (['ALTER TABLE cursos ADD COLUMN id_ciclo_lectivo INT NULL AFTER responsable',"ALTER TABLE cursos ADD COLUMN modalidadCalificacion VARCHAR(20) NOT NULL DEFAULT 'DOS_TRAMOS'","ALTER TABLE cursos ADD COLUMN intensificacionActiva TINYINT(1) NOT NULL DEFAULT 1",'ALTER TABLE evaluaciones ADD COLUMN id_periodo INT NULL AFTER id_curso','ALTER TABLE evaluaciones ADD COLUMN id_instrumento INT NULL AFTER id_periodo',"ALTER TABLE evaluaciones_calificaciones ADD COLUMN estadoAsistencia VARCHAR(15) NOT NULL DEFAULT 'PRESENTE' AFTER calificacion"] as $alter) {
            try { $pdo->exec($alter); } catch (PDOException $e) { if ((int)($e->errorInfo[1] ?? 0) !== 1060) { throw $e; } }
        }
        try { $pdo->exec('ALTER TABLE evaluaciones_calificaciones MODIFY calificacion DECIMAL(5,2) NULL'); } catch (PDOException $e) {}
        foreach (['Examen escrito','Lección oral','Concepto','Trabajo práctico','Carpeta','Acreditación de aprendizaje prioritario','Integradora'] as $orden=>$nombre) {
            $pdo->prepare('INSERT IGNORE INTO instrumentos_evaluacion (nombre,orden) VALUES (?,?)')->execute([$nombre,$orden+1]);
        }
        self::$tablasEvaluacionesPreparadas = true;
    }

    public static function mdlContextoAcademicoSeccion($idSeccion)
    {
        self::prepararTablasEvaluaciones();
        $pdo=Conexion::conectar();
        $stmt=$pdo->prepare('SELECT c.idCurso,c.id_ciclo_lectivo,c.fechaInicioCurso,c.modalidadCalificacion,c.intensificacionActiva FROM secciones s INNER JOIN cursos c ON c.idCurso=s.id_curso WHERE s.idSeccion=?');
        $stmt->execute([(int)$idSeccion]); $curso=$stmt->fetch(PDO::FETCH_ASSOC);
        if(!$curso){return ['ciclo'=>null,'periodos'=>[],'instrumentos'=>[]];}
        $anio=(int)substr((string)($curso['fechaInicioCurso']??''),0,4); if($anio<2000){$anio=(int)date('Y');}
        $pdo->prepare('INSERT IGNORE INTO ciclos_lectivos (nombre,anio) VALUES (?,?)')->execute(['Ciclo lectivo '.$anio,$anio]);
        $q=$pdo->prepare('SELECT * FROM ciclos_lectivos WHERE anio=? LIMIT 1'); $q->execute([$anio]); $ciclo=$q->fetch(PDO::FETCH_ASSOC);
        $idCiclo=(int)$ciclo['idCicloLectivo'];
        $pdo->prepare('UPDATE cursos SET id_ciclo_lectivo=? WHERE idCurso=?')->execute([$idCiclo,(int)$curso['idCurso']]);
        foreach([['Primer período','REGULAR'],['Segundo período','REGULAR'],['Período único','REGULAR'],['Calificación final','FINAL'],['Intensificación','INTENSIFICACION']] as $orden=>$periodo){
            $pdo->prepare('INSERT IGNORE INTO periodos_calificacion (id_ciclo,nombre,tipo,orden) VALUES (?,?,?,?)')->execute([$idCiclo,$periodo[0],$periodo[1],$orden+1]);
        }
        $nombres=strtoupper((string)($curso['modalidadCalificacion']??'DOS_TRAMOS'))==='UNICO'?['Período único','Calificación final']:['Primer período','Segundo período','Calificación final'];
        $qInt=$pdo->prepare("SELECT COUNT(*) FROM periodos_calificacion p LEFT JOIN evaluaciones e ON e.id_periodo=p.idPeriodo AND e.id_seccion=? LEFT JOIN cierres_periodo_calificaciones cp ON cp.id_periodo=p.idPeriodo AND cp.id_seccion=? WHERE p.id_ciclo=? AND p.tipo='INTENSIFICACION' AND (e.idEvaluacion IS NOT NULL OR cp.idCierrePeriodo IS NOT NULL)");$qInt->execute([(int)$idSeccion,(int)$idSeccion,$idCiclo]);
        if(!empty($curso['intensificacionActiva'])||(int)$qInt->fetchColumn()>0){$nombres[]='Intensificación';}
        $marcas=implode(',',array_fill(0,count($nombres),'?'));$q=$pdo->prepare("SELECT p.*,COALESCE(pe.estado,'ABIERTO') estado FROM periodos_calificacion p LEFT JOIN periodos_seccion_estado pe ON pe.id_periodo=p.idPeriodo AND pe.id_seccion=? WHERE p.id_ciclo=? AND p.nombre IN ($marcas) ORDER BY FIELD(p.nombre,'Primer período','Segundo período','Período único','Calificación final','Intensificación'),p.idPeriodo");$q->execute(array_merge([(int)$idSeccion,$idCiclo],$nombres));$periodos=$q->fetchAll(PDO::FETCH_ASSOC);
        if($periodos){$pdo->prepare('UPDATE evaluaciones SET id_periodo=? WHERE id_curso=? AND id_periodo IS NULL')->execute([(int)$periodos[0]['idPeriodo'],(int)$curso['idCurso']]);}
        return ['ciclo'=>$ciclo,'periodos'=>$periodos,'instrumentos'=>$pdo->query('SELECT * FROM instrumentos_evaluacion WHERE activo=1 ORDER BY orden,nombre')->fetchAll(PDO::FETCH_ASSOC)];
    }

    public static function mdlPeriodoPorId($idPeriodo,$idSeccion=0)
    {
        self::prepararTablasEvaluaciones(); $stmt=Conexion::conectar()->prepare("SELECT p.*,COALESCE(pe.estado,'ABIERTO') estado FROM periodos_calificacion p LEFT JOIN periodos_seccion_estado pe ON pe.id_periodo=p.idPeriodo AND pe.id_seccion=? WHERE p.idPeriodo=? LIMIT 1");
        $stmt->execute([(int)$idSeccion,(int)$idPeriodo]); return $stmt->fetch(PDO::FETCH_ASSOC)?:null;
    }

    public static function mdlCambiarEstadoPeriodo($idPeriodo,$idSeccion,$estado,$idUsuario,$motivo='')
    {
        $cerrar=strtoupper((string)$estado)==='CERRADO';
        $sql=$cerrar?'INSERT INTO periodos_seccion_estado(id_periodo,id_seccion,estado,fechaCierre,cerradoPor) VALUES(?, ?,"CERRADO",NOW(),?) ON DUPLICATE KEY UPDATE estado="CERRADO",fechaCierre=NOW(),cerradoPor=VALUES(cerradoPor)':'INSERT INTO periodos_seccion_estado(id_periodo,id_seccion,estado,fechaReapertura,reabiertoPor,motivoReapertura) VALUES(?, ?,"ABIERTO",NOW(),?,?) ON DUPLICATE KEY UPDATE estado="ABIERTO",fechaReapertura=NOW(),reabiertoPor=VALUES(reabiertoPor),motivoReapertura=VALUES(motivoReapertura)';
        $stmt=Conexion::conectar()->prepare($sql); return $stmt->execute($cerrar?[(int)$idPeriodo,(int)$idSeccion,(int)$idUsuario]:[(int)$idPeriodo,(int)$idSeccion,(int)$idUsuario,trim((string)$motivo)])?'ok':'error';
    }

    public static function mdlCalcularCierresPeriodo($idPeriodo,$idSeccion,$idUsuario)
    {
        self::prepararTablasEvaluaciones(); $pdo=Conexion::conectar();
        $stmt=$pdo->prepare("SELECT ec.id_estudiante,ROUND(AVG(ec.calificacion),2) promedio
            FROM evaluaciones e INNER JOIN evaluaciones_calificaciones ec ON ec.id_evaluacion=e.idEvaluacion
            WHERE e.id_periodo=? AND e.id_seccion=? AND ec.estadoAsistencia='PRESENTE' AND ec.calificacion IS NOT NULL
            GROUP BY ec.id_estudiante");
        $stmt->execute([(int)$idPeriodo,(int)$idSeccion]); $promedios=$stmt->fetchAll(PDO::FETCH_ASSOC);
        $guardar=$pdo->prepare('INSERT INTO cierres_periodo_calificaciones (id_periodo,id_seccion,id_estudiante,promedioCalculado,calificacionCierre,actualizadoPor) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE promedioCalculado=VALUES(promedioCalculado),calificacionCierre=IF(confirmada=1,calificacionCierre,VALUES(calificacionCierre)),fechaActualizacion=NOW(),actualizadoPor=VALUES(actualizadoPor)');
        foreach($promedios as $fila){$guardar->execute([(int)$idPeriodo,(int)$idSeccion,(int)$fila['id_estudiante'],$fila['promedio'],$fila['promedio'],(int)$idUsuario]);}
        return count($promedios);
    }

    public static function mdlCierresPeriodo($idPeriodo,$idSeccion)
    {
        self::prepararTablasEvaluaciones(); $stmt=Conexion::conectar()->prepare('SELECT cp.*,u.nombreUsuario,u.apellidoUsuario FROM cierres_periodo_calificaciones cp INNER JOIN usuarios u ON u.idUsuario=cp.id_estudiante WHERE cp.id_periodo=? AND cp.id_seccion=? ORDER BY u.apellidoUsuario,u.nombreUsuario');
        $stmt->execute([(int)$idPeriodo,(int)$idSeccion]); return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlCierresEstudianteSeccion($idSeccion,$idEstudiante)
    {
        self::prepararTablasEvaluaciones(); $stmt=Conexion::conectar()->prepare('SELECT cp.*,p.nombre AS nombrePeriodo,p.estado AS estadoPeriodo FROM cierres_periodo_calificaciones cp INNER JOIN periodos_calificacion p ON p.idPeriodo=cp.id_periodo WHERE cp.id_seccion=? AND cp.id_estudiante=? ORDER BY p.orden,p.idPeriodo');
        $stmt->execute([(int)$idSeccion,(int)$idEstudiante]); return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlGuardarCierresPeriodo($idPeriodo,$idSeccion,array $notas,$idUsuario)
    {
        self::prepararTablasEvaluaciones(); $stmt=Conexion::conectar()->prepare('UPDATE cierres_periodo_calificaciones SET calificacionCierre=?,confirmada=1,fechaActualizacion=NOW(),actualizadoPor=? WHERE id_periodo=? AND id_seccion=? AND id_estudiante=?');
        foreach($notas as $idEstudiante=>$nota){if(!$stmt->execute([(float)$nota,(int)$idUsuario,(int)$idPeriodo,(int)$idSeccion,(int)$idEstudiante])){return 'error';}}
        return 'ok';
    }

    public static function mdlGuardarCalificacion($datos)
    {
        self::prepararTablaCalificaciones();
        ModeloTenant::exigirCalificacion($datos);
        $stmt = Conexion::conectar()->prepare(
            'INSERT INTO calificaciones (id_estudiante, id_seccion, id_modulo, id_curso, calificacion, devolucion)
             SELECT :id_estudiante, :id_seccion, :id_modulo, :id_curso, :calificacion, :devolucion
             WHERE ' . ModeloTenant::escrituraCalificacion($datos) . '
             ON DUPLICATE KEY UPDATE
                id_curso = VALUES(id_curso),
                calificacion = VALUES(calificacion),
                devolucion = VALUES(devolucion),
                fechaActualizacion = NOW()'
        );
        $stmt->bindValue(':id_estudiante', (int) $datos['id_estudiante'], PDO::PARAM_INT);
        $stmt->bindValue(':id_seccion', (int) $datos['id_seccion'], PDO::PARAM_INT);
        $stmt->bindValue(':id_modulo', (int) $datos['id_modulo'], PDO::PARAM_INT);
        $stmt->bindValue(':id_curso', (int) $datos['id_curso'], PDO::PARAM_INT);
        $stmt->bindValue(':calificacion', (int) $datos['calificacion'], PDO::PARAM_INT);
        $stmt->bindValue(':devolucion', (string) ($datos['devolucion'] ?? ''), PDO::PARAM_STR);
        return $stmt->execute() ? 'ok' : 'error';
    }

    public static function mdlGuardarCalificaciones(array $calificaciones)
    {
        self::prepararTablaCalificaciones();
        if (empty($calificaciones)) {
            return 'error';
        }
        if (ModeloTenant::activo()) {
            foreach ($calificaciones as $calificacion) {
                if (self::mdlGuardarCalificacion($calificacion) !== 'ok') { return 'error'; }
            }
            return 'ok';
        }

        $valores = [];
        $params = [];

        foreach (array_values($calificaciones) as $indice => $calificacion) {
            $valores[] = "(:id_estudiante_{$indice}, :id_seccion_{$indice}, :id_modulo_{$indice}, :id_curso_{$indice}, :calificacion_{$indice}, :devolucion_{$indice})";
            $params[":id_estudiante_{$indice}"] = (int) $calificacion['id_estudiante'];
            $params[":id_seccion_{$indice}"] = (int) $calificacion['id_seccion'];
            $params[":id_modulo_{$indice}"] = (int) $calificacion['id_modulo'];
            $params[":id_curso_{$indice}"] = (int) $calificacion['id_curso'];
            $params[":calificacion_{$indice}"] = (int) $calificacion['calificacion'];
            $params[":devolucion_{$indice}"] = (string) ($calificacion['devolucion'] ?? '');
        }

        $stmt = Conexion::conectar()->prepare(
            'INSERT INTO calificaciones
                (id_estudiante, id_seccion, id_modulo, id_curso, calificacion, devolucion)
             VALUES ' . implode(', ', $valores) . '
             ON DUPLICATE KEY UPDATE
                id_curso = VALUES(id_curso),
                calificacion = VALUES(calificacion),
                devolucion = VALUES(devolucion),
                fechaActualizacion = NOW()'
        );

        return $stmt->execute($params) ? 'ok' : 'error';
    }

    public static function mdlCalificacionesPorSeccion($idSeccion)
    {
        ModeloTenant::exigirSeccion($idSeccion);
        $stmt = Conexion::conectar()->prepare(
            'SELECT c.idCalificacion, c.id_estudiante, c.id_seccion, c.id_modulo, c.id_curso, c.calificacion,
                    c.devolucion,
                    l.nombreLeccion, l.tipoLeccion,
                    u.nombreUsuario, u.apellidoUsuario, u.email
             FROM calificaciones c
             INNER JOIN usuarios u ON u.idUsuario = c.id_estudiante
             LEFT JOIN lecciones l ON l.idLeccion = c.id_modulo
             WHERE c.id_seccion = :idSeccion AND ' . ModeloTenant::calificaciones('c') . '
             ORDER BY c.idCalificacion DESC'
        );
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlCalificacionesGenerales($idEstudiante = 0, $idDocente = 0)
    {
        self::prepararTablaCalificaciones();
        self::prepararTablasEvaluaciones();

        $pdo = Conexion::conectar();
        $filtroTarea = (int) $idEstudiante > 0
            ? ' WHERE c.id_estudiante = :idEstudiante'
            : ((int) $idDocente > 0 ? ' WHERE (s.docente = :idDocente OR s.tutor = :idDocente OR curso.responsable = :idDocente)' : '');
        $filtroEvaluacion = (int) $idEstudiante > 0
            ? ' WHERE ec.id_estudiante = :idEstudiante'
            : ((int) $idDocente > 0 ? ' WHERE (s.docente = :idDocente OR s.tutor = :idDocente OR curso.responsable = :idDocente)' : '');

        $stmtTareas = $pdo->prepare("
            SELECT COALESCE(c.fechaActualizacion, c.fechaCalificacion) AS fecha,
                   curso.nombreCurso AS curso, s.tituloSeccion AS materia,
                   l.nombreLeccion AS actividad, 'Actividad' AS origen,
                   c.calificacion AS nota, c.id_estudiante,
                   u.nombreUsuario, u.apellidoUsuario,
                   CONCAT(docente.nombreUsuario, ' ', docente.apellidoUsuario) AS docente
            FROM calificaciones c
            INNER JOIN cursos curso ON curso.idCurso = c.id_curso
            INNER JOIN secciones s ON s.idSeccion = c.id_seccion
            LEFT JOIN lecciones l ON l.idLeccion = c.id_modulo
            INNER JOIN usuarios u ON u.idUsuario = c.id_estudiante
            LEFT JOIN usuarios docente ON docente.idUsuario = s.docente
            {$filtroTarea}
        ");
        if ((int) $idEstudiante > 0) {
            $stmtTareas->bindValue(':idEstudiante', (int) $idEstudiante, PDO::PARAM_INT);
        } elseif ((int) $idDocente > 0) {
            $stmtTareas->bindValue(':idDocente', (int) $idDocente, PDO::PARAM_INT);
        }
        $stmtTareas->execute();
        $calificaciones = $stmtTareas->fetchAll(PDO::FETCH_ASSOC);

        $stmtEvaluaciones = $pdo->prepare("
            SELECT COALESCE(ec.fechaActualizacion, e.fechaEvaluacion) AS fecha,
                   curso.nombreCurso AS curso, s.tituloSeccion AS materia,
                   e.temaEvaluacion AS actividad, 'Evaluación' AS origen,
                   ec.calificacion AS nota, ec.id_estudiante,
                   u.nombreUsuario, u.apellidoUsuario,
                   CONCAT(docente.nombreUsuario, ' ', docente.apellidoUsuario) AS docente
            FROM evaluaciones_calificaciones ec
            INNER JOIN evaluaciones e ON e.idEvaluacion = ec.id_evaluacion
            INNER JOIN cursos curso ON curso.idCurso = e.id_curso
            INNER JOIN secciones s ON s.idSeccion = e.id_seccion
            INNER JOIN usuarios u ON u.idUsuario = ec.id_estudiante
            LEFT JOIN usuarios docente ON docente.idUsuario = s.docente
            {$filtroEvaluacion}
        ");
        if ((int) $idEstudiante > 0) {
            $stmtEvaluaciones->bindValue(':idEstudiante', (int) $idEstudiante, PDO::PARAM_INT);
        } elseif ((int) $idDocente > 0) {
            $stmtEvaluaciones->bindValue(':idDocente', (int) $idDocente, PDO::PARAM_INT);
        }
        $stmtEvaluaciones->execute();
        $calificaciones = array_merge($calificaciones, $stmtEvaluaciones->fetchAll(PDO::FETCH_ASSOC));

        $filtroCierre = (int) $idEstudiante > 0
            ? ' WHERE cp.id_estudiante = :idEstudiante'
            : ((int) $idDocente > 0 ? ' WHERE (s.docente = :idDocente OR s.tutor = :idDocente OR curso.responsable = :idDocente)' : '');
        $stmtCierres = $pdo->prepare("
            SELECT cp.fechaActualizacion AS fecha, curso.nombreCurso AS curso, s.tituloSeccion AS materia,
                   CONCAT('Cierre - ', p.nombre) AS actividad, 'Cierre de período' AS origen,
                   cp.calificacionCierre AS nota, cp.id_estudiante, u.nombreUsuario, u.apellidoUsuario,
                   CONCAT(docente.nombreUsuario, ' ', docente.apellidoUsuario) AS docente
            FROM cierres_periodo_calificaciones cp
            INNER JOIN periodos_calificacion p ON p.idPeriodo = cp.id_periodo
            INNER JOIN secciones s ON s.idSeccion = cp.id_seccion
            INNER JOIN cursos curso ON curso.idCurso = s.id_curso
            INNER JOIN usuarios u ON u.idUsuario = cp.id_estudiante
            LEFT JOIN usuarios docente ON docente.idUsuario = s.docente
            {$filtroCierre}
        ");
        if ((int) $idEstudiante > 0) {
            $stmtCierres->bindValue(':idEstudiante', (int) $idEstudiante, PDO::PARAM_INT);
        } elseif ((int) $idDocente > 0) {
            $stmtCierres->bindValue(':idDocente', (int) $idDocente, PDO::PARAM_INT);
        }
        $stmtCierres->execute();
        $calificaciones = array_merge($calificaciones, $stmtCierres->fetchAll(PDO::FETCH_ASSOC));

        usort($calificaciones, static function (array $primera, array $segunda) {
            $fechaPrimera = strtotime((string) ($primera['fecha'] ?? '')) ?: 0;
            $fechaSegunda = strtotime((string) ($segunda['fecha'] ?? '')) ?: 0;
            if ($fechaPrimera !== $fechaSegunda) {
                return $fechaSegunda <=> $fechaPrimera;
            }

            return strcasecmp(
                implode(' ', [$primera['curso'] ?? '', $primera['materia'] ?? '', $primera['actividad'] ?? '']),
                implode(' ', [$segunda['curso'] ?? '', $segunda['materia'] ?? '', $segunda['actividad'] ?? ''])
            );
        });

        return $calificaciones;
    }

    public static function mdlResumenCierresGenerales($idEstudiante = 0, $idDocente = 0)
    {
        self::prepararTablasEvaluaciones();
        $filtros = [];
        if ((int) $idEstudiante > 0) { $filtros[] = 'cp.id_estudiante = :idEstudiante'; }
        if ((int) $idDocente > 0) { $filtros[] = '(s.docente = :idDocente OR s.tutor = :idDocente OR c.responsable = :idDocente)'; }
        $sql = 'SELECT cp.id_estudiante,cp.id_seccion,cp.calificacionCierre,cp.promedioCalculado,cp.confirmada,
                       cp.fechaActualizacion,p.idPeriodo,p.nombre AS periodo,p.tipo AS tipoPeriodo,p.orden AS ordenPeriodo,
                       cl.idCicloLectivo,cl.nombre AS ciclo,cl.anio,c.idCurso,c.nombreCurso AS curso,
                       s.tituloSeccion AS materia,u.nombreUsuario,u.apellidoUsuario,
                       CONCAT(d.nombreUsuario," ",d.apellidoUsuario) AS docente
                FROM cierres_periodo_calificaciones cp
                INNER JOIN periodos_calificacion p ON p.idPeriodo=cp.id_periodo
                INNER JOIN ciclos_lectivos cl ON cl.idCicloLectivo=p.id_ciclo
                INNER JOIN secciones s ON s.idSeccion=cp.id_seccion
                INNER JOIN cursos c ON c.idCurso=s.id_curso
                INNER JOIN usuarios u ON u.idUsuario=cp.id_estudiante
                LEFT JOIN usuarios d ON d.idUsuario=s.docente'
                . ($filtros ? ' WHERE '.implode(' AND ',$filtros) : '')
                . ' ORDER BY cl.anio DESC,c.nombreCurso,s.tituloSeccion,u.apellidoUsuario,u.nombreUsuario,p.orden';
        $stmt=Conexion::conectar()->prepare($sql);
        if ((int)$idEstudiante>0) { $stmt->bindValue(':idEstudiante',(int)$idEstudiante,PDO::PARAM_INT); }
        if ((int)$idDocente>0) { $stmt->bindValue(':idDocente',(int)$idDocente,PDO::PARAM_INT); }
        $stmt->execute(); return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlSeccionesParaCalificaciones($idDocente = 0)
    {
        self::prepararTablasEvaluaciones();
        $filtro=(int)$idDocente>0?' WHERE (s.docente=:idDocente OR s.tutor=:idDocente OR c.responsable=:idDocente)':'';
        $stmt=Conexion::conectar()->prepare('SELECT s.idSeccion,s.tituloSeccion AS materia,c.idCurso,c.nombreCurso AS curso,
                    cl.anio,CONCAT(d.nombreUsuario," ",d.apellidoUsuario) AS docente,
                    COUNT(DISTINCT e.idEvaluacion) AS evaluaciones,COUNT(DISTINCT cp.idCierrePeriodo) AS cierres
                FROM secciones s INNER JOIN cursos c ON c.idCurso=s.id_curso
                LEFT JOIN ciclos_lectivos cl ON cl.idCicloLectivo=c.id_ciclo_lectivo
                LEFT JOIN usuarios d ON d.idUsuario=s.docente
                LEFT JOIN evaluaciones e ON e.id_seccion=s.idSeccion
                LEFT JOIN cierres_periodo_calificaciones cp ON cp.id_seccion=s.idSeccion'
                .$filtro.' GROUP BY s.idSeccion,s.tituloSeccion,c.idCurso,c.nombreCurso,cl.anio,d.nombreUsuario,d.apellidoUsuario
                HAVING evaluaciones>0 OR cierres>0 ORDER BY c.nombreCurso,s.tituloSeccion');
        if((int)$idDocente>0){$stmt->bindValue(':idDocente',(int)$idDocente,PDO::PARAM_INT);}
        $stmt->execute(); return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlCalificacionesPorEstudiante($idSeccion, $idEstudiante)
    {
        ModeloTenant::exigirSeccion($idSeccion);
        ModeloTenant::exigirUsuario($idEstudiante, ['ESTUDIANTE']);
        $stmt = Conexion::conectar()->prepare(
            'SELECT c.idCalificacion, c.id_estudiante, c.id_seccion, c.id_modulo, c.id_curso, c.calificacion,
                    c.devolucion,
                    l.nombreLeccion, l.tipoLeccion
             FROM calificaciones c
             LEFT JOIN lecciones l ON l.idLeccion = c.id_modulo
             WHERE c.id_seccion = :idSeccion
               AND c.id_estudiante = :idEstudiante AND ' . ModeloTenant::calificaciones('c') . '
             ORDER BY c.idCalificacion DESC'
        );
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
        $stmt->bindValue(':idEstudiante', (int) $idEstudiante, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlCalificacionPorLeccionYEstudiante($idSeccion, $idLeccion, $idEstudiante)
    {
        ModeloTenant::exigirLeccion($idLeccion, $idSeccion);
        ModeloTenant::exigirUsuario($idEstudiante, ['ESTUDIANTE']);
        $stmt = Conexion::conectar()->prepare(
            'SELECT c.idCalificacion, c.id_estudiante, c.id_seccion, c.id_modulo, c.id_curso, c.calificacion, c.devolucion,
                    l.nombreLeccion, l.tipoLeccion
             FROM calificaciones c
             LEFT JOIN lecciones l ON l.idLeccion = c.id_modulo
             WHERE c.id_seccion = :idSeccion
               AND c.id_modulo = :idLeccion
               AND c.id_estudiante = :idEstudiante AND ' . ModeloTenant::calificaciones('c') . '
             LIMIT 1'
        );
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
        $stmt->bindValue(':idLeccion', (int) $idLeccion, PDO::PARAM_INT);
        $stmt->bindValue(':idEstudiante', (int) $idEstudiante, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function mdlCalificacionPorSeccionYEstudiante($idSeccion, $idEstudiante)
    {
        ModeloTenant::exigirSeccion($idSeccion);
        ModeloTenant::exigirUsuario($idEstudiante, ['ESTUDIANTE']);
        $stmt = Conexion::conectar()->prepare(
            'SELECT c.idCalificacion, c.id_estudiante, c.id_seccion, c.id_modulo, c.id_curso, c.calificacion, c.devolucion,
                    l.nombreLeccion, l.tipoLeccion
             FROM calificaciones c
             LEFT JOIN lecciones l ON l.idLeccion = c.id_modulo
             WHERE c.id_seccion = :idSeccion
               AND c.id_estudiante = :idEstudiante AND ' . ModeloTenant::calificaciones('c') . '
             ORDER BY c.idCalificacion DESC'
        );
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
        $stmt->bindValue(':idEstudiante', (int) $idEstudiante, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlCrearEvaluacion(array $datos)
    {
        self::prepararTablasEvaluaciones();

        $pdo = Conexion::conectar();
        $stmt = $pdo->prepare('
            INSERT INTO evaluaciones (id_seccion, id_curso, id_periodo, id_instrumento, id_autor, temaEvaluacion, fechaEvaluacion)
            VALUES (:id_seccion, :id_curso, :id_periodo, :id_instrumento, :id_autor, :temaEvaluacion, :fechaEvaluacion)
        ');
        $stmt->bindValue(':id_seccion', (int) $datos['id_seccion'], PDO::PARAM_INT);
        $stmt->bindValue(':id_curso', (int) $datos['id_curso'], PDO::PARAM_INT);
        $stmt->bindValue(':id_periodo', (int) $datos['id_periodo'], PDO::PARAM_INT);
        $stmt->bindValue(':id_instrumento', (int) $datos['id_instrumento'], PDO::PARAM_INT);
        $stmt->bindValue(':id_autor', (int) $datos['id_autor'], PDO::PARAM_INT);
        $stmt->bindValue(':temaEvaluacion', (string) $datos['temaEvaluacion'], PDO::PARAM_STR);
        $stmt->bindValue(':fechaEvaluacion', (string) $datos['fechaEvaluacion'], PDO::PARAM_STR);

        if (!$stmt->execute()) {
            return 'error';
        }

        return (int) $pdo->lastInsertId();
    }

    public static function mdlActualizarEvaluacion(array $datos)
    {
        self::prepararTablasEvaluaciones();

        $stmt = Conexion::conectar()->prepare("
            UPDATE evaluaciones
            SET temaEvaluacion = :temaEvaluacion
            WHERE idEvaluacion = :idEvaluacion
        ");
        $stmt->bindValue(':temaEvaluacion', (string) $datos['temaEvaluacion'], PDO::PARAM_STR);
        $stmt->bindValue(':idEvaluacion', (int) $datos['idEvaluacion'], PDO::PARAM_INT);

        return $stmt->execute() ? 'ok' : 'error';
    }

    public static function mdlEliminarEvaluacion($idEvaluacion)
    {
        self::prepararTablasEvaluaciones();

        $stmt = Conexion::conectar()->prepare('
            DELETE e, ec
            FROM evaluaciones e
            LEFT JOIN evaluaciones_calificaciones ec ON ec.id_evaluacion = e.idEvaluacion
            WHERE e.idEvaluacion = :idEvaluacion
        ');
        $stmt->bindValue(':idEvaluacion', (int) $idEvaluacion, PDO::PARAM_INT);

        return $stmt->execute() ? 'ok' : 'error';
    }

    public static function mdlEvaluacionPorId($idEvaluacion)
    {
        self::prepararTablasEvaluaciones();

        $stmt = Conexion::conectar()->prepare('
            SELECT e.*, s.tituloSeccion, c.nombreCurso, COALESCE(pe.estado,"ABIERTO") AS estadoPeriodo, p.nombre AS nombrePeriodo, i.nombre AS nombreInstrumento
            FROM evaluaciones e
            INNER JOIN secciones s ON s.idSeccion = e.id_seccion
            INNER JOIN cursos c ON c.idCurso = e.id_curso
            LEFT JOIN periodos_calificacion p ON p.idPeriodo=e.id_periodo
            LEFT JOIN periodos_seccion_estado pe ON pe.id_periodo=e.id_periodo AND pe.id_seccion=e.id_seccion
            LEFT JOIN instrumentos_evaluacion i ON i.idInstrumento=e.id_instrumento
            WHERE e.idEvaluacion = :idEvaluacion
            LIMIT 1
        ');
        $stmt->bindValue(':idEvaluacion', (int) $idEvaluacion, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function mdlEvaluacionesPorSeccion($idSeccion)
    {
        self::prepararTablasEvaluaciones();

        $stmt = Conexion::conectar()->prepare("
            SELECT e.*, u.nombreUsuario, u.apellidoUsuario, p.nombre AS nombrePeriodo, COALESCE(pe.estado,'ABIERTO') AS estadoPeriodo,
                   i.nombre AS nombreInstrumento,
                   COUNT(ec.idEvaluacionCalificacion) AS totalCalificados,
                   SUM(CASE WHEN ec.estadoAsistencia='AUSENTE' THEN 1 ELSE 0 END) AS totalAusentes,
                   SUM(CASE WHEN ec.estadoAsistencia='PRESENTE' AND ec.calificacion>=7 THEN 1 ELSE 0 END) AS totalAprobados,
                   SUM(CASE WHEN ec.estadoAsistencia='PRESENTE' AND ec.calificacion<7 THEN 1 ELSE 0 END) AS totalDesaprobados,
                   ROUND(AVG(CASE WHEN ec.estadoAsistencia='PRESENTE' THEN ec.calificacion END), 2) AS promedio
            FROM evaluaciones e
            LEFT JOIN usuarios u ON u.idUsuario = e.id_autor
            LEFT JOIN evaluaciones_calificaciones ec ON ec.id_evaluacion = e.idEvaluacion
            LEFT JOIN periodos_calificacion p ON p.idPeriodo=e.id_periodo
            LEFT JOIN periodos_seccion_estado pe ON pe.id_periodo=e.id_periodo AND pe.id_seccion=e.id_seccion
            LEFT JOIN instrumentos_evaluacion i ON i.idInstrumento=e.id_instrumento
            WHERE e.id_seccion = :idSeccion
            GROUP BY e.idEvaluacion
            ORDER BY e.fechaEvaluacion DESC, e.idEvaluacion DESC
        ");
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlEvaluacionesPorEstudiante($idSeccion, $idEstudiante)
    {
        self::prepararTablasEvaluaciones();

        $stmt = Conexion::conectar()->prepare('
            SELECT e.idEvaluacion, e.temaEvaluacion, e.fechaEvaluacion, p.nombre AS nombrePeriodo,
                   i.nombre AS nombreInstrumento, ec.calificacion, ec.estadoAsistencia, ec.devolucion, ec.fechaActualizacion
            FROM evaluaciones e
            INNER JOIN evaluaciones_calificaciones ec ON ec.id_evaluacion = e.idEvaluacion
            LEFT JOIN periodos_calificacion p ON p.idPeriodo=e.id_periodo
            LEFT JOIN instrumentos_evaluacion i ON i.idInstrumento=e.id_instrumento
            WHERE e.id_seccion = :idSeccion
              AND ec.id_estudiante = :idEstudiante
            ORDER BY e.fechaEvaluacion DESC, e.idEvaluacion DESC
        ');
        $stmt->bindValue(':idSeccion', (int) $idSeccion, PDO::PARAM_INT);
        $stmt->bindValue(':idEstudiante', (int) $idEstudiante, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlEstudiantesPorCurso($idCurso)
    {
        self::prepararTablasEvaluaciones();
        ModeloTenant::exigirCurso($idCurso);

        $stmt = Conexion::conectar()->prepare('
            SELECT DISTINCT u.idUsuario, u.nombreUsuario, u.apellidoUsuario, u.email
            FROM asignacioncursos a
            INNER JOIN usuarios u ON u.idUsuario = a.id_estudiante
            WHERE a.id_seccion = :idCurso
              AND a.estadoInscripcion = "ACTIVA"
              AND u.activo = 1
              AND ' . ModeloTenant::cursoId($idCurso) . '
              AND ' . ModeloTenant::usuarioConRol('u.idUsuario', ['ESTUDIANTE']) . '
            ORDER BY u.apellidoUsuario ASC, u.nombreUsuario ASC
        ');
        $stmt->bindValue(':idCurso', (int) $idCurso, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlEstudiantesIntensificacion($idCurso,$idSeccion)
    {
        self::prepararTablasEvaluaciones();
        $stmt=Conexion::conectar()->prepare("SELECT DISTINCT u.idUsuario,u.nombreUsuario,u.apellidoUsuario,u.email FROM asignacioncursos a INNER JOIN usuarios u ON u.idUsuario=a.id_estudiante WHERE a.id_seccion=:idCurso AND a.estadoInscripcion='ACTIVA' AND u.activo=1 AND (SELECT cp.calificacionCierre FROM cierres_periodo_calificaciones cp INNER JOIN periodos_calificacion p ON p.idPeriodo=cp.id_periodo WHERE cp.id_seccion=:idSeccion AND cp.id_estudiante=u.idUsuario AND p.tipo IN ('REGULAR','FINAL') AND cp.calificacionCierre IS NOT NULL ORDER BY CASE WHEN p.tipo='FINAL' THEN 1 ELSE 0 END DESC,p.orden DESC LIMIT 1)<7 ORDER BY u.apellidoUsuario,u.nombreUsuario");
        $stmt->execute([':idCurso'=>(int)$idCurso,':idSeccion'=>(int)$idSeccion]);return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlCalificacionesEvaluacion($idEvaluacion)
    {
        self::prepararTablasEvaluaciones();

        $stmt = Conexion::conectar()->prepare('
            SELECT ec.*, u.nombreUsuario, u.apellidoUsuario, u.email
            FROM evaluaciones_calificaciones ec
            INNER JOIN usuarios u ON u.idUsuario = ec.id_estudiante
            WHERE ec.id_evaluacion = :idEvaluacion
            ORDER BY u.apellidoUsuario ASC, u.nombreUsuario ASC
        ');
        $stmt->bindValue(':idEvaluacion', (int) $idEvaluacion, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function mdlGuardarCalificacionesEvaluacion($idEvaluacion, array $calificaciones)
    {
        self::prepararTablasEvaluaciones();

        $stmt = Conexion::conectar()->prepare('
            INSERT INTO evaluaciones_calificaciones
                (id_evaluacion, id_estudiante, calificacion, estadoAsistencia, devolucion, fechaActualizacion)
            VALUES
                (:id_evaluacion, :id_estudiante, :calificacion, :estadoAsistencia, :devolucion, NOW())
            ON DUPLICATE KEY UPDATE
                calificacion = VALUES(calificacion),
                estadoAsistencia = VALUES(estadoAsistencia),
                devolucion = VALUES(devolucion),
                fechaActualizacion = NOW()
        ');

        try {
            foreach ($calificaciones as $calificacion) {
                $stmt->bindValue(':id_evaluacion', (int) $idEvaluacion, PDO::PARAM_INT);
                $stmt->bindValue(':id_estudiante', (int) $calificacion['id_estudiante'], PDO::PARAM_INT);
                $esAusente = ($calificacion['estadoAsistencia'] ?? 'PRESENTE') === 'AUSENTE';
                $stmt->bindValue(':calificacion', $esAusente ? null : (string) $calificacion['calificacion'], $esAusente ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $stmt->bindValue(':estadoAsistencia', $esAusente ? 'AUSENTE' : 'PRESENTE', PDO::PARAM_STR);
                $stmt->bindValue(':devolucion', (string) ($calificacion['devolucion'] ?? ''), PDO::PARAM_STR);
                if (!$stmt->execute()) {
                    return 'error';
                }
            }
        } catch (Throwable $e) {
            return 'error';
        }

        return 'ok';
    }
}
