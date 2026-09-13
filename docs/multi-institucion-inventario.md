# Inventario inicial de accesos

Generado antes de modificar el código de aplicación. Referencias del punto de partida.

## Referencias a rol

```text
modelos\usuarios.modelo.php:200:        $rol = self::resolverRolWordPress($usuarioWp);
modelos\usuarios.modelo.php:201:        if ($rol === null) {
modelos\usuarios.modelo.php:241:            // WordPress autentica la identidad, pero el rol academico se administra
modelos\usuarios.modelo.php:243:            $rolLocal = strtoupper(trim((string) ($usuarioLocal['rol'] ?? '')));
modelos\usuarios.modelo.php:247:                $rol = $rolLocal;
modelos\usuarios.modelo.php:255:                    rol = :rol,
modelos\usuarios.modelo.php:263:            $update->bindValue(':rol', $rol, PDO::PARAM_STR);
modelos\usuarios.modelo.php:275:                (nombreUsuario, apellidoUsuario, email, pass, resetPass, imgUsuario, activo, rol, fechaAlta, wpUserId, origenAuth)
modelos\usuarios.modelo.php:277:                (:nombre, :apellido, :email, :pass, 0, '', 1, :rol, NOW(), :wpUserId, 'WORDPRESS')
modelos\usuarios.modelo.php:283:        $insert->bindValue(':rol', $rol, PDO::PARAM_STR);
modelos\usuarios.modelo.php:384:        $columnasPermitidas = ['idUsuario', 'nombreUsuario', 'apellidoUsuario', 'email', 'rol', 'activo', 'resetPass'];
modelos\usuarios.modelo.php:417:              AND u.rol IN ('DOCENTE', 'ADMINISTRADOR')
modelos\usuarios.modelo.php:418:            ORDER BY FIELD(u.rol, 'DOCENTE', 'ADMINISTRADOR'), u.apellidoUsuario ASC, u.nombreUsuario ASC
modelos\usuarios.modelo.php:486:                SELECT idUsuario, nombreUsuario, apellidoUsuario, email, rol
modelos\usuarios.modelo.php:490:                ORDER BY rol ASC, apellidoUsuario ASC, nombreUsuario ASC
modelos\usuarios.modelo.php:499:            SELECT DISTINCT u.idUsuario, u.nombreUsuario, u.apellidoUsuario, u.email, u.rol
modelos\usuarios.modelo.php:506:              AND u.rol = 'ESTUDIANTE'
modelos\usuarios.modelo.php:511:            SELECT DISTINCT u.idUsuario, u.nombreUsuario, u.apellidoUsuario, u.email, u.rol
modelos\usuarios.modelo.php:518:              AND u.rol IN ('DOCENTE', 'ADMINISTRADOR')
modelos\usuarios.modelo.php:521:            ORDER BY rol ASC, apellidoUsuario ASC, nombreUsuario ASC
modelos\usuarios.modelo.php:586:                (nombreUsuario, apellidoUsuario, email, pass, resetPass, imgUsuario, activo, rol, fechaAlta)
modelos\usuarios.modelo.php:588:                (:nombreUsuario, :apellidoUsuario, :email, :pass, :resetPass, :imgUsuario, :activo, :rol, :fechaAlta)
modelos\usuarios.modelo.php:598:        $registro->bindParam(":rol", $datos["rol"], PDO::PARAM_STR);
modelos\usuarios.modelo.php:606:        $consulta = "UPDATE $tabla SET nombreUsuario = :nombreUsuario, apellidoUsuario = :apellidoUsuario, email = :email, rol = :rol";
modelos\usuarios.modelo.php:611:            ":rol" => $datos["rol"],
controladores\auth.controller.php:62:            'rol' => $usuario['rol'],
controladores\auth.controller.php:233:            $error = 'Tu cuenta no tiene un rol habilitado para ingresar a Campus.';
controladores\auth.controller.php:234:            self::registrarAuthDebug('Usuario WP sin rol habilitado o sincronizacion fallida', [
controladores\auth.controller.php:244:            'rol' => $usuarioLocal['rol'] ?? '',
modelos\cursos.modelo.php:490:            WHERE u.rol = 'ESTUDIANTE'
modelos\cursos.modelo.php:516:              AND u.rol = 'ESTUDIANTE'
modelos\mensajes.modelo.php:28:    private static function normalizarRol($rol)
modelos\mensajes.modelo.php:30:        return strtoupper(trim((string) $rol));
modelos\mensajes.modelo.php:40:        $rol = self::normalizarRol($rolActual);
modelos\mensajes.modelo.php:43:        if ($rol === 'ADMINISTRADOR') {
modelos\mensajes.modelo.php:54:        if ($rol === 'DOCENTE') {
modelos\mensajes.modelo.php:80:              AND u.rol = "ESTUDIANTE"
modelos\mensajes.modelo.php:349:                   u.nombreUsuario, u.apellidoUsuario, u.email, u.rol
modelos\mensajes.modelo.php:388:            SELECT u.idUsuario, u.nombreUsuario, u.apellidoUsuario, u.rol, mp.leido, mp.enPapelera
modelos\actividades.modelo.php:205:    public static function mdlListarParaUsuario($idUsuario, $rol, $busqueda = '', $tipo = '', $visibilidad = '', $estado = '', $soloDestacadas = '')
modelos\actividades.modelo.php:209:        $rol = strtoupper((string) $rol);
modelos\actividades.modelo.php:218:        if ($rol === 'ADMINISTRADOR') {
modelos\actividades.modelo.php:257:        if ($rol === 'DOCENTE') {
modelos\actividades.modelo.php:349:    public static function mdlListarBancoParaUsuario($idUsuario, $rol, $busqueda = '', $tipo = '', $alcance = '', $estado = '')
modelos\actividades.modelo.php:353:        $rol = strtoupper((string) $rol);
modelos\actividades.modelo.php:361:        if ($rol === 'ESTUDIANTE') {
modelos\actividades.modelo.php:365:        if ($rol === 'ADMINISTRADOR') {
modelos\panel.modelo.php:10:    private static function normalizarRol($rol)
modelos\panel.modelo.php:12:        return strtoupper(trim((string) $rol));
modelos\panel.modelo.php:40:    private static function tarjetasPorRol($rol, $idUsuario)
modelos\panel.modelo.php:42:        $rol = self::normalizarRol($rol);
modelos\panel.modelo.php:44:        if ($rol === 'ADMINISTRADOR') {
modelos\panel.modelo.php:115:        if ($rol === 'DOCENTE') {
modelos\panel.modelo.php:291:    private static function actividadPosteos($idUsuario, $rol, $limite = 2)
modelos\panel.modelo.php:293:        $rol = self::normalizarRol($rol);
modelos\panel.modelo.php:294:        if ($rol === 'ADMINISTRADOR') {
modelos\panel.modelo.php:308:        if ($rol === 'DOCENTE') {
modelos\panel.modelo.php:341:    private static function actividadEntregas($idUsuario, $rol, $limite = 2)
modelos\panel.modelo.php:343:        $rol = self::normalizarRol($rol);
modelos\panel.modelo.php:345:        if ($rol === 'ESTUDIANTE') {
modelos\panel.modelo.php:359:        if ($rol === 'DOCENTE') {
modelos\panel.modelo.php:390:    private static function entregasPendientes($idUsuario, $rol)
modelos\panel.modelo.php:392:        $rol = self::normalizarRol($rol);
modelos\panel.modelo.php:394:        if (!in_array($rol, ['ADMINISTRADOR', 'DOCENTE'], true)) {
modelos\panel.modelo.php:398:        $filtroDocente = $rol === 'DOCENTE'
modelos\panel.modelo.php:401:        $params = $rol === 'DOCENTE' ? [':idUsuario' => (int) $idUsuario] : [];
modelos\panel.modelo.php:424:    private static function actividadCalificaciones($idUsuario, $rol, $limite = 2)
modelos\panel.modelo.php:426:        $rol = self::normalizarRol($rol);
modelos\panel.modelo.php:428:        if ($rol === 'ESTUDIANTE') {
modelos\panel.modelo.php:442:        if ($rol === 'DOCENTE') {
modelos\panel.modelo.php:521:    private static function urlLeccion($idSeccion, $idLeccion, $rol)
modelos\panel.modelo.php:535:        $prefijo = self::normalizarRol($rol) === 'ESTUDIANTE'
modelos\panel.modelo.php:548:    private static function actividadNormalizada(array $items, $idUsuario, $rol)
modelos\panel.modelo.php:564:                    'url' => self::urlLeccion($item['id_seccion'] ?? 0, $item['id_leccion'] ?? 0, $rol),
modelos\panel.modelo.php:575:                    'url' => self::urlLeccion($item['idSeccion'] ?? 0, $item['id_leccion'] ?? 0, $rol),
modelos\panel.modelo.php:586:                    'url' => self::urlLeccion($item['id_seccion'] ?? 0, $item['id_modulo'] ?? 0, $rol),
modelos\panel.modelo.php:598:                        $rol
modelos\panel.modelo.php:663:    public static function mdlResumenDashboard($idUsuario, $rol)
modelos\panel.modelo.php:666:        $rol = self::normalizarRol($rol);
modelos\panel.modelo.php:667:        $tarjetas = self::tarjetasPorRol($rol, $idUsuario);
modelos\panel.modelo.php:672:            self::actividadEntregas($idUsuario, $rol, 2),
modelos\panel.modelo.php:673:            self::actividadPosteos($idUsuario, $rol, 2),
modelos\panel.modelo.php:674:            self::actividadCalificaciones($idUsuario, $rol, 2)
modelos\panel.modelo.php:736:            'pendientes' => self::entregasPendientes($idUsuario, $rol),
modelos\panel.modelo.php:737:            'rol' => $rol,
modelos\panel.modelo.php:741:    public static function mdlIndicadoresCabecera($idUsuario, $rol)
modelos\panel.modelo.php:744:        $rol = self::normalizarRol($rol);
modelos\panel.modelo.php:748:        if ($rol === 'ADMINISTRADOR') {
modelos\panel.modelo.php:758:        } elseif ($rol === 'DOCENTE') {
modelos\panel.modelo.php:771:        } elseif ($rol === 'ESTUDIANTE') {
modelos\panel.modelo.php:780:        if ($rol === 'ADMINISTRADOR') {
modelos\panel.modelo.php:786:        } elseif ($rol === 'DOCENTE') {
modelos\panel.modelo.php:796:        } elseif ($rol === 'ESTUDIANTE') {
modelos\panel.modelo.php:811:            self::actividadEntregas($idUsuario, $rol, 10),
modelos\panel.modelo.php:812:            self::actividadPosteos($idUsuario, $rol, 10),
modelos\panel.modelo.php:813:            self::actividadCalificaciones($idUsuario, $rol, 10)
modelos\panel.modelo.php:814:        ), $idUsuario, $rol), 0, 10);
controladores\cursos.controller.php:157:                $rol = strtoupper((string) ($usuario['rol'] ?? ''));
controladores\cursos.controller.php:158:                if (!$usuario || (int) ($usuario['activo'] ?? 0) !== 1 || !in_array($rol, ['DOCENTE', 'ADMINISTRADOR'], true)) {
controladores\mensajes.controller.php:172:            $mapaPermitidos[(int) $usuario['idUsuario']] = strtoupper(trim((string) ($usuario['rol'] ?? '')));
modelos\notificaciones.modelo.php:46:               AND u.rol = "ESTUDIANTE"
modelos\calificaciones.modelo.php:552:              AND u.rol = "ESTUDIANTE"
controladores\usuarios.controller.php:181:        $rolUsuario = trim((string) ($_POST["rol"] ?? ''));
controladores\usuarios.controller.php:201:                "rol" => $rolUsuario,
controladores\usuarios.controller.php:261:        if (!isset($_POST["nombreUsuario"], $_POST["apellidoUsuario"], $_POST["emailUsuario"], $_POST["rol"])) {
controladores\usuarios.controller.php:271:            "rol" => trim((string) $_POST["rol"]),
modelos\lecciones.modelo.php:377:               AND u.rol = "ESTUDIANTE"
vistas\paginas\cursos\listado-cursos.php:21:            (array) ControladorUsuarios::crtSeleccionarUsuario('rol', 'ESTUDIANTE'),
controladores\permisos.controller.php:5:    private static function normalizarRol($rol)
controladores\permisos.controller.php:7:        return strtoupper(trim((string) $rol));
controladores\permisos.controller.php:12:        return self::normalizarRol($_SESSION['usuario']['rol'] ?? '');
controladores\permisos.controller.php:62:        $rol = self::rolActual();
controladores\permisos.controller.php:64:        if ($rol === 'ADMINISTRADOR') {
controladores\permisos.controller.php:118:        return in_array($ruta, $permisos[$rol] ?? [], true);
controladores\permisos.controller.php:139:        $rol = self::rolActual();
controladores\permisos.controller.php:144:        return $rol !== '' ? $rol : 'SIN ROL';
controladores\materias.controller.php:17:            && in_array(strtoupper((string) ($usuario['rol'] ?? '')), ['DOCENTE', 'ADMINISTRADOR'], true);
controladores\rutas.controller.php:189:                && strtoupper((string) ($estudiante['rol'] ?? '')) === 'ESTUDIANTE'
vistas\paginas\cursos\detalle-curso.php:24:        WHERE usuarios.rol = 'ESTUDIANTE'
vistas\paginas\inicio.php:61:  'descripcion' => 'Los valores de abajo resumen mensajes, actividad reciente y el estado academico o de gestion segun tu rol. Los accesos rapidos siguen estando mas abajo para ir directo a lo que usas mas.',
vistas\paginas\usuario\listado-usuarios.php:80:                  <td><?php echo $e($valor['rol'] ?? ''); ?></td>
vistas\paginas\usuario\usuarios-inactivos.php:81:                  <td><?php echo $e($valor['rol'] ?? ''); ?></td>
vistas\paginas\usuario\perfil-usuario.php:27:            <?php echo $e($usuario['rol'] ?? 'Sin rol'); ?> · <?php echo $estaActivo ? 'Cuenta activa' : 'Cuenta dada de baja'; ?>
vistas\paginas\usuario\perfil-usuario.php:60:              <strong><?php echo $e($usuario['rol'] ?? 'Sin rol'); ?></strong>
vistas\paginas\usuario\perfil-publico.php:45:            <?php echo $e($usuario['rol'] ?? 'Integrante'); ?> · <?php echo $e($seccion['tituloSeccion'] ?? 'Materia'); ?>
vistas\paginas\usuario\usuarios-no-conectados.php:81:                  <td><?php echo $e($valor['rol'] ?? ''); ?></td>
vistas\paginas\usuario\editar-usuario.php:13:        'rol' => 'ESTUDIANTE',
vistas\contenido\header.php:55:          title="<?php echo $vistaEstudianteActiva ? 'Volver a mi rol real' : 'Cambiar a vista estudiante'; ?>"
vistas\paginas\usuario\_formulario-usuario.php:64:            <select class="custom-select" name="rol" required>
vistas\paginas\usuario\_formulario-usuario.php:65:              <?php $rolActual = strtoupper((string) ($usuarioFormulario['rol'] ?? 'ESTUDIANTE')); ?>
vistas\paginas\usuario\_formulario-usuario.php:66:              <?php foreach (['ADMINISTRADOR', 'DOCENTE', 'ESTUDIANTE'] as $rol): ?>
vistas\paginas\usuario\_formulario-usuario.php:67:                <option value="<?php echo $rol; ?>" <?php echo $rolActual === $rol ? 'selected' : ''; ?>><?php echo $rol; ?></option>
vistas\paginas\mensajes\detalle-mensaje.php:133:                        <?php echo htmlspecialchars('(' . ($destinatario['rol'] ?? '') . ')', ENT_QUOTES, 'UTF-8'); ?>
vistas\paginas\mensajes\nuevo-mensaje.php:119:                                                    <?php echo htmlspecialchars($valor['nombreUsuario'] . ' ' . $valor['apellidoUsuario'] . ' (' . $valor['rol'] . ')', ENT_QUOTES, 'UTF-8'); ?>
```

