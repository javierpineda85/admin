<?php
/** Ensayo CLI no destructivo: crea una base nueva y nunca modifica la de origen. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../config.php';

function comprobar($condicion, $mensaje)
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
    echo "OK: $mensaje\n";
}

function ejecutarMigracion(PDO $pdo, string $archivo): void
{
    $delimitador = ';';
    $buffer = '';
    foreach (file($archivo) as $linea) {
        if (preg_match('/^DELIMITER\s+(\S+)/i', trim($linea), $m)) {
            $delimitador = $m[1];
            continue;
        }
        if (trim($linea) === '' || str_starts_with(ltrim($linea), '--')) {
            continue;
        }
        $buffer .= $linea;
        if (str_ends_with(rtrim($buffer), $delimitador)) {
            $sql = substr(rtrim($buffer), 0, -strlen($delimitador));
            $stmt = $pdo->query($sql);
            if ($stmt) {
                while ($stmt->nextRowset()) {}
                $stmt->closeCursor();
            }
            $buffer = '';
        }
    }
    if (trim($buffer) !== '') {
        throw new RuntimeException('SQL incompleto al finalizar el archivo');
    }
}

function huella(PDO $pdo, string $tabla, array $columnas): string
{
    $seleccion = implode(',', array_map(static fn($c) => '`' . str_replace('`', '``', $c) . '`', $columnas));
    $filas = $pdo->query("SELECT $seleccion FROM `$tabla`")->fetchAll(PDO::FETCH_NUM);
    $serializadas = array_map('serialize', $filas);
    sort($serializadas, SORT_STRING);
    return hash('sha256', serialize($serializadas));
}

$nombreOrigen = (string) DB_NAME;
if (!preg_match('/^[a-zA-Z0-9_]+$/', $nombreOrigen)) {
    throw new RuntimeException('Nombre de base de origen no permitido para el ensayo');
}
$nombrePrueba = 'campus_mt_test_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3));
$dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4';
$pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec("CREATE DATABASE `$nombrePrueba` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `$nombrePrueba`");
echo "Base de ensayo: $nombrePrueba (se conserva para inspección local)\n";

$tablas = $pdo->query("SHOW FULL TABLES FROM `$nombreOrigen` WHERE Table_type='BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
$columnas = [];
$huellas = [];
foreach ($tablas as $tabla) {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $tabla)) {
        throw new RuntimeException('Tabla no permitida en el ensayo');
    }
    $pdo->exec("CREATE TABLE `$tabla` LIKE `$nombreOrigen`.`$tabla`");
    $pdo->exec("INSERT INTO `$tabla` SELECT * FROM `$nombreOrigen`.`$tabla`");
    $columnas[$tabla] = $pdo->query("SHOW COLUMNS FROM `$tabla`")->fetchAll(PDO::FETCH_COLUMN);
    $huellas[$tabla] = huella($pdo, $tabla, $columnas[$tabla]);
}
$migracion = __DIR__ . '/../sql/2026-09-14_multi_institucion_01_expandir.sql';
ejecutarMigracion($pdo, $migracion);
foreach ($huellas as $tabla => $esperada) {
    comprobar(huella($pdo, $tabla, $columnas[$tabla]) === $esperada, "Datos originales preservados: $tabla");
}
$idInicial = (int) $pdo->query("SELECT idInstitucion FROM instituciones WHERE slug='mentemotion'")->fetchColumn();
comprobar($idInicial > 0, 'MenteMotion creada');
comprobar((int) $pdo->query('SELECT COUNT(*) FROM usuarios_instituciones')->fetchColumn() === (int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn(), 'Todos los usuarios tienen membresía inicial');
comprobar((int) $pdo->query("SELECT COUNT(*) FROM cursos WHERE id_institucion IS NULL OR id_institucion<>$idInicial")->fetchColumn() === 0, 'Cursos existentes asociados a MenteMotion');
comprobar((int) $pdo->query('SELECT COUNT(*) FROM usuarios WHERE esSuperAdmin<>0')->fetchColumn() === 0, 'Ninguna promoción global implícita');
comprobar((int) $pdo->query("SELECT COUNT(*) FROM usuarios_instituciones_roles ur INNER JOIN usuarios_instituciones ui ON ui.idUsuarioInstitucion=ur.id_usuario_institucion INNER JOIN usuarios u ON u.idUsuario=ui.id_usuario WHERE u.rol='GESTOR'")->fetchColumn() === 0, 'GESTOR no recibe permisos por inferencia');

// La repetición debe conservar suspensiones y roles revocados.
$membresia = $pdo->query('SELECT id_usuario_institucion,id_rol FROM usuarios_instituciones_roles LIMIT 1')->fetch(PDO::FETCH_ASSOC);
if ($membresia) {
    $pdo->prepare('DELETE FROM usuarios_instituciones_roles WHERE id_usuario_institucion=? AND id_rol=?')->execute(array_values($membresia));
    $pdo->prepare('UPDATE usuarios_instituciones SET activo=0 WHERE idUsuarioInstitucion=?')->execute([$membresia['id_usuario_institucion']]);
}
$pdo->exec("UPDATE instituciones SET activo=0 WHERE idInstitucion=$idInicial");
$huellasNuevas = [];
foreach (['instituciones','usuarios_instituciones','roles','usuarios_instituciones_roles','campus_migraciones'] as $tabla) {
    $cols = $pdo->query("SHOW COLUMNS FROM `$tabla`")->fetchAll(PDO::FETCH_COLUMN);
    $huellasNuevas[$tabla] = [$cols,huella($pdo,$tabla,$cols)];
}
ejecutarMigracion($pdo, $migracion);
foreach ($huellasNuevas as $tabla => [$cols,$esperada]) {
    comprobar(huella($pdo,$tabla,$cols)===$esperada, "Reejecución sin alterar decisiones: $tabla");
}

// Fixtures de la matriz solicitada. Estas verificaciones son del esquema,
// no sustituyen las pruebas de autorización de modelos y navegación.
$pdo->exec("INSERT INTO instituciones(nombre,slug) VALUES ('Instituto Demo','instituto-demo')");
$idDemo = (int) $pdo->lastInsertId();
$idsUsuarios = [];
foreach (['A','B','C'] as $letra) {
    $pdo->prepare("INSERT INTO usuarios(nombreUsuario,apellidoUsuario,email,pass,resetPass,imgUsuario,activo,rol) VALUES (?, 'Prueba', ?, ?, 0, '', 1, 'ESTUDIANTE')")
        ->execute(['Usuario '.$letra, 'mt-'.strtolower($letra).'@example.invalid', password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT)]);
    $idsUsuarios[$letra] = (int) $pdo->lastInsertId();
}
$matriz = [
    ['A',$idInicial,'DOCENTE'],
    ['A',$idDemo,'ESTUDIANTE'],
    ['B',$idDemo,'ADMINISTRADOR'],
    ['B',$idDemo,'DOCENTE'],
    ['C',$idInicial,'ESTUDIANTE'],
];
foreach ($matriz as [$letra,$institucion,$rol]) {
    $pdo->prepare('INSERT INTO usuarios_instituciones(id_usuario,id_institucion) VALUES (?,?) ON DUPLICATE KEY UPDATE idUsuarioInstitucion=LAST_INSERT_ID(idUsuarioInstitucion)')
        ->execute([$idsUsuarios[$letra],$institucion]);
    $idMembresia = (int) $pdo->lastInsertId();
    $pdo->prepare('INSERT INTO usuarios_instituciones_roles(id_usuario_institucion,id_rol) SELECT ?,idRol FROM roles WHERE codigo=?')
        ->execute([$idMembresia,$rol]);
}
$consultaRoles = $pdo->prepare('SELECT r.codigo FROM usuarios_instituciones_roles ur INNER JOIN usuarios_instituciones ui ON ui.idUsuarioInstitucion=ur.id_usuario_institucion INNER JOIN roles r ON r.idRol=ur.id_rol WHERE ui.id_usuario=? AND ui.id_institucion=? ORDER BY r.codigo');
$consultaRoles->execute([$idsUsuarios['A'],$idInicial]);
comprobar($consultaRoles->fetchAll(PDO::FETCH_COLUMN)===['DOCENTE'], 'A es docente en MenteMotion');
$consultaRoles->execute([$idsUsuarios['A'],$idDemo]);
comprobar($consultaRoles->fetchAll(PDO::FETCH_COLUMN)===['ESTUDIANTE'], 'La misma cuenta A es estudiante en Demo');
$consultaRoles->execute([$idsUsuarios['B'],$idDemo]);
comprobar($consultaRoles->fetchAll(PDO::FETCH_COLUMN)===['ADMINISTRADOR','DOCENTE'], 'Una membresía admite múltiples roles');
$consultaRoles->execute([$idsUsuarios['C'],$idDemo]);
comprobar($consultaRoles->fetchAll(PDO::FETCH_COLUMN)===[], 'C no tiene membresía ni roles en Demo');
$consultaRoles->execute([$idsUsuarios['B'],$idInicial]);
comprobar($consultaRoles->fetchAll(PDO::FETCH_COLUMN)===[], 'B no recibe roles en MenteMotion');
comprobar((int)$pdo->query('SELECT COUNT(*) FROM usuarios WHERE esSuperAdmin<>0')->fetchColumn()===0, 'Administrador y docente no implican SuperAdmin');

$duplicadaRechazada = false;
try {
    $pdo->prepare('INSERT INTO usuarios_instituciones(id_usuario,id_institucion) VALUES (?,?)')->execute([$idsUsuarios['A'],$idDemo]);
} catch (PDOException $e) {
    $duplicadaRechazada = (int)($e->errorInfo[1]??0)===1062;
}
comprobar($duplicadaRechazada,'La clave única impide duplicar una membresía');

// Un email duplicado debe detenerse antes de agregar datos o promover usuarios.
$pdo->exec("INSERT INTO usuarios (nombreUsuario,apellidoUsuario,email,pass,resetPass,imgUsuario,activo,rol) VALUES ('Prueba','Duplicado','mt-duplicado@example.invalid','hash-no-utilizable',0,'',1,'ESTUDIANTE'),('Prueba','Duplicado','mt-duplicado@example.invalid','hash-no-utilizable',0,'',1,'ESTUDIANTE')");
$rechazo = false;
try {
    ejecutarMigracion($pdo,$migracion);
} catch (PDOException $e) {
    $rechazo = $e->getCode()==='45000';
}
comprobar($rechazo, 'Emails duplicados detienen la migración');

// El endurecimiento sólo se aplica cuando todas las filas ya tienen tenant.
// La base sintética contiene el corte completo y permite comprobar NOT NULL e identidad única.
// Se borran las filas de ensayo duplicadas después de comprobar el bloqueo de la fase 1.
$pdo->exec("DELETE FROM usuarios WHERE email='mt-duplicado@example.invalid'");
$idCursoEndurecer = (int) $pdo->query('SELECT idCurso FROM cursos ORDER BY idCurso LIMIT 1')->fetchColumn();
$pdo->prepare('UPDATE cursos SET id_institucion=NULL WHERE idCurso=?')->execute([$idCursoEndurecer]);
$rechazoEndurecer = false;
try {
    ejecutarMigracion($pdo, __DIR__ . '/../sql/2026-09-14_multi_institucion_04_endurecer.sql');
} catch (PDOException $e) {
    $rechazoEndurecer = $e->getCode()==='45000';
}
comprobar($rechazoEndurecer, 'El endurecimiento se detiene ante un curso sin institución');
$pdo->prepare('UPDATE cursos SET id_institucion=? WHERE idCurso=?')->execute([$idInicial, $idCursoEndurecer]);
$responsableOriginal = (int) $pdo->query('SELECT responsable FROM cursos WHERE idCurso=' . $idCursoEndurecer)->fetchColumn();
$pdo->prepare('UPDATE cursos SET responsable=? WHERE idCurso=?')->execute([$idsUsuarios['B'], $idCursoEndurecer]);
$rechazoRelacion = false;
try {
    ejecutarMigracion($pdo, __DIR__ . '/../sql/2026-09-14_multi_institucion_04_endurecer.sql');
} catch (PDOException $e) {
    $rechazoRelacion = $e->getCode()==='45000';
}
comprobar($rechazoRelacion, 'El endurecimiento se detiene ante una relación sin membresía institucional');
$pdo->prepare('UPDATE cursos SET responsable=? WHERE idCurso=?')->execute([$responsableOriginal, $idCursoEndurecer]);
$idLeccionEndurecer = (int) $pdo->query('SELECT idLeccion FROM lecciones ORDER BY idLeccion LIMIT 1')->fetchColumn();
$pdo->prepare('INSERT INTO posteos (id_autor,contenidoPosteo,fechaPosteo,id_curso,id_leccion) VALUES (?,?,?,?,NULL)')
    ->execute([$idsUsuarios['B'], 'Preflight sintético', '2026-09-14 10:00:00', $idCursoEndurecer]);
$idPosteoEndurecer = (int) $pdo->lastInsertId();
$pdo->prepare('INSERT INTO recursoslecciones (id_leccion,tipoRecurso,tituloRecurso,urlRecurso,creadoPor) VALUES (?,?,?,?,?)')
    ->execute([$idLeccionEndurecer, 'ENLACE', 'Preflight sintético', 'https://example.invalid/preflight', $idsUsuarios['B']]);
$idRecursoEndurecer = (int) $pdo->lastInsertId();
$rechazoRelacionesSecundarias = false;
try {
    ejecutarMigracion($pdo, __DIR__ . '/../sql/2026-09-14_multi_institucion_04_endurecer.sql');
} catch (PDOException $e) {
    $rechazoRelacionesSecundarias = $e->getCode()==='45000';
}
comprobar($rechazoRelacionesSecundarias, 'El endurecimiento detiene autores de posteos y recursos sin membresía');
$diagnosticoSecundario = $pdo->query(file_get_contents(__DIR__ . '/../sql/2026-09-14_multi_institucion_05_validar_relaciones.sql'))->fetchAll(PDO::FETCH_KEY_PAIR);
comprobar((int) ($diagnosticoSecundario['posteos_autor_sin_membresia'] ?? 0) > 0
    && (int) ($diagnosticoSecundario['recursos_creador_sin_membresia'] ?? 0) > 0,
    'El diagnóstico identifica autores y creadores sin membresía');
$pdo->prepare('DELETE FROM posteos WHERE idPosteo=?')->execute([$idPosteoEndurecer]);
$pdo->prepare('DELETE FROM recursoslecciones WHERE idRecursoLeccion=?')->execute([$idRecursoEndurecer]);
ejecutarMigracion($pdo, __DIR__ . '/../sql/2026-09-14_multi_institucion_04_endurecer.sql');
comprobar((string)$pdo->query("SELECT IS_NULLABLE FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='cursos' AND column_name='id_institucion'")->fetchColumn()==='NO', 'Cursos pasan a exigir institución después del corte');
comprobar((string)$pdo->query("SELECT IS_NULLABLE FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='mensajes' AND column_name='id_institucion'")->fetchColumn()==='NO', 'Mensajes pasan a exigir institución después del corte');
comprobar((int)$pdo->query("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='usuarios' AND index_name='uq_usuarios_email_global'")->fetchColumn()===1, 'El email global queda protegido por unicidad estructural');
ejecutarMigracion($pdo, __DIR__ . '/../sql/2026-09-14_multi_institucion_04_endurecer.sql');
comprobar((int)$pdo->query("SELECT COUNT(*) FROM campus_migraciones WHERE codigo='multi_institucion_04_endurecer'")->fetchColumn()===1, 'El endurecimiento es reanudable e idempotente');
$diagnosticoRelaciones = $pdo->query(file_get_contents(__DIR__ . '/../sql/2026-09-14_multi_institucion_05_validar_relaciones.sql'))->fetchAll(PDO::FETCH_ASSOC);
comprobar($diagnosticoRelaciones && max(array_map(static fn($fila) => (int) $fila['incidencias'], $diagnosticoRelaciones))===0, 'El diagnóstico de relaciones no detecta cruces de membresía');
echo "Ensayo de expansión finalizado; no verifica aún aislamiento web ni roles de sesión.\n";