## SQL y puntos de acceso a datos

```text
modelos\actividades.modelo.php:220:                SELECT a.*, s.tituloSeccion, c.nombreCurso, u.nombreUsuario, u.apellidoUsuario,
modelos\actividades.modelo.php:227:                    SELECT id_actividad, COUNT(*) AS totalIntentos
modelos\actividades.modelo.php:259:                SELECT a.*, s.tituloSeccion, c.nombreCurso, u.nombreUsuario, u.apellidoUsuario,
modelos\actividades.modelo.php:266:                    SELECT id_actividad, COUNT(*) AS totalIntentos
modelos\actividades.modelo.php:305:            SELECT DISTINCT a.*, s.tituloSeccion, c.nombreCurso, u.nombreUsuario, u.apellidoUsuario,
modelos\actividades.modelo.php:313:                SELECT id_actividad, COUNT(*) AS totalIntentos
modelos\actividades.modelo.php:367:                SELECT a.*, s.tituloSeccion, c.nombreCurso, u.nombreUsuario, u.apellidoUsuario
modelos\actividades.modelo.php:396:            SELECT a.*, s.tituloSeccion, c.nombreCurso, u.nombreUsuario, u.apellidoUsuario
modelos\actividades.modelo.php:437:            SELECT a.*, s.tituloSeccion, c.nombreCurso
modelos\actividades.modelo.php:455:            SELECT a.*, s.tituloSeccion, c.nombreCurso, s.docente, s.tutor
modelos\actividades.modelo.php:472:            SELECT a.*, s.tituloSeccion, c.nombreCurso, s.docente, s.tutor
modelos\actividades.modelo.php:489:            SELECT *
modelos\actividades.modelo.php:500:                SELECT *
modelos\actividades.modelo.php:518:            SELECT COUNT(*) AS total
modelos\actividades.modelo.php:535:            INSERT INTO actividades (
modelos\actividades.modelo.php:565:            UPDATE actividades
modelos\actividades.modelo.php:649:        $sql = "UPDATE actividades SET " . implode(', ', $sets) . ", fechaActualizacion = NOW() WHERE idActividad = :idActividad";
modelos\actividades.modelo.php:666:                INSERT INTO actividades_preguntas (
modelos\actividades.modelo.php:688:                    INSERT INTO actividades_opciones (id_pregunta, textoOpcion, esCorrecta, orden)
modelos\actividades.modelo.php:703:            SELECT idPregunta
modelos\actividades.modelo.php:713:            $stmtOpciones = Conexion::conectar()->prepare("DELETE FROM actividades_opciones WHERE id_pregunta IN ($placeholders)");
modelos\actividades.modelo.php:720:        $stmt = Conexion::conectar()->prepare("DELETE FROM actividades_preguntas WHERE id_actividad = :idActividad");
modelos\actividades.modelo.php:741:            $stmt = $pdo->prepare("DELETE FROM actividades_intentos WHERE id_actividad = :idActividad");
modelos\actividades.modelo.php:754:            $stmt = $pdo->prepare("DELETE FROM actividades_preguntas WHERE id_actividad = :idActividad");
modelos\actividades.modelo.php:758:            $stmt = $pdo->prepare("DELETE FROM actividades WHERE idActividad = :idActividad");
modelos\actividades.modelo.php:773:            INSERT INTO actividades_intentos (
modelos\actividades.modelo.php:794:                INSERT INTO actividades_respuestas (
modelos\actividades.modelo.php:817:            SELECT COUNT(*) AS total
modelos\actividades.modelo.php:833:            SELECT i.*, u.nombreUsuario, u.apellidoUsuario, u.email
modelos\actividades.modelo.php:849:            SELECT
modelos\actividades.modelo.php:894:            SELECT
modelos\actividades.modelo.php:927:            SELECT
modelos\calificaciones.modelo.php:42:        $pdo->exec("INSERT IGNORE INTO periodos_seccion_estado(id_periodo,id_seccion,estado,fechaCierre,cerradoPor,fechaReapertura,reabiertoPor,motivoReapertura) SELECT relaciones.id_periodo,relaciones.id_seccion,p.estado,p.fechaCierre,p.cerradoPor,p.fechaReapertura,p.reabiertoPor,p.motivoReapertura FROM (SELECT id_periodo,id_seccion FROM evaluaciones WHERE id_periodo IS NOT NULL UNION SELECT id_periodo,id_seccion FROM cierres_periodo_calificaciones) relaciones INNER JOIN periodos_calificacion p ON p.idPeriodo=relaciones.id_periodo");
modelos\calificaciones.modelo.php:77:            $pdo->prepare('INSERT IGNORE INTO instrumentos_evaluacion (nombre,orden) VALUES (?,?)')->execute([$nombre,$orden+1]);
modelos\calificaciones.modelo.php:86:        $stmt=$pdo->prepare('SELECT c.idCurso,c.id_ciclo_lectivo,c.fechaInicioCurso,c.modalidadCalificacion,c.intensificacionActiva FROM secciones s INNER JOIN cursos c ON c.idCurso=s.id_curso WHERE s.idSeccion=?');
modelos\calificaciones.modelo.php:90:        $pdo->prepare('INSERT IGNORE INTO ciclos_lectivos (nombre,anio) VALUES (?,?)')->execute(['Ciclo lectivo '.$anio,$anio]);
modelos\calificaciones.modelo.php:91:        $q=$pdo->prepare('SELECT * FROM ciclos_lectivos WHERE anio=? LIMIT 1'); $q->execute([$anio]); $ciclo=$q->fetch(PDO::FETCH_ASSOC);
modelos\calificaciones.modelo.php:93:        $pdo->prepare('UPDATE cursos SET id_ciclo_lectivo=? WHERE idCurso=?')->execute([$idCiclo,(int)$curso['idCurso']]);
modelos\calificaciones.modelo.php:95:            $pdo->prepare('INSERT IGNORE INTO periodos_calificacion (id_ciclo,nombre,tipo,orden) VALUES (?,?,?,?)')->execute([$idCiclo,$periodo[0],$periodo[1],$orden+1]);
modelos\calificaciones.modelo.php:98:        $qInt=$pdo->prepare("SELECT COUNT(*) FROM periodos_calificacion p LEFT JOIN evaluaciones e ON e.id_periodo=p.idPeriodo AND e.id_seccion=? LEFT JOIN cierres_periodo_calificaciones cp ON cp.id_periodo=p.idPeriodo AND cp.id_seccion=? WHERE p.id_ciclo=? AND p.tipo='INTENSIFICACION' AND (e.idEvaluacion IS NOT NULL OR cp.idCierrePeriodo IS NOT NULL)");$qInt->execute([(int)$idSeccion,(int)$idSeccion,$idCiclo]);
modelos\calificaciones.modelo.php:100:        $marcas=implode(',',array_fill(0,count($nombres),'?'));$q=$pdo->prepare("SELECT p.*,COALESCE(pe.estado,'ABIERTO') estado FROM periodos_calificacion p LEFT JOIN periodos_seccion_estado pe ON pe.id_periodo=p.idPeriodo AND pe.id_seccion=? WHERE p.id_ciclo=? AND p.nombre IN ($marcas) ORDER BY FIELD(p.nombre,'Primer período','Segundo período','Período único','Calificación final','Intensificación'),p.idPeriodo");$q->execute(array_merge([(int)$idSeccion,$idCiclo],$nombres));$periodos=$q->fetchAll(PDO::FETCH_ASSOC);
modelos\calificaciones.modelo.php:101:        if($periodos){$pdo->prepare('UPDATE evaluaciones SET id_periodo=? WHERE id_curso=? AND id_periodo IS NULL')->execute([(int)$periodos[0]['idPeriodo'],(int)$curso['idCurso']]);}
modelos\calificaciones.modelo.php:102:        return ['ciclo'=>$ciclo,'periodos'=>$periodos,'instrumentos'=>$pdo->query('SELECT * FROM instrumentos_evaluacion WHERE activo=1 ORDER BY orden,nombre')->fetchAll(PDO::FETCH_ASSOC)];
modelos\calificaciones.modelo.php:107:        self::prepararTablasEvaluaciones(); $stmt=Conexion::conectar()->prepare("SELECT p.*,COALESCE(pe.estado,'ABIERTO') estado FROM periodos_calificacion p LEFT JOIN periodos_seccion_estado pe ON pe.id_periodo=p.idPeriodo AND pe.id_seccion=? WHERE p.idPeriodo=? LIMIT 1");
modelos\calificaciones.modelo.php:114:        $sql=$cerrar?'INSERT INTO periodos_seccion_estado(id_periodo,id_seccion,estado,fechaCierre,cerradoPor) VALUES(?, ?,"CERRADO",NOW(),?) ON DUPLICATE KEY UPDATE estado="CERRADO",fechaCierre=NOW(),cerradoPor=VALUES(cerradoPor)':'INSERT INTO periodos_seccion_estado(id_periodo,id_seccion,estado,fechaReapertura,reabiertoPor,motivoReapertura) VALUES(?, ?,"ABIERTO",NOW(),?,?) ON DUPLICATE KEY UPDATE estado="ABIERTO",fechaReapertura=NOW(),reabiertoPor=VALUES(reabiertoPor),motivoReapertura=VALUES(motivoReapertura)';
modelos\calificaciones.modelo.php:121:        $stmt=$pdo->prepare("SELECT ec.id_estudiante,ROUND(AVG(ec.calificacion),2) promedio
modelos\calificaciones.modelo.php:126:        $guardar=$pdo->prepare('INSERT INTO cierres_periodo_calificaciones (id_periodo,id_seccion,id_estudiante,promedioCalculado,calificacionCierre,actualizadoPor) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE promedioCalculado=VALUES(promedioCalculado),calificacionCierre=IF(confirmada=1,calificacionCierre,VALUES(calificacionCierre)),fechaActualizacion=NOW(),actualizadoPor=VALUES(actualizadoPor)');
modelos\calificaciones.modelo.php:133:        self::prepararTablasEvaluaciones(); $stmt=Conexion::conectar()->prepare('SELECT cp.*,u.nombreUsuario,u.apellidoUsuario FROM cierres_periodo_calificaciones cp INNER JOIN usuarios u ON u.idUsuario=cp.id_estudiante WHERE cp.id_periodo=? AND cp.id_seccion=? ORDER BY u.apellidoUsuario,u.nombreUsuario');
modelos\calificaciones.modelo.php:139:        self::prepararTablasEvaluaciones(); $stmt=Conexion::conectar()->prepare('SELECT cp.*,p.nombre AS nombrePeriodo,p.estado AS estadoPeriodo FROM cierres_periodo_calificaciones cp INNER JOIN periodos_calificacion p ON p.idPeriodo=cp.id_periodo WHERE cp.id_seccion=? AND cp.id_estudiante=? ORDER BY p.orden,p.idPeriodo');
modelos\calificaciones.modelo.php:145:        self::prepararTablasEvaluaciones(); $stmt=Conexion::conectar()->prepare('UPDATE cierres_periodo_calificaciones SET calificacionCierre=?,confirmada=1,fechaActualizacion=NOW(),actualizadoPor=? WHERE id_periodo=? AND id_seccion=? AND id_estudiante=?');
modelos\calificaciones.modelo.php:154:            'INSERT INTO calificaciones (id_estudiante, id_seccion, id_modulo, id_curso, calificacion, devolucion)
modelos\calificaciones.modelo.php:156:             ON DUPLICATE KEY UPDATE
modelos\calificaciones.modelo.php:192:            'INSERT INTO calificaciones
modelos\calificaciones.modelo.php:195:             ON DUPLICATE KEY UPDATE
modelos\calificaciones.modelo.php:208:            'SELECT c.idCalificacion, c.id_estudiante, c.id_seccion, c.id_modulo, c.id_curso, c.calificacion,
modelos\calificaciones.modelo.php:237:            SELECT COALESCE(c.fechaActualizacion, c.fechaCalificacion) AS fecha,
modelos\calificaciones.modelo.php:260:            SELECT COALESCE(ec.fechaActualizacion, e.fechaEvaluacion) AS fecha,
modelos\calificaciones.modelo.php:286:            SELECT cp.fechaActualizacion AS fecha, curso.nombreCurso AS curso, s.tituloSeccion AS materia,
modelos\calificaciones.modelo.php:328:        $sql = 'SELECT cp.id_estudiante,cp.id_seccion,cp.calificacionCierre,cp.promedioCalculado,cp.confirmada,
modelos\calificaciones.modelo.php:352:        $stmt=Conexion::conectar()->prepare('SELECT s.idSeccion,s.tituloSeccion AS materia,c.idCurso,c.nombreCurso AS curso,
modelos\calificaciones.modelo.php:369:            'SELECT c.idCalificacion, c.id_estudiante, c.id_seccion, c.id_modulo, c.id_curso, c.calificacion,
modelos\calificaciones.modelo.php:387:            'SELECT c.idCalificacion, c.id_estudiante, c.id_seccion, c.id_modulo, c.id_curso, c.calificacion, c.devolucion,
modelos\calificaciones.modelo.php:406:            'SELECT c.idCalificacion, c.id_estudiante, c.id_seccion, c.id_modulo, c.id_curso, c.calificacion, c.devolucion,
modelos\calificaciones.modelo.php:426:            INSERT INTO evaluaciones (id_seccion, id_curso, id_periodo, id_instrumento, id_autor, temaEvaluacion, fechaEvaluacion)
modelos\calificaciones.modelo.php:449:            UPDATE evaluaciones
modelos\calificaciones.modelo.php:479:            SELECT e.*, s.tituloSeccion, c.nombreCurso, COALESCE(pe.estado,"ABIERTO") AS estadoPeriodo, p.nombre AS nombrePeriodo, i.nombre AS nombreInstrumento
modelos\calificaciones.modelo.php:499:            SELECT e.*, u.nombreUsuario, u.apellidoUsuario, p.nombre AS nombrePeriodo, COALESCE(pe.estado,'ABIERTO') AS estadoPeriodo,
modelos\calificaciones.modelo.php:526:            SELECT e.idEvaluacion, e.temaEvaluacion, e.fechaEvaluacion, p.nombre AS nombrePeriodo,
modelos\calificaciones.modelo.php:547:            SELECT DISTINCT u.idUsuario, u.nombreUsuario, u.apellidoUsuario, u.email
modelos\calificaciones.modelo.php:564:        $stmt=Conexion::conectar()->prepare("SELECT DISTINCT u.idUsuario,u.nombreUsuario,u.apellidoUsuario,u.email FROM asignacioncursos a INNER JOIN usuarios u ON u.idUsuario=a.id_estudiante WHERE a.id_seccion=:idCurso AND a.estadoInscripcion='ACTIVA' AND u.activo=1 AND (SELECT cp.calificacionCierre FROM cierres_periodo_calificaciones cp INNER JOIN periodos_calificacion p ON p.idPeriodo=cp.id_periodo WHERE cp.id_seccion=:idSeccion AND cp.id_estudiante=u.idUsuario AND p.tipo IN ('REGULAR','FINAL') AND cp.calificacionCierre IS NOT NULL ORDER BY CASE WHEN p.tipo='FINAL' THEN 1 ELSE 0 END DESC,p.orden DESC LIMIT 1)<7 ORDER BY u.apellidoUsuario,u.nombreUsuario");
modelos\calificaciones.modelo.php:573:            SELECT ec.*, u.nombreUsuario, u.apellidoUsuario, u.email
modelos\calificaciones.modelo.php:589:            INSERT INTO evaluaciones_calificaciones
modelos\calificaciones.modelo.php:593:            ON DUPLICATE KEY UPDATE
modelos\asistencias.modelo.php:7:    public static function mdlSecciones($idDocente=0){self::prepararTablas();$f=(int)$idDocente>0?' WHERE s.docente=:d OR s.tutor=:d OR c.responsable=:d':'';$st=Conexion::conectar()->prepare("SELECT s.idSeccion,s.tituloSeccion materia,c.idCurso,c.nombreCurso curso,CONCAT(u.nombreUsuario,' ',u.apellidoUsuario) docente,COUNT(DISTINCT ac.idClase) clases FROM secciones s INNER JOIN cursos c ON c.idCurso=s.id_curso LEFT JOIN usuarios u ON u.idUsuario=s.docente LEFT JOIN asistencia_clases ac ON ac.id_seccion=s.idSeccion $f GROUP BY s.idSeccion ORDER BY c.nombreCurso,s.tituloSeccion");if((int)$idDocente>0)$st->bindValue(':d',(int)$idDocente,PDO::PARAM_INT);$st->execute();return $st->fetchAll(PDO::FETCH_ASSOC);}
modelos\asistencias.modelo.php:8:    public static function mdlCrearClase($idSeccion,$idCurso,$fecha,$tema,$idUsuario){self::prepararTablas();$pdo=Conexion::conectar();$st=$pdo->prepare('INSERT INTO asistencia_clases(id_seccion,id_curso,fechaClase,tema,creadaPor) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE tema=VALUES(tema),idClase=LAST_INSERT_ID(idClase)');$st->execute([(int)$idSeccion,(int)$idCurso,$fecha,$tema,(int)$idUsuario]);$id=(int)$pdo->lastInsertId();$q=$pdo->prepare("INSERT IGNORE INTO asistencia_registros(id_clase,id_estudiante,estado,actualizadoPor) SELECT ?,a.id_estudiante,'PRESENTE',? FROM asignacioncursos a INNER JOIN usuarios u ON u.idUsuario=a.id_estudiante WHERE a.id_seccion=? AND u.activo=1 AND (a.fechaAlta IS NULL OR DATE(a.fechaAlta)<=?) AND (a.fechaBaja IS NULL OR DATE(a.fechaBaja)>?)");$q->execute([$id,(int)$idUsuario,(int)$idCurso,$fecha,$fecha]);return $id;}
modelos\asistencias.modelo.php:9:    public static function mdlClasesSeccion($idSeccion){self::prepararTablas();$st=Conexion::conectar()->prepare("SELECT c.*,COUNT(r.idAsistencia) total,SUM(r.estado='PRESENTE') presentes,SUM(r.estado='AUSENTE') ausentes,SUM(r.estado='TARDANZA') tardanzas,SUM(r.estado='JUSTIFICADA') justificadas FROM asistencia_clases c LEFT JOIN asistencia_registros r ON r.id_clase=c.idClase WHERE c.id_seccion=? GROUP BY c.idClase ORDER BY c.fechaClase DESC");$st->execute([(int)$idSeccion]);return $st->fetchAll(PDO::FETCH_ASSOC);}
modelos\asistencias.modelo.php:10:    public static function mdlClase($idClase){self::prepararTablas();$st=Conexion::conectar()->prepare('SELECT c.*,s.tituloSeccion materia,cu.nombreCurso curso FROM asistencia_clases c INNER JOIN secciones s ON s.idSeccion=c.id_seccion INNER JOIN cursos cu ON cu.idCurso=c.id_curso WHERE c.idClase=?');$st->execute([(int)$idClase]);return $st->fetch(PDO::FETCH_ASSOC)?:null;}
modelos\asistencias.modelo.php:11:    public static function mdlRegistrosClase($idClase){self::prepararTablas();$st=Conexion::conectar()->prepare('SELECT r.*,u.nombreUsuario,u.apellidoUsuario FROM asistencia_registros r INNER JOIN usuarios u ON u.idUsuario=r.id_estudiante WHERE r.id_clase=? ORDER BY u.apellidoUsuario,u.nombreUsuario');$st->execute([(int)$idClase]);return $st->fetchAll(PDO::FETCH_ASSOC);}
modelos\asistencias.modelo.php:12:    public static function mdlResumenEstudiantesSeccion($idSeccion){self::prepararTablas();$st=Conexion::conectar()->prepare("SELECT r.id_estudiante,COUNT(*) clases,SUM(r.estado='AUSENTE') ausentes,SUM(r.estado='TARDANZA') tardanzas,SUM(r.estado='JUSTIFICADA') justificadas,ROUND(100*SUM(r.estado IN ('PRESENTE','TARDANZA','JUSTIFICADA'))/COUNT(*),1) porcentaje FROM asistencia_registros r INNER JOIN asistencia_clases c ON c.idClase=r.id_clase WHERE c.id_seccion=? GROUP BY r.id_estudiante");$st->execute([(int)$idSeccion]);return $st->fetchAll(PDO::FETCH_ASSOC);}
modelos\asistencias.modelo.php:13:    public static function mdlGuardar($idClase,array $estados,array $observaciones,$idUsuario){self::prepararTablas();$permitidos=array_fill_keys(array_map('intval',array_column(self::mdlRegistrosClase($idClase),'id_estudiante')),true);$st=Conexion::conectar()->prepare('UPDATE asistencia_registros SET estado=?,observacion=?,actualizadoPor=?,fechaActualizacion=NOW() WHERE id_clase=? AND id_estudiante=?');foreach($estados as $id=>$estado){$id=(int)$id;$estado=strtoupper((string)$estado);if(!isset($permitidos[$id])||!in_array($estado,['PRESENTE','AUSENTE','TARDANZA','JUSTIFICADA'],true))continue;if(!$st->execute([$estado,trim((string)($observaciones[$id]??'')),(int)$idUsuario,(int)$idClase,$id]))return 'error';}return 'ok';}
modelos\asistencias.modelo.php:14:    public static function mdlResumenEstudiante($idEstudiante){self::prepararTablas();$st=Conexion::conectar()->prepare("SELECT s.tituloSeccion materia,c.nombreCurso curso,COUNT(*) clases,SUM(r.estado='PRESENTE') presentes,SUM(r.estado='AUSENTE') ausentes,SUM(r.estado='TARDANZA') tardanzas,SUM(r.estado='JUSTIFICADA') justificadas,ROUND(100*SUM(r.estado IN ('PRESENTE','TARDANZA','JUSTIFICADA'))/COUNT(*),1) porcentaje FROM asistencia_registros r INNER JOIN asistencia_clases ac ON ac.idClase=r.id_clase INNER JOIN secciones s ON s.idSeccion=ac.id_seccion INNER JOIN cursos c ON c.idCurso=ac.id_curso WHERE r.id_estudiante=? GROUP BY ac.id_seccion ORDER BY c.nombreCurso,s.tituloSeccion");$st->execute([(int)$idEstudiante]);return $st->fetchAll(PDO::FETCH_ASSOC);}
modelos\usuarios.modelo.php:25:        $stmt = Conexion::conectar()->prepare("SELECT * FROM usuarios WHERE email = :email LIMIT 1");
modelos\usuarios.modelo.php:149:            SELECT u.ID,
modelos\usuarios.modelo.php:176:        $stmt = Conexion::conectar()->prepare("SELECT idPerfil FROM perfiles WHERE id_usuario = :idUsuario LIMIT 1");
modelos\usuarios.modelo.php:185:            INSERT INTO perfiles (id_usuario, dniPerfil, telefonoPerfil, fnacPerfil, domicilioPerfil, provinciaPerfil, contenidoPerfil)
modelos\usuarios.modelo.php:228:            SELECT *
modelos\usuarios.modelo.php:251:                UPDATE usuarios
modelos\usuarios.modelo.php:274:            INSERT INTO usuarios
modelos\usuarios.modelo.php:295:        $stmt = Conexion::conectar()->prepare("SELECT * FROM usuarios WHERE idUsuario = :idUsuario LIMIT 1");
modelos\usuarios.modelo.php:305:            SELECT u.*,
modelos\usuarios.modelo.php:333:            SELECT DISTINCT idCurso, nombreCurso, idSeccion, tituloSeccion, origen
modelos\usuarios.modelo.php:335:                SELECT c.idCurso,
modelos\usuarios.modelo.php:347:                SELECT c.idCurso,
modelos\usuarios.modelo.php:370:                SELECT u.*,
modelos\usuarios.modelo.php:390:            SELECT u.*,
modelos\usuarios.modelo.php:409:            SELECT u.*,
modelos\usuarios.modelo.php:429:            SELECT u.*,
modelos\usuarios.modelo.php:449:            SELECT u.*,
modelos\usuarios.modelo.php:469:            SELECT COUNT(*) AS total
modelos\usuarios.modelo.php:486:                SELECT idUsuario, nombreUsuario, apellidoUsuario, email, rol
modelos\usuarios.modelo.php:499:            SELECT DISTINCT u.idUsuario, u.nombreUsuario, u.apellidoUsuario, u.email, u.rol
modelos\usuarios.modelo.php:511:            SELECT DISTINCT u.idUsuario, u.nombreUsuario, u.apellidoUsuario, u.email, u.rol
modelos\usuarios.modelo.php:532:            SELECT COUNT(*) AS total
modelos\usuarios.modelo.php:550:            SELECT COUNT(*) AS total
modelos\usuarios.modelo.php:567:        $stmt = Conexion::conectar()->prepare("UPDATE usuarios SET pass = :pass, resetPass = 0 WHERE idUsuario = :idUsuario");
modelos\usuarios.modelo.php:576:        $stmt = Conexion::conectar()->prepare("UPDATE usuarios SET ultimaConexion = NOW() WHERE idUsuario = :idUsuario");
modelos\usuarios.modelo.php:585:            INSERT INTO $tabla
modelos\usuarios.modelo.php:606:        $consulta = "UPDATE $tabla SET nombreUsuario = :nombreUsuario, apellidoUsuario = :apellidoUsuario, email = :email, rol = :rol";
modelos\usuarios.modelo.php:639:            UPDATE usuarios
modelos\usuarios.modelo.php:658:            UPDATE usuarios
modelos\usuarios.modelo.php:672:        $stmt = Conexion::conectar()->prepare("UPDATE usuarios SET imgUsuario = :imgUsuario WHERE idUsuario = :idUsuario");
modelos\usuarios.modelo.php:682:            INSERT INTO usuarios_historial
modelos\usuarios.modelo.php:699:            SELECT h.*,
vistas\paginas\inicio.php:18:$sql = "SELECT idCurso, nombreCurso FROM cursos ORDER BY idCurso ASC LIMIT 1";
vistas\paginas\inicio.php:19:$primerCurso = $db->consultas($sql);
vistas\paginas\inicio.php:128:  $primeraMateriaDashboard = $db->consultas('SELECT idSeccion FROM secciones ORDER BY idSeccion ASC LIMIT 1');
vistas\paginas\inicio.php:133:  $primeraMateriaDashboard = $db->consultas("
vistas\paginas\inicio.php:134:    SELECT s.idSeccion
modelos\perfiles.modelo.php:9:            SELECT *,
modelos\perfiles.modelo.php:27:        $existe = $conexion->prepare("SELECT idPerfil FROM perfiles WHERE id_usuario = :id_usuario LIMIT 1");
modelos\perfiles.modelo.php:44:            UPDATE perfiles
modelos\perfiles.modelo.php:72:        $registro = Conexion::conectar()->prepare("INSERT INTO perfiles (id_usuario, dniPerfil, telefonoPerfil, fnacPerfil, domicilioPerfil, provinciaPerfil, contenidoPerfil) VALUES (:id_usuario, :dniPerfil, :telefonoPerfil, :fnacPerfil, :domicilioPerfil, :provinciaPerfil, :contenidoPerfil)");
vistas\paginas\cursos\editar-curso.php:4:$curso = $db->consultas("SELECT * FROM cursos WHERE idCurso = $idCurso");
modelos\panel.modelo.php:45:            $usuariosActivos = self::contar('SELECT COUNT(*) AS total FROM usuarios WHERE activo = 1');
modelos\panel.modelo.php:46:            $usuariosConectados = self::contar('SELECT COUNT(*) AS total FROM usuarios WHERE activo = 1 AND ultimaConexion >= (NOW() - INTERVAL 60 MINUTE)');
modelos\panel.modelo.php:47:            $cursos = self::contar('SELECT COUNT(*) AS total FROM cursos');
modelos\panel.modelo.php:48:            $secciones = self::contar('SELECT COUNT(*) AS total FROM secciones');
modelos\panel.modelo.php:50:                'SELECT COUNT(*) AS total
modelos\panel.modelo.php:60:                'SELECT COUNT(*) AS total
modelos\panel.modelo.php:117:                'SELECT COUNT(DISTINCT s.idSeccion) AS total
modelos\panel.modelo.php:123:                'SELECT COUNT(DISTINCT l.idLeccion) AS total
modelos\panel.modelo.php:130:                'SELECT COUNT(*) AS total
modelos\panel.modelo.php:142:                'SELECT COUNT(*) AS total
modelos\panel.modelo.php:152:                'SELECT COALESCE(ROUND(AVG(c.calificacion), 2), 0) AS total
modelos\panel.modelo.php:199:            'SELECT COUNT(DISTINCT a.id_seccion) AS total
modelos\panel.modelo.php:205:            'SELECT COUNT(DISTINCT l.idLeccion) AS total
modelos\panel.modelo.php:212:            'SELECT COUNT(*) AS total
modelos\panel.modelo.php:218:            'SELECT COUNT(*) AS total
modelos\panel.modelo.php:228:            'SELECT COALESCE(ROUND(AVG(calificacion), 2), 0) AS total
modelos\panel.modelo.php:276:            'SELECT m.idMensaje, m.contenidoMensaje, m.fechaMensaje,
modelos\panel.modelo.php:296:                'SELECT p.idPosteo, p.contenidoPosteo, p.fechaPosteo, p.id_curso, p.id_leccion,
modelos\panel.modelo.php:310:                'SELECT DISTINCT p.idPosteo, p.contenidoPosteo, p.fechaPosteo, p.id_curso, p.id_leccion,
modelos\panel.modelo.php:325:            'SELECT DISTINCT p.idPosteo, p.contenidoPosteo, p.fechaPosteo, p.id_curso, p.id_leccion,
modelos\panel.modelo.php:347:                'SELECT e.idEntregaLeccion, e.id_leccion, e.id_seccion, e.id_curso, e.fechaEntrega, e.urlArchivo,
modelos\panel.modelo.php:361:                'SELECT e.idEntregaLeccion, e.id_leccion, e.id_seccion, e.id_curso,
modelos\panel.modelo.php:377:            'SELECT e.idEntregaLeccion, e.id_leccion, e.id_seccion, e.id_curso,
modelos\panel.modelo.php:404:            'SELECT e.idEntregaLeccion, e.id_leccion, e.id_seccion, e.id_curso,
modelos\panel.modelo.php:430:                'SELECT c.idCalificacion, c.calificacion, c.id_seccion, c.id_modulo,
modelos\panel.modelo.php:444:                'SELECT c.idCalificacion, c.calificacion, c.id_seccion, c.id_modulo,
modelos\panel.modelo.php:459:            'SELECT c.idCalificacion, c.calificacion, c.id_seccion, c.id_modulo,
modelos\panel.modelo.php:506:                SELECT claveNotificacion
modelos\panel.modelo.php:639:                INSERT IGNORE INTO notificaciones_lecturas (id_usuario, claveNotificacion)
modelos\panel.modelo.php:750:                'SELECT COUNT(*) AS total
modelos\panel.modelo.php:760:                'SELECT COUNT(*) AS total
modelos\panel.modelo.php:773:                'SELECT COUNT(*) AS total
modelos\panel.modelo.php:782:                'SELECT COUNT(*) AS total
modelos\panel.modelo.php:788:                'SELECT COUNT(*) AS total
modelos\panel.modelo.php:798:                'SELECT COUNT(*) AS total
modelos\lecciones.modelo.php:95:            'SELECT s.idSeccion, s.tituloSeccion, s.contenidoSeccion, s.bannerSeccion, s.colorInicioBanner, s.colorFinBanner, s.id_curso, s.docente, s.tutor,
modelos\lecciones.modelo.php:117:            'SELECT COUNT(*) AS total
modelos\lecciones.modelo.php:133:            'SELECT * FROM lecciones WHERE idLeccion = :idLeccion LIMIT 1'
modelos\lecciones.modelo.php:146:            'SELECT l.idLeccion, l.nombreLeccion, l.tipoLeccion, l.contenidoLeccion, l.estadoLeccion, l.fechaPublicacionLeccion, l.id_modulo,
modelos\lecciones.modelo.php:171:            'SELECT
modelos\lecciones.modelo.php:187:            'SELECT COUNT(*) AS totalRecursos
modelos\lecciones.modelo.php:197:            'SELECT COUNT(*) AS totalEntregas
modelos\lecciones.modelo.php:206:            'SELECT COALESCE(ROUND(AVG(calificacion), 2), 0) AS promedioNotas
modelos\lecciones.modelo.php:215:            'SELECT COUNT(*) AS pendientes
modelos\lecciones.modelo.php:236:            'SELECT
modelos\lecciones.modelo.php:250:            'SELECT COALESCE(ROUND(AVG(calificacion), 2), 0) AS promedioNotas
modelos\lecciones.modelo.php:261:            'SELECT COUNT(*) AS totalPosts
modelos\lecciones.modelo.php:264:                SELECT idLeccion FROM lecciones
modelos\lecciones.modelo.php:282:            'SELECT idRecursoLeccion, id_leccion, tipoRecurso, tituloRecurso, urlRecurso, creadoPor, fechaRecurso
modelos\lecciones.modelo.php:295:            'SELECT idRecursoLeccion, id_leccion, tipoRecurso, tituloRecurso, urlRecurso, creadoPor, fechaRecurso
modelos\lecciones.modelo.php:308:            'SELECT p.idPosteo, p.id_autor, p.contenidoPosteo, p.fechaPosteo,
modelos\lecciones.modelo.php:323:            'SELECT idEntregaLeccion, id_leccion, id_seccion, id_curso, id_estudiante, urlArchivo, comentarioEntrega, fechaEntrega, estadoEntrega
modelos\lecciones.modelo.php:338:            'SELECT e.idEntregaLeccion, e.id_leccion, e.id_seccion, e.id_curso, e.id_estudiante, e.urlArchivo, e.comentarioEntrega, e.fechaEntrega, e.estadoEntrega,
modelos\lecciones.modelo.php:356:                'SELECT idAdjuntoEntrega, id_entrega, nombreOriginal, rutaArchivo, mimeType, tamanoArchivo, fechaAdjunto
modelos\lecciones.modelo.php:372:            'SELECT DISTINCT u.idUsuario, u.nombreUsuario, u.apellidoUsuario, u.email
modelos\lecciones.modelo.php:388:            'SELECT COUNT(*) AS total
modelos\lecciones.modelo.php:405:            'SELECT l.*, s.idSeccion, s.tituloSeccion, s.id_curso, c.nombreCurso
modelos\lecciones.modelo.php:427:            "INSERT INTO $tabla (nombreLeccion, tipoLeccion, contenidoLeccion, estadoLeccion, fechaPublicacionLeccion, id_modulo)
modelos\lecciones.modelo.php:444:            "UPDATE $tabla
modelos\lecciones.modelo.php:465:        $pdo->prepare('DELETE FROM recursoslecciones WHERE id_leccion = :idLeccion')->execute([':idLeccion' => (int) $idLeccion]);
modelos\lecciones.modelo.php:466:        $pdo->prepare('DELETE FROM posteos WHERE id_leccion = :idLeccion')->execute([':idLeccion' => (int) $idLeccion]);
modelos\lecciones.modelo.php:477:        $pdo->prepare('DELETE FROM entregaslecciones WHERE id_leccion = :idLeccion')->execute([':idLeccion' => (int) $idLeccion]);
modelos\lecciones.modelo.php:478:        $pdo->prepare('DELETE FROM calificaciones WHERE id_modulo = :idLeccion')->execute([':idLeccion' => (int) $idLeccion]);
modelos\lecciones.modelo.php:480:        $stmt = $pdo->prepare('DELETE FROM lecciones WHERE idLeccion = :idLeccion');
modelos\lecciones.modelo.php:488:            "INSERT INTO $tabla (id_leccion, tipoRecurso, tituloRecurso, urlRecurso, creadoPor)
modelos\lecciones.modelo.php:502:            'UPDATE recursoslecciones
modelos\lecciones.modelo.php:518:            'DELETE FROM recursoslecciones WHERE idRecursoLeccion = :idRecursoLeccion'
modelos\lecciones.modelo.php:527:            'INSERT INTO posteos (id_autor, contenidoPosteo, fechaPosteo, id_curso, id_leccion)
modelos\lecciones.modelo.php:541:            'INSERT INTO entregaslecciones
modelos\lecciones.modelo.php:545:             ON DUPLICATE KEY UPDATE
modelos\lecciones.modelo.php:580:                'INSERT INTO entregaslecciones
modelos\lecciones.modelo.php:584:                 ON DUPLICATE KEY UPDATE
modelos\lecciones.modelo.php:603:                'SELECT idEntregaLeccion
modelos\lecciones.modelo.php:620:                    'DELETE FROM entregaslecciones_adjuntos WHERE id_entrega = :idEntregaLeccion'
modelos\lecciones.modelo.php:626:                    'INSERT INTO entregaslecciones_adjuntos
modelos\lecciones.modelo.php:656:            'SELECT idEntregaLeccion, id_leccion, id_seccion, id_curso, id_estudiante, urlArchivo, comentarioEntrega, fechaEntrega, estadoEntrega
modelos\lecciones.modelo.php:678:                    'DELETE FROM entregaslecciones_adjuntos WHERE id_entrega = :idEntregaLeccion'
modelos\lecciones.modelo.php:685:                'DELETE FROM entregaslecciones WHERE idEntregaLeccion = :idEntregaLeccion'
modelos\notificaciones.modelo.php:41:            'SELECT DISTINCT u.idUsuario, u.nombreUsuario, u.apellidoUsuario, u.email
modelos\notificaciones.modelo.php:61:                'INSERT IGNORE INTO notificaciones
modelos\notificaciones.modelo.php:89:                'SELECT idNotificacion, id_usuario, tipoNotificacion, referenciaTipo, referenciaId,
vistas\paginas\materias\crear-materia.php:5:  : $db->consultas("SELECT * FROM cursos WHERE activo = 1 ORDER BY nombreCurso ASC");
modelos\cursos.modelo.php:14:        $pdo->exec('UPDATE asignacioncursos SET fechaAlta=COALESCE(fechaAlta,NOW()) WHERE fechaAlta IS NULL');
modelos\cursos.modelo.php:42:            $cursoStmt = $pdo->prepare('SELECT * FROM cursos WHERE idCurso = :idCurso LIMIT 1');
modelos\cursos.modelo.php:49:            $insertCurso = $pdo->prepare('INSERT INTO cursos
modelos\cursos.modelo.php:66:            $secciones = $pdo->prepare('SELECT * FROM secciones WHERE id_curso = :idCurso ORDER BY idSeccion');
modelos\cursos.modelo.php:69:                $insertSeccion = $pdo->prepare('INSERT INTO secciones
modelos\cursos.modelo.php:80:                $lecciones = $pdo->prepare('SELECT * FROM lecciones WHERE id_modulo = :idSeccion ORDER BY idLeccion');
modelos\cursos.modelo.php:83:                    $insertLeccion = $pdo->prepare('INSERT INTO lecciones
modelos\cursos.modelo.php:92:                    $recursos = $pdo->prepare('SELECT * FROM recursoslecciones WHERE id_leccion = :idLeccion ORDER BY idRecursoLeccion');
modelos\cursos.modelo.php:95:                        $insertRecurso = $pdo->prepare('INSERT INTO recursoslecciones
modelos\cursos.modelo.php:108:                    $actividades = $pdo->prepare('SELECT * FROM actividades WHERE id_curso = :idCurso AND id_seccion = :idSeccion AND COALESCE(esPlantilla, 0) = 0 ORDER BY idActividad');
modelos\cursos.modelo.php:112:                        $insertActividad = $pdo->prepare('INSERT INTO actividades
modelos\cursos.modelo.php:128:                        $preguntas = $pdo->prepare('SELECT * FROM actividades_preguntas WHERE id_actividad = :id ORDER BY orden, idPregunta');
modelos\cursos.modelo.php:131:                            $insertPregunta = $pdo->prepare('INSERT INTO actividades_preguntas
modelos\cursos.modelo.php:142:                            $opciones = $pdo->prepare('SELECT * FROM actividades_opciones WHERE id_pregunta = :id ORDER BY orden, idOpcion');
modelos\cursos.modelo.php:145:                                $pdo->prepare('INSERT INTO actividades_opciones (id_pregunta, textoOpcion, esCorrecta, orden) VALUES (?, ?, ?, ?)')
modelos\cursos.modelo.php:167:            SELECT c.*,
modelos\cursos.modelo.php:185:            SELECT c.*,
modelos\cursos.modelo.php:206:            SELECT DISTINCT c.*,
modelos\cursos.modelo.php:229:            SELECT DISTINCT c.*,
modelos\cursos.modelo.php:253:            SELECT COUNT(*) AS total
modelos\cursos.modelo.php:269:            SELECT COUNT(*) AS total
modelos\cursos.modelo.php:287:            SELECT COUNT(*) AS total
modelos\cursos.modelo.php:302:            SELECT s.idSeccion, s.tituloSeccion, s.contenidoSeccion, s.id_curso, s.docente, s.tutor, s.activo,
modelos\cursos.modelo.php:327:            SELECT s.idSeccion, s.tituloSeccion, s.contenidoSeccion, s.id_curso, s.docente, s.tutor, s.activo,
modelos\cursos.modelo.php:356:        $registro = Conexion::conectar()->prepare("INSERT INTO $tabla(nombreCurso, contenidoCurso, estado, fechaInicioCurso, fechaFinCurso, horarioCurso, creadoPor, responsable, modalidadCalificacion, intensificacionActiva) VALUES(:nombreCurso, :contenidoCurso, :estado, :fechaInicioCurso, :fechaFinCurso, :horarioCurso, :creadoPor, :responsable, :modalidadCalificacion, :intensificacionActiva)");
modelos\cursos.modelo.php:384:        $registro = Conexion::conectar()->prepare("UPDATE $tabla SET nombreCurso=:nombreCurso, contenidoCurso=:contenidoCurso, estado=:estado, fechaInicioCurso=:fechaInicioCurso, fechaFinCurso=:fechaFinCurso, horarioCurso=:horarioCurso, modalidadCalificacion=:modalidadCalificacion, intensificacionActiva=:intensificacionActiva WHERE idCurso=:idCurso");
modelos\cursos.modelo.php:407:        $stmt=Conexion::conectar()->prepare('SELECT (SELECT COUNT(*) FROM calificaciones WHERE id_curso=:curso1)+(SELECT COUNT(*) FROM evaluaciones_calificaciones ec INNER JOIN evaluaciones e ON e.idEvaluacion=ec.id_evaluacion WHERE e.id_curso=:curso2)+(SELECT COUNT(*) FROM cierres_periodo_calificaciones cp INNER JOIN secciones s ON s.idSeccion=cp.id_seccion WHERE s.id_curso=:curso3) total');
modelos\cursos.modelo.php:414:            UPDATE cursos
modelos\cursos.modelo.php:433:        $stmt = Conexion::conectar()->prepare('UPDATE cursos SET responsable = :idResponsable WHERE idCurso = :idCurso');
modelos\cursos.modelo.php:457:                $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM {$tabla} WHERE {$columna} = :idCurso");
modelos\cursos.modelo.php:477:        $stmt = Conexion::conectar()->prepare('DELETE FROM cursos WHERE idCurso = :idCurso');
modelos\cursos.modelo.php:488:            SELECT u.*
modelos\cursos.modelo.php:493:                  SELECT 1
modelos\cursos.modelo.php:508:        $reactivar=Conexion::conectar()->prepare("UPDATE $tabla SET estadoInscripcion='ACTIVA',fechaAlta=NOW(),fechaBaja=NULL,motivoBaja=NULL WHERE id_estudiante=:idUsuario AND id_seccion=:idCurso AND estadoInscripcion='BAJA'");
modelos\cursos.modelo.php:512:            INSERT INTO $tabla (id_estudiante,id_seccion,estadoInscripcion,fechaAlta)
modelos\cursos.modelo.php:513:            SELECT u.idUsuario, :idCurso, 'ACTIVA', NOW()
modelos\cursos.modelo.php:519:                SELECT 1
modelos\cursos.modelo.php:547:            UPDATE asignacioncursos SET estadoInscripcion='BAJA',fechaBaja=NOW(),motivoBaja=:motivo
modelos\mensajes.modelo.php:45:                SELECT s.idSeccion, s.tituloSeccion, c.nombreCurso
modelos\mensajes.modelo.php:56:                SELECT s.idSeccion, s.tituloSeccion, c.nombreCurso
modelos\mensajes.modelo.php:73:            SELECT DISTINCT u.idUsuario
modelos\mensajes.modelo.php:102:                INSERT INTO mensajes (id_remitente, id_destinatario, contenidoMensaje, fechaMensaje)
modelos\mensajes.modelo.php:121:                INSERT INTO mensajes_participantes
modelos\mensajes.modelo.php:151:                    INSERT INTO mensajes_adjuntos
modelos\mensajes.modelo.php:179:            SELECT mp.idMensajeParticipante, mp.id_mensaje, mp.id_usuario, mp.rolParticipante, mp.leido, mp.fechaLeido,
modelos\mensajes.modelo.php:183:                   (SELECT COUNT(*) FROM mensajes_adjuntos ma WHERE ma.id_mensaje = m.idMensaje) AS totalAdjuntos,
modelos\mensajes.modelo.php:184:                   (SELECT COUNT(*) FROM mensajes_participantes mp2
modelos\mensajes.modelo.php:200:            SELECT mp.idMensajeParticipante, mp.id_mensaje, mp.id_usuario, mp.rolParticipante, mp.leido, mp.fechaLeido,
modelos\mensajes.modelo.php:203:                   (SELECT COUNT(*) FROM mensajes_adjuntos ma WHERE ma.id_mensaje = m.idMensaje) AS totalAdjuntos,
modelos\mensajes.modelo.php:204:                   (SELECT COUNT(*) FROM mensajes_participantes mp2
modelos\mensajes.modelo.php:208:                   (SELECT GROUP_CONCAT(CONCAT(u.nombreUsuario, " ", u.apellidoUsuario) SEPARATOR ", ")
modelos\mensajes.modelo.php:225:            SELECT mp.idMensajeParticipante, mp.id_mensaje, mp.id_usuario, mp.rolParticipante, mp.leido, mp.fechaLeido,
modelos\mensajes.modelo.php:229:                   (SELECT COUNT(*) FROM mensajes_adjuntos ma WHERE ma.id_mensaje = m.idMensaje) AS totalAdjuntos
modelos\mensajes.modelo.php:242:            SELECT COUNT(*) AS total
modelos\mensajes.modelo.php:257:            SELECT COUNT(*) AS total
modelos\mensajes.modelo.php:272:            SELECT COUNT(*) AS total
modelos\mensajes.modelo.php:286:            SELECT COUNT(*) AS total
modelos\mensajes.modelo.php:346:            SELECT mp.idMensajeParticipante, mp.id_mensaje, mp.id_usuario, mp.rolParticipante, mp.leido, mp.fechaLeido,
modelos\mensajes.modelo.php:375:            SELECT idAdjunto, id_mensaje, nombreOriginal, nombreGuardado, rutaArchivo, mimeType, tamanoArchivo, fechaAdjunto
modelos\mensajes.modelo.php:388:            SELECT u.idUsuario, u.nombreUsuario, u.apellidoUsuario, u.rol, mp.leido, mp.enPapelera
modelos\mensajes.modelo.php:404:            UPDATE mensajes_participantes
modelos\mensajes.modelo.php:420:            UPDATE mensajes_participantes
modelos\mensajes.modelo.php:436:            UPDATE mensajes_participantes
modelos\mensajes.modelo.php:451:            UPDATE mensajes_participantes
modelos\mensajes.modelo.php:467:            UPDATE mensajes_participantes
modelos\mensajes.modelo.php:478:        $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM mensajes_participantes WHERE id_mensaje = :idMensaje AND eliminado = 0');
modelos\mensajes.modelo.php:484:            $pdo->prepare('DELETE FROM mensajes_adjuntos WHERE id_mensaje = :idMensaje')->execute([':idMensaje' => (int) $idMensaje]);
modelos\mensajes.modelo.php:485:            $pdo->prepare('DELETE FROM mensajes_participantes WHERE id_mensaje = :idMensaje')->execute([':idMensaje' => (int) $idMensaje]);
modelos\mensajes.modelo.php:486:            $pdo->prepare('DELETE FROM mensajes WHERE idMensaje = :idMensaje')->execute([':idMensaje' => (int) $idMensaje]);
modelos\materias.modelo.php:9:            SELECT s.*,
modelos\materias.modelo.php:26:        $registro = Conexion::conectar()->prepare("INSERT INTO $tabla (tituloSeccion, contenidoSeccion, id_curso, docente, tutor, bannerSeccion, colorInicioBanner, colorFinBanner, creadoPor) VALUES (:tituloSeccion, :contenidoSeccion, :id_curso, :docente, :tutor, :bannerSeccion, :colorInicioBanner, :colorFinBanner, :creadoPor)");
modelos\materias.modelo.php:50:            UPDATE $tabla
modelos\materias.modelo.php:87:            $stmt = Conexion::conectar()->prepare("SELECT idSeccion, tituloSeccion, contenidoSeccion,id_curso, docente, tutor, cursos.nombreCurso, usuarios.nombreUsuario , usuarios.apellidoUsuario FROM secciones JOIN cursos ON secciones.id_curso = cursos.idCurso JOIN usuarios ON secciones.docente = usuarios.idUsuario WHERE secciones.id_curso= $valor ORDER BY tituloSeccion ASC");
modelos\materias.modelo.php:108:            SELECT s.idSeccion, s.tituloSeccion, s.contenidoSeccion, s.id_curso, s.docente, s.tutor,
modelos\materias.modelo.php:140:            UPDATE secciones
modelos\materias.modelo.php:160:            UPDATE secciones
modelos\materias.modelo.php:187:                $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM {$tabla} WHERE {$columna} = :idSeccion");
modelos\materias.modelo.php:206:        $stmt = Conexion::conectar()->prepare('DELETE FROM secciones WHERE idSeccion = :idSeccion');
vistas\paginas\actividades\_formulario-actividad-config.php:8:  ? (new Conexion())->consultas("
vistas\paginas\actividades\_formulario-actividad-config.php:9:      SELECT s.idSeccion, s.tituloSeccion, s.id_curso, c.nombreCurso
vistas\paginas\cursos\detalle-curso.php:22:$sql = "SELECT * FROM asignacioncursos
vistas\paginas\cursos\detalle-curso.php:27:$cursantes = $db->consultas($sql);
vistas\paginas\cursos\detalle-curso.php:28:$cursantesBaja=$esAdmin?$db->consultas("SELECT a.*,u.nombreUsuario,u.apellidoUsuario,u.email FROM asignacioncursos a INNER JOIN usuarios u ON u.idUsuario=a.id_estudiante WHERE a.id_seccion=$idCurso AND a.estadoInscripcion='BAJA' ORDER BY a.fechaBaja DESC"):[];
vistas\paginas\materias\editar-materia.php:4:$materia = $db->consultas("SELECT * FROM secciones WHERE idSeccion = $idSeccion");
vistas\paginas\materias\editar-materia.php:17:$cursos = $db->consultas("SELECT * FROM cursos ORDER BY nombreCurso ASC");
```

## Entradas POST y GET

```text
controladores\asistencias.controller.php:13:    public static function crtProcesar(){if(!isset($_POST['accion_asistencia']))return null;$accion=$_POST['accion_asistencia'];$idSeccion=(int)($_POST['id_seccion']??0);if(!self::puede($idSeccion)){$_SESSION['error_message']='No tenés permisos para gestionar esta asistencia.';return 0;}if($accion==='crear_clase'){$fecha=(string)($_POST['fechaClase']??'');$d=DateTime::createFromFormat('Y-m-d',$fecha);$seccion=ControladorLecciones::crtBuscarSeccionPorId($idSeccion);if(!$seccion||!$d||$d->format('Y-m-d')!==$fecha){$_SESSION['error_message']='Seleccioná una fecha válida.';return 0;}$id=ModeloAsistencias::mdlCrearClase($idSeccion,(int)$seccion['id_curso'],$fecha,trim((string)($_POST['tema']??'')),(int)($_SESSION['usuario']['id']??0));$_SESSION['success_message']='Clase preparada con todos los estudiantes presentes.';return $id;}if($accion==='guardar_asistencia'){$idClase=(int)($_POST['id_clase']??0);$clase=self::crtClase($idClase);if(!$clase||(int)$clase['id_seccion']!==$idSeccion)return 0;$r=ModeloAsistencias::mdlGuardar($idClase,(array)($_POST['estados']??[]),(array)($_POST['observaciones']??[]),(int)($_SESSION['usuario']['id']??0));$_SESSION[$r==='ok'?'success_message':'error_message']=$r==='ok'?'Asistencia guardada correctamente.':'No se pudo guardar la asistencia.';return $idClase;}return null;}
controladores\auth.controller.php:251:        if (!isset($_POST['login_email'], $_POST['login_pass'])) {
controladores\auth.controller.php:255:        $email = trim((string) $_POST['login_email']);
controladores\auth.controller.php:256:        $password = (string) $_POST['login_pass'];
controladores\auth.controller.php:286:        if (!isset($_POST['forgot_email'])) {
controladores\auth.controller.php:295:        $email = trim((string) $_POST['forgot_email']);
controladores\actividades.controller.php:37:        $busqueda = trim((string) ($_GET['q'] ?? ''));
controladores\actividades.controller.php:38:        $tipo = trim((string) ($_GET['tipo'] ?? ''));
controladores\actividades.controller.php:39:        $visibilidad = trim((string) ($_GET['visibilidad'] ?? ''));
controladores\actividades.controller.php:40:        $estado = trim((string) ($_GET['estado'] ?? ''));
controladores\actividades.controller.php:41:        $soloDestacadas = isset($_GET['destacadas']) && $_GET['destacadas'] !== '' ? 1 : '';
controladores\actividades.controller.php:56:        $busqueda = trim((string) ($_GET['q'] ?? ''));
controladores\actividades.controller.php:57:        $tipo = trim((string) ($_GET['tipo'] ?? ''));
controladores\actividades.controller.php:58:        $alcance = trim((string) ($_GET['alcance'] ?? ''));
controladores\actividades.controller.php:59:        $estado = trim((string) ($_GET['estado'] ?? ''));
controladores\actividades.controller.php:324:        $accion = trim((string) ($_POST['accion_actividad'] ?? ''));
controladores\actividades.controller.php:370:        $idActividad = (int) ($_POST['idActividad'] ?? 0);
controladores\actividades.controller.php:391:        $idActividad = (int) ($_POST['idActividad'] ?? 0);
controladores\actividades.controller.php:399:        $tipo = self::valorPermitido($_POST['tipoActividad'] ?? '', array_keys(self::tiposDisponibles()), 'multiple_choice');
controladores\actividades.controller.php:400:        $visibilidad = self::valorPermitido($_POST['visibilidad'] ?? '', array_keys(self::visibilidadesDisponibles()), 'privada');
controladores\actividades.controller.php:401:        $estado = self::valorPermitido($_POST['estadoActividad'] ?? '', array_keys(self::estadosDisponibles()), 'BORRADOR');
controladores\actividades.controller.php:402:        $titulo = trim((string) ($_POST['tituloActividad'] ?? ''));
controladores\actividades.controller.php:409:        $idSeccion = (int) ($_POST['id_seccion'] ?? 0);
controladores\actividades.controller.php:442:            'descripcionActividad' => trim((string) ($_POST['descripcionActividad'] ?? '')),
controladores\actividades.controller.php:450:            'intentosPermitidos' => max(0, (int) ($_POST['intentosPermitidos'] ?? 1)),
controladores\actividades.controller.php:451:            'permiteVisitantes' => isset($_POST['permiteVisitantes']) ? 1 : 0,
controladores\actividades.controller.php:454:            'recursoExternoUrl' => trim((string) ($_POST['recursoExternoUrl'] ?? '')),
controladores\actividades.controller.php:455:            'recursoExternoEmbed' => trim((string) ($_POST['recursoExternoEmbed'] ?? '')),
controladores\actividades.controller.php:481:        $idActividad = (int) ($_POST['idActividad'] ?? 0);
controladores\actividades.controller.php:521:        $idActividad = (int) ($_POST['idActividad'] ?? 0);
controladores\actividades.controller.php:550:        $idActividad = (int) ($_POST['idActividad'] ?? 0);
controladores\actividades.controller.php:581:        $idActividad = (int) ($_POST['idActividad'] ?? 0);
controladores\actividades.controller.php:608:        $idActividad = (int) ($_POST['idActividad'] ?? 0);
controladores\actividades.controller.php:633:        $idActividad = (int) ($_POST['idActividad'] ?? 0);
controladores\actividades.controller.php:648:        $respuestasPost = $_POST['respuesta'] ?? [];
controladores\actividades.controller.php:678:            'nombreVisitante' => $idUsuario > 0 ? '' : trim((string) ($_POST['nombreVisitante'] ?? '')),
controladores\actividades.controller.php:679:            'emailVisitante' => $idUsuario > 0 ? '' : trim((string) ($_POST['emailVisitante'] ?? '')),
controladores\actividades.controller.php:744:        $payload = json_decode((string) ($_POST['preguntasPayload'] ?? ''), true, 64);
controladores\actividades.controller.php:774:            $textos = $_POST['preguntaTexto'] ?? [];
controladores\actividades.controller.php:775:            $respuestas = $_POST['respuestaCorrecta'] ?? [];
controladores\actividades.controller.php:776:            $puntajes = $_POST['puntajePregunta'] ?? [];
controladores\actividades.controller.php:777:            $pistas = $_POST['pistaPregunta'] ?? [];
controladores\actividades.controller.php:778:            $explicaciones = $_POST['explicacionError'] ?? [];
controladores\actividades.controller.php:779:            $codigos = $_POST['codigoBase'] ?? [];
controladores\actividades.controller.php:780:            $lenguajesCodigo = $_POST['lenguajeCodigo'] ?? [];
controladores\actividades.controller.php:781:            $variantesCodigo = $_POST['variantesCodigo'] ?? [];
controladores\actividades.controller.php:782:            $opciones = $_POST['opciones'] ?? [];
controladores\actividades.controller.php:783:            $correctas = $_POST['opcionCorrecta'] ?? [];
controladores\actividades.controller.php:1066:        $ruta = trim((string) ($_POST['ruta_retorno'] ?? ''));
controladores\lecciones.controller.php:131:        $accion = trim((string) ($_POST['accion'] ?? ''));
controladores\lecciones.controller.php:163:        if (!isset($_POST['nombreLeccion'], $_POST['id_modulo'], $_POST['tipoLeccion'])) {
controladores\lecciones.controller.php:172:        $nombreLeccion = trim((string) $_POST['nombreLeccion']);
controladores\lecciones.controller.php:173:        $contenidoLeccion = trim((string) ($_POST['contenidoLeccion'] ?? ''));
controladores\lecciones.controller.php:174:        $tipoLeccion = strtoupper(trim((string) $_POST['tipoLeccion']));
controladores\lecciones.controller.php:175:        $estadoLeccion = self::normalizarEstadoLeccion($_POST['estadoLeccion'] ?? 'PUBLICADA');
controladores\lecciones.controller.php:176:        $fechaPublicacionLeccion = self::normalizarFechaPublicacion($_POST['fechaPublicacionLeccion'] ?? '');
controladores\lecciones.controller.php:177:        $idModulo = (int) $_POST['id_modulo'];
controladores\lecciones.controller.php:228:        if (!isset($_POST['idLeccion'], $_POST['nombreLeccion'], $_POST['tipoLeccion'])) {
controladores\lecciones.controller.php:237:        $idLeccion = (int) $_POST['idLeccion'];
controladores\lecciones.controller.php:238:        $nombreLeccion = trim((string) $_POST['nombreLeccion']);
controladores\lecciones.controller.php:239:        $contenidoLeccion = trim((string) ($_POST['contenidoLeccion'] ?? ''));
controladores\lecciones.controller.php:240:        $tipoLeccion = strtoupper(trim((string) $_POST['tipoLeccion']));
controladores\lecciones.controller.php:244:        $estadoLeccion = self::normalizarEstadoLeccion($_POST['estadoLeccion'] ?? $estadoActual, $estadoActual ?: 'PUBLICADA');
controladores\lecciones.controller.php:245:        $fechaPublicacionLeccion = self::normalizarFechaPublicacion($_POST['fechaPublicacionLeccion'] ?? '');
controladores\lecciones.controller.php:304:        if (!isset($_POST['idLeccion'])) {
controladores\lecciones.controller.php:313:        $idLeccion = (int) $_POST['idLeccion'];
controladores\lecciones.controller.php:350:        if (!isset($_POST['id_leccion'])) {
controladores\lecciones.controller.php:359:        $idLeccion = (int) $_POST['id_leccion'];
controladores\lecciones.controller.php:401:        if (!isset($_POST['idRecursoLeccion'], $_POST['tipoRecurso'], $_POST['tituloRecurso'], $_POST['id_leccion'])) {
controladores\lecciones.controller.php:410:        $idRecurso = (int) $_POST['idRecursoLeccion'];
controladores\lecciones.controller.php:411:        $idLeccion = (int) $_POST['id_leccion'];
controladores\lecciones.controller.php:412:        $tipoRecurso = strtoupper(trim((string) $_POST['tipoRecurso']));
controladores\lecciones.controller.php:413:        $tituloRecurso = trim((string) $_POST['tituloRecurso']);
controladores\lecciones.controller.php:432:        $urlRecurso = trim((string) ($_POST['urlRecurso'] ?? $recurso['urlRecurso']));
controladores\lecciones.controller.php:464:        if (!isset($_POST['idRecursoLeccion'])) {
controladores\lecciones.controller.php:473:        $idRecurso = (int) $_POST['idRecursoLeccion'];
controladores\lecciones.controller.php:502:        if (!isset($_POST['id_leccion'], $_POST['contenidoPosteo'])) {
controladores\lecciones.controller.php:506:        $leccion = self::crtBuscarLeccionPorId((int) $_POST['id_leccion']);
controladores\lecciones.controller.php:515:            $idCurso = (int) ($_POST['id_curso'] ?? 0);
controladores\lecciones.controller.php:522:        $contenido = trim((string) $_POST['contenidoPosteo']);
controladores\lecciones.controller.php:532:            'id_curso' => (int) ($_POST['id_curso'] ?? 0),
controladores\lecciones.controller.php:547:        if (!isset($_POST['id_leccion'], $_POST['id_seccion'], $_POST['id_curso'])) {
controladores\lecciones.controller.php:557:        if (!ModeloLecciones::mdlEstudianteEnCurso($idEstudiante, (int) $_POST['id_curso'])) {
controladores\lecciones.controller.php:562:        $leccion = self::crtBuscarLeccionPorId((int) $_POST['id_leccion']);
controladores\lecciones.controller.php:568:        $entregaAnterior = self::crtBuscarEntregaPorLeccionEstudiante((int) $_POST['id_leccion'], $idEstudiante);
controladores\lecciones.controller.php:570:        $comentarioEntrega = trim((string) ($_POST['comentarioEntrega'] ?? ''));
controladores\lecciones.controller.php:607:            'id_leccion' => (int) $_POST['id_leccion'],
controladores\lecciones.controller.php:608:            'id_seccion' => (int) $_POST['id_seccion'],
controladores\lecciones.controller.php:609:            'id_curso' => (int) $_POST['id_curso'],
controladores\lecciones.controller.php:641:        if (!isset($_POST['id_leccion'], $_POST['id_seccion'], $_POST['id_curso'])) {
controladores\lecciones.controller.php:651:        $idLeccion = (int) $_POST['id_leccion'];
controladores\lecciones.controller.php:652:        $idSeccion = (int) $_POST['id_seccion'];
controladores\lecciones.controller.php:781:        if ($campoMultiple !== '' && isset($_POST[$campoMultiple])) {
controladores\lecciones.controller.php:782:            $entradaMultiple = $_POST[$campoMultiple];
controladores\lecciones.controller.php:786:        if (isset($_POST[$campoSimple])) {
controladores\lecciones.controller.php:787:            $entradaSimple = $_POST[$campoSimple];
controladores\lecciones.controller.php:860:        $tituloBase = trim((string) ($_POST[$campos['titulo']] ?? ''));
controladores\lecciones.controller.php:863:        $titulosArchivos = isset($campos['titulosArchivos'], $_POST[$campos['titulosArchivos']])
controladores\lecciones.controller.php:864:            ? (array) $_POST[$campos['titulosArchivos']]
controladores\lecciones.controller.php:866:        $titulosUrls = isset($campos['titulosUrls'], $_POST[$campos['titulosUrls']])
controladores\lecciones.controller.php:867:            ? (array) $_POST[$campos['titulosUrls']]
controladores\cursos.controller.php:60:        if (isset($_POST["nombreCurso"])) {
controladores\cursos.controller.php:68:            $modalidadNueva=in_array(($_POST['modalidadCalificacion']??''),['UNICO','DOS_TRAMOS'],true)?$_POST['modalidadCalificacion']:'DOS_TRAMOS';
controladores\cursos.controller.php:71:                "nombreCurso"       => $_POST["nombreCurso"],
controladores\cursos.controller.php:72:                "contenidoCurso"    => $_POST["contenidoCurso"],
controladores\cursos.controller.php:73:                "estado"            => $_POST["estado"],
controladores\cursos.controller.php:74:                "fechaInicioCurso"  => $_POST["fechaInicioCurso"],
controladores\cursos.controller.php:75:                "fechaFinCurso"     => $_POST["fechaFinCurso"],
controladores\cursos.controller.php:76:                "horarioCurso"      => $_POST["horarioCurso"],
controladores\cursos.controller.php:80:                "intensificacionActiva" => isset($_POST['intensificacionActiva'])?1:0
controladores\cursos.controller.php:96:        if (isset($_POST["nombreCurso"])) {
controladores\cursos.controller.php:98:            $idCurso = (int) ($_POST['idCurso'] ?? 0);
controladores\cursos.controller.php:106:            $modalidadNueva=in_array(($_POST['modalidadCalificacion']??''),['UNICO','DOS_TRAMOS'],true)?$_POST['modalidadCalificacion']:'DOS_TRAMOS';
controladores\cursos.controller.php:114:                "nombreCurso"       => $_POST["nombreCurso"],
controladores\cursos.controller.php:115:                "contenidoCurso"    => $_POST["contenidoCurso"],
controladores\cursos.controller.php:116:                "estado"            => $_POST["estado"],
controladores\cursos.controller.php:117:                "fechaInicioCurso"  => $_POST["fechaInicioCurso"],
controladores\cursos.controller.php:118:                "fechaFinCurso"     => $_POST["fechaFinCurso"],
controladores\cursos.controller.php:119:                "horarioCurso"      => $_POST["horarioCurso"],
controladores\cursos.controller.php:121:                "intensificacionActiva" => isset($_POST['intensificacionActiva'])?1:0
controladores\cursos.controller.php:136:        $accion = trim((string) ($_POST['accion_curso'] ?? ''));
controladores\cursos.controller.php:146:        $idCurso = (int) ($_POST['idCurso'] ?? 0);
controladores\cursos.controller.php:154:            $idResponsable = $accion === 'quitar_docente_curso' ? 0 : (int) ($_POST['idResponsable'] ?? 0);
controladores\cursos.controller.php:172:            $motivo = trim((string) ($_POST['motivoBaja'] ?? ''));
controladores\cursos.controller.php:212:        if (($_POST['accion_curso'] ?? '') !== 'duplicar_curso') {
controladores\cursos.controller.php:216:        $idCurso = (int) ($_POST['idCurso'] ?? 0);
controladores\cursos.controller.php:222:        $nombre = trim((string) ($_POST['nombreCursoCopia'] ?? ''));
controladores\cursos.controller.php:223:        $inicio = trim((string) ($_POST['fechaInicioCursoCopia'] ?? ''));
controladores\cursos.controller.php:224:        $fin = trim((string) ($_POST['fechaFinCursoCopia'] ?? ''));
controladores\cursos.controller.php:250:        if (isset($_POST["idUsuarios"])) {
controladores\cursos.controller.php:257:            $idCurso = (int) ($_POST['idCurso'] ?? ($_GET['idCurso'] ?? 0));
controladores\cursos.controller.php:258:            $idUsuarios = array_values(array_unique(array_filter(array_map('intval', (array) $_POST['idUsuarios']))));
controladores\cursos.controller.php:295:        if (!isset($_POST['accion_curso']) || $_POST['accion_curso'] !== 'quitar_estudiante') {
controladores\cursos.controller.php:304:        $idCurso = (int) ($_POST['idCurso'] ?? ($_GET['idCurso'] ?? 0));
controladores\cursos.controller.php:305:        $idUsuario = (int) ($_POST['idUsuario'] ?? 0);
controladores\mensajes.controller.php:98:        $accion = trim((string) ($_POST['accion'] ?? $_GET['accion'] ?? ''));
controladores\mensajes.controller.php:125:        $contenido = self::limpiarMensaje($_POST['contenidoMensaje'] ?? '');
controladores\mensajes.controller.php:126:        $destinatarios = $_POST['id_destinatarios'] ?? [];
controladores\mensajes.controller.php:127:        $seccionDestino = (int) ($_POST['id_seccion_destino'] ?? 0);
controladores\mensajes.controller.php:128:        $idMensajeRespuesta = (int) ($_POST['id_mensaje_respuesta'] ?? 0);
controladores\mensajes.controller.php:300:        $idMensaje = (int) ($_POST['id_mensaje'] ?? $_GET['id_mensaje'] ?? 0);
controladores\mensajes.controller.php:317:        $idMensaje = (int) ($_POST['id_mensaje'] ?? $_GET['id_mensaje'] ?? 0);
controladores\mensajes.controller.php:334:        $idMensaje = (int) ($_POST['id_mensaje'] ?? $_GET['id_mensaje'] ?? 0);
controladores\mensajes.controller.php:351:        $idMensaje = (int) ($_POST['id_mensaje'] ?? $_GET['id_mensaje'] ?? 0);
controladores\mensajes.controller.php:368:        $idMensaje = (int) ($_POST['id_mensaje'] ?? $_GET['id_mensaje'] ?? 0);
controladores\materias.controller.php:66:        if (isset($_POST["tituloSeccion"])) {
controladores\materias.controller.php:77:                "tituloSeccion" => $_POST["tituloSeccion"],
controladores\materias.controller.php:78:                "contenidoSeccion" => $_POST["contenidoSeccion"],
controladores\materias.controller.php:79:                "id_curso" => $_POST["id_curso"],
controladores\materias.controller.php:80:                "docente" => $_POST["docente"],
controladores\materias.controller.php:81:                "tutor" => $_POST["tutor"],
controladores\materias.controller.php:83:                "colorInicioBanner" => trim((string) ($_POST["colorInicioBanner"] ?? '#0f172a')),
controladores\materias.controller.php:84:                "colorFinBanner" => trim((string) ($_POST["colorFinBanner"] ?? '#1d4ed8')),
controladores\materias.controller.php:128:        if (isset($_POST["tituloSeccion"], $_POST["idSeccion"])) {
controladores\materias.controller.php:130:            $materiaActual = self::crtBuscarMateriaPorId((int) $_POST["idSeccion"]);
controladores\materias.controller.php:139:                $esDocenteAsignado = ControladorLecciones::crtSeccionAsignadaDocente((int) $_POST["idSeccion"], $idDocente);
controladores\materias.controller.php:147:                $_POST['id_curso'] = $materiaActual['id_curso'];
controladores\materias.controller.php:148:                $_POST['docente'] = $materiaActual['docente'];
controladores\materias.controller.php:166:                "idSeccion" => (int) $_POST["idSeccion"],
controladores\materias.controller.php:167:                "tituloSeccion" => $_POST["tituloSeccion"],
controladores\materias.controller.php:168:                "contenidoSeccion" => $_POST["contenidoSeccion"],
controladores\materias.controller.php:169:                "id_curso" => $_POST["id_curso"],
controladores\materias.controller.php:170:                "docente" => $_POST["docente"],
controladores\materias.controller.php:171:                "tutor" => $_POST["tutor"],
controladores\materias.controller.php:173:                "colorInicioBanner" => trim((string) ($_POST["colorInicioBanner"] ?? '#0f172a')),
controladores\materias.controller.php:174:                "colorFinBanner" => trim((string) ($_POST["colorFinBanner"] ?? '#1d4ed8')),
controladores\materias.controller.php:203:        $accion = trim((string) ($_POST['accion_materia'] ?? ''));
controladores\materias.controller.php:213:        $idSeccion = (int) ($_POST['idSeccion'] ?? 0);
controladores\materias.controller.php:221:            $idDocente = (int) ($_POST['idDocente'] ?? 0);
controladores\materias.controller.php:222:            $idAdjunto = (int) ($_POST['idAdjunto'] ?? 0);
controladores\materias.controller.php:240:            $motivo = trim((string) ($_POST['motivoBaja'] ?? ''));
controladores\calificaciones.controller.php:27:        $accion = trim((string) ($_POST['accion'] ?? ''));
controladores\calificaciones.controller.php:72:        $idSeccion = (int) ($_POST['id_seccion'] ?? 0);
controladores\calificaciones.controller.php:73:        $tema = trim((string) ($_POST['temaEvaluacion'] ?? ''));
controladores\calificaciones.controller.php:74:        $fecha = trim((string) ($_POST['fechaEvaluacion'] ?? ''));
controladores\calificaciones.controller.php:75:        $idPeriodo = (int) ($_POST['id_periodo'] ?? 0);
controladores\calificaciones.controller.php:76:        $idInstrumento = (int) ($_POST['id_instrumento'] ?? 0);
controladores\calificaciones.controller.php:118:        $idEvaluacion = (int) ($_POST['id_evaluacion'] ?? 0);
controladores\calificaciones.controller.php:119:        $tema = trim((string) ($_POST['temaEvaluacion'] ?? ''));
controladores\calificaciones.controller.php:151:        $idEvaluacion = (int) ($_POST['id_evaluacion'] ?? 0);
controladores\calificaciones.controller.php:175:        $idEvaluacion = (int) ($_POST['id_evaluacion'] ?? 0);
controladores\calificaciones.controller.php:187:        $notas = (array) ($_POST['calificaciones'] ?? []);
controladores\calificaciones.controller.php:188:        $devoluciones = (array) ($_POST['devoluciones'] ?? []);
controladores\calificaciones.controller.php:189:        $ausentes = array_fill_keys(array_map('intval', (array) ($_POST['ausentes'] ?? [])), true);
controladores\calificaciones.controller.php:240:        $idPeriodo=(int)($_POST['id_periodo']??0); $idSeccion=(int)($_POST['id_seccion']??0);
controladores\calificaciones.controller.php:241:        $estado=strtoupper(trim((string)($_POST['estado_periodo']??''))); $periodo=ModeloCalificaciones::mdlPeriodoPorId($idPeriodo,$idSeccion);
controladores\calificaciones.controller.php:256:        $motivo=trim((string)($_POST['motivoReapertura']??''));
controladores\calificaciones.controller.php:265:        $idPeriodo=(int)($_POST['id_periodo']??0); $idSeccion=(int)($_POST['id_seccion']??0);
controladores\calificaciones.controller.php:277:        $idPeriodo=(int)($_POST['id_periodo']??0); $idSeccion=(int)($_POST['id_seccion']??0); $periodo=ModeloCalificaciones::mdlPeriodoPorId($idPeriodo,$idSeccion);
controladores\calificaciones.controller.php:282:        foreach((array)($_POST['cierres']??[]) as $idEstudiante=>$valor){$normalizada=str_replace(',','.',trim((string)$valor));$idEstudiante=(int)$idEstudiante;if(!isset($permitidos[$idEstudiante])||!is_numeric($normalizada)||(float)$normalizada<0||(float)$normalizada>10){$_SESSION['error_message']='Revisá las notas de cierre. Deben estar entre 0 y 10.';return 'error';}$notas[$idEstudiante]=(float)$normalizada;}
controladores\calificaciones.controller.php:290:        $idSeccion = (int) ($_POST['id_seccion'] ?? 0);
controladores\calificaciones.controller.php:291:        $idLeccion = (int) ($_POST['id_modulo'] ?? 0);
controladores\calificaciones.controller.php:292:        $idCurso = (int) ($_POST['id_curso'] ?? 0);
controladores\calificaciones.controller.php:308:        $notas = (array) ($_POST['calificaciones'] ?? []);
controladores\calificaciones.controller.php:309:        $devoluciones = (array) ($_POST['devoluciones'] ?? []);
controladores\calificaciones.controller.php:372:        if (!isset($_POST['id_estudiante'], $_POST['id_seccion'], $_POST['id_modulo'], $_POST['id_curso'], $_POST['calificacion'])) {
controladores\calificaciones.controller.php:381:        $calificacion = (int) $_POST['calificacion'];
controladores\calificaciones.controller.php:388:            'id_estudiante' => (int) $_POST['id_estudiante'],
controladores\calificaciones.controller.php:389:            'id_seccion' => (int) $_POST['id_seccion'],
controladores\calificaciones.controller.php:390:            'id_modulo' => (int) $_POST['id_modulo'],
controladores\calificaciones.controller.php:391:            'id_curso' => (int) $_POST['id_curso'],
controladores\calificaciones.controller.php:393:            'devolucion' => trim((string) ($_POST['devolucion'] ?? '')),
controladores\perfiles.controller.php:9:        if (!isset($_POST["id_usuario"])) {
controladores\perfiles.controller.php:13:        $idUsuario = (int) $_POST["id_usuario"];
controladores\perfiles.controller.php:18:            && trim((string) ($_POST['passActual'] ?? '')) !== ''
controladores\perfiles.controller.php:19:            && trim((string) ($_POST['passNueva'] ?? '')) !== ''
controladores\perfiles.controller.php:20:            && trim((string) ($_POST['passNuevaConfirmar'] ?? '')) !== '';
controladores\perfiles.controller.php:24:            $passActual = (string) $_POST['passActual'];
controladores\perfiles.controller.php:25:            $passNueva = (string) $_POST['passNueva'];
controladores\perfiles.controller.php:26:            $passNuevaConfirmar = (string) $_POST['passNuevaConfirmar'];
controladores\perfiles.controller.php:51:            "dniPerfil" => $_POST["dniPerfil"] ?? null,
controladores\perfiles.controller.php:52:            "telefonoPerfil" => $_POST["telefonoPerfil"] ?? null,
controladores\perfiles.controller.php:53:            "fnacPerfil" => $_POST["fnacPerfil"] ?? null,
controladores\perfiles.controller.php:54:            "domicilioPerfil" => $_POST["domicilioPerfil"] ?? null,
controladores\perfiles.controller.php:55:            "provinciaPerfil" => $_POST["provinciaPerfil"] ?? null,
controladores\perfiles.controller.php:56:            "contenidoPerfil" => $_POST["contenidoPerfil"] ?? ''
controladores\perfiles.controller.php:85:            $respuestaClave = ModeloUsuarios::mdlActualizarPassword($idUsuario, password_hash((string) $_POST['passNueva'], PASSWORD_DEFAULT));
controladores\rutas.controller.php:113:            $idCurso = (int) ($_GET['idCurso'] ?? 0);
controladores\rutas.controller.php:116:                $eliminado = $resultado === 'ok' && ($_POST['accion_curso'] ?? '') === 'eliminar_curso';
controladores\rutas.controller.php:139:        if($ruta==='asistencia-seccion'){$idSeccion=(int)($_GET['idSeccion']??0);$resultado=ControladorAsistencias::crtProcesar();if($resultado!==null){$destino='index.php?r=asistencia-seccion&idSeccion='.$idSeccion;if((int)$resultado>0){$destino.='&idClase='.(int)$resultado;}header('Location: '.$destino);exit;}}
controladores\rutas.controller.php:142:            $idCurso = (int) ($_GET['idCurso'] ?? $_GET['id'] ?? 0);
controladores\rutas.controller.php:151:            $idSeccion = (int) ($_GET['idSeccion'] ?? $_GET['id'] ?? 0);
controladores\rutas.controller.php:155:                    $eliminada = $resultado === 'ok' && ($_POST['accion_materia'] ?? '') === 'eliminar_materia';
controladores\rutas.controller.php:180:        $estado = trim((string) ($_GET['estado'] ?? '1'));
controladores\rutas.controller.php:182:        $idEstudiante = max(0, (int) ($_GET['idEstudiante'] ?? 0));
controladores\rutas.controller.php:210:        $redirigir = self::redireccionSegura($_GET['redir'] ?? 'index.php');
controladores\rutas.controller.php:222:        $ruta = isset($_GET['r']) ? trim($_GET['r']) : '';
controladores\usuarios.controller.php:177:        $nombreUsuario = trim((string) ($_POST["nombreUsuario"] ?? ''));
controladores\usuarios.controller.php:178:        $apellidoUsuario = trim((string) ($_POST["apellidoUsuario"] ?? ''));
controladores\usuarios.controller.php:179:        $emailUsuario = trim((string) ($_POST["emailUsuario"] ?? ($_POST["email"] ?? '')));
controladores\usuarios.controller.php:180:        $passUsuario = (string) ($_POST["passUsuario"] ?? ($_POST["pass"] ?? ''));
controladores\usuarios.controller.php:181:        $rolUsuario = trim((string) ($_POST["rol"] ?? ''));
controladores\usuarios.controller.php:223:                "dniPerfil" => $_POST['dniPerfil'] ?? null,
controladores\usuarios.controller.php:224:                "telefonoPerfil" => $_POST['telefonoPerfil'] ?? null,
controladores\usuarios.controller.php:225:                "fnacPerfil" => $_POST['fnacPerfil'] ?? null,
controladores\usuarios.controller.php:226:                "domicilioPerfil" => $_POST['domicilioPerfil'] ?? null,
controladores\usuarios.controller.php:227:                "provinciaPerfil" => $_POST['provinciaPerfil'] ?? null,
controladores\usuarios.controller.php:228:                "contenidoPerfil" => $_POST['contenidoPerfil'] ?? '',
controladores\usuarios.controller.php:261:        if (!isset($_POST["nombreUsuario"], $_POST["apellidoUsuario"], $_POST["emailUsuario"], $_POST["rol"])) {
controladores\usuarios.controller.php:267:            "idUsuario" => (int) ($_POST["idUsuario"] ?? 0),
controladores\usuarios.controller.php:268:            "nombreUsuario" => trim((string) $_POST["nombreUsuario"]),
controladores\usuarios.controller.php:269:            "apellidoUsuario" => trim((string) $_POST["apellidoUsuario"]),
controladores\usuarios.controller.php:270:            "emailUsuario" => trim((string) $_POST["emailUsuario"]),
controladores\usuarios.controller.php:271:            "rol" => trim((string) $_POST["rol"]),
controladores\usuarios.controller.php:282:        if (!empty($_POST["passUsuario"])) {
controladores\usuarios.controller.php:283:            $datos["passUsuario"] = (string) $_POST["passUsuario"];
controladores\usuarios.controller.php:290:                "dniPerfil" => $_POST['dniPerfil'] ?? null,
controladores\usuarios.controller.php:291:                "telefonoPerfil" => $_POST['telefonoPerfil'] ?? null,
controladores\usuarios.controller.php:292:                "fnacPerfil" => $_POST['fnacPerfil'] ?? null,
controladores\usuarios.controller.php:293:                "domicilioPerfil" => $_POST['domicilioPerfil'] ?? null,
controladores\usuarios.controller.php:294:                "provinciaPerfil" => $_POST['provinciaPerfil'] ?? null,
controladores\usuarios.controller.php:295:                "contenidoPerfil" => $_POST['contenidoPerfil'] ?? '',
controladores\usuarios.controller.php:321:        if (!isset($_POST['accion_usuario']) || $_POST['accion_usuario'] !== 'baja_usuario') {
controladores\usuarios.controller.php:326:            'idUsuario' => (int) ($_POST['idUsuario'] ?? 0),
controladores\usuarios.controller.php:327:            'fechaBaja' => !empty($_POST['fechaBaja']) ? $_POST['fechaBaja'] . ' 00:00:00' : date('Y-m-d H:i:s'),
controladores\usuarios.controller.php:328:            'motivoBaja' => trim((string) ($_POST['motivoBaja'] ?? '')),
```
