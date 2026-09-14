<?php
/** Ensayo del SQL autocontenido. Crea una base separada y no modifica classroom. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/soporte/sql.php';

function comprobarInstalador($condicion, string $mensaje): void
{
    if (!$condicion) { throw new RuntimeException($mensaje); }
    echo "OK: $mensaje\n";
}

function huellaInstalador(PDO $pdo, string $tabla, array $columnas): string
{
    $lista = implode(',', array_map(static fn($columna) => '`' . str_replace('`', '``', $columna) . '`', $columnas));
    $filas = $pdo->query("SELECT $lista FROM `$tabla`")->fetchAll(PDO::FETCH_NUM);
    usort($filas, static fn($a, $b) => strcmp(serialize($a), serialize($b)));
    return hash('sha256', serialize($filas));
}

$origen = DB_NAME;
if (!preg_match('/^[a-zA-Z0-9_]+$/D', $origen)) { throw new RuntimeException('Base de origen inválida'); }
$base = 'campus_mt_instalador_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3));
$pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4', DB_USER, DB_PASSWORD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec("CREATE DATABASE `$base` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `$base`");

$tablas = $pdo->query("SHOW FULL TABLES FROM `$origen` WHERE Table_type='BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
$columnas = [];
$huellas = [];
foreach ($tablas as $tabla) {
    if (!preg_match('/^[a-zA-Z0-9_]+$/D', $tabla)) { throw new RuntimeException('Tabla de origen inválida'); }
    $pdo->exec("CREATE TABLE `$tabla` LIKE `$origen`.`$tabla`");
    $pdo->exec("INSERT INTO `$tabla` SELECT * FROM `$origen`.`$tabla`");
    $columnas[$tabla] = $pdo->query("SHOW COLUMNS FROM `$tabla`")->fetchAll(PDO::FETCH_COLUMN);
    $huellas[$tabla] = huellaInstalador($pdo, $tabla, $columnas[$tabla]);
}

$instalador = __DIR__ . '/../sql/2026-09-14_multi_institucion_instalacion_completa.sql';
ejecutarMigracion($pdo, $instalador);
foreach ($huellas as $tabla => $esperada) {
    comprobarInstalador(huellaInstalador($pdo, $tabla, $columnas[$tabla]) === $esperada, "Datos originales preservados: $tabla");
}
comprobarInstalador((int)$pdo->query("SELECT COUNT(*) FROM campus_migraciones WHERE codigo IN ('multi_institucion_01_expandir','multi_institucion_02_asistencias','multi_institucion_03_catalogos_calificacion')")->fetchColumn() === 3,
    'El instalador aplica expansión, asistencia y catálogos');
comprobarInstalador((int)$pdo->query("SELECT COUNT(*) FROM campus_migraciones WHERE codigo='multi_institucion_04_endurecer'")->fetchColumn() === 0,
    'La primera ejecución no aplica el endurecimiento opcional');
comprobarInstalador((int)$pdo->query("SELECT COUNT(*) FROM cursos WHERE id_institucion IS NULL")->fetchColumn() === 0,
    'Todos los cursos existentes quedan asociados a MenteMotion');
comprobarInstalador((int)$pdo->query('SELECT COUNT(*) FROM usuarios_instituciones')->fetchColumn() === (int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn(),
    'Todos los usuarios existentes reciben membresía inicial');

$estadoAntes = $pdo->query('SELECT COUNT(*) instituciones, (SELECT COUNT(*) FROM usuarios_instituciones) membresias FROM instituciones')->fetch(PDO::FETCH_ASSOC);
ejecutarMigracion($pdo, $instalador);
$estadoDespues = $pdo->query('SELECT COUNT(*) instituciones, (SELECT COUNT(*) FROM usuarios_instituciones) membresias FROM instituciones')->fetch(PDO::FETCH_ASSOC);
comprobarInstalador($estadoAntes === $estadoDespues, 'La reimportación no duplica instituciones ni membresías');

$idCurso = (int)$pdo->query('SELECT idCurso FROM cursos ORDER BY idCurso LIMIT 1')->fetchColumn();
if ($idCurso > 0) {
    $responsable = $pdo->query('SELECT responsable FROM cursos WHERE idCurso=' . $idCurso)->fetchColumn();
    $inexistente = (int)$pdo->query('SELECT COALESCE(MAX(idUsuario),0)+1000 FROM usuarios')->fetchColumn();
    $pdo->prepare('UPDATE cursos SET responsable=? WHERE idCurso=?')->execute([$inexistente, $idCurso]);
    $sqlEndurecer = preg_replace('/SET @CAMPUS_MT_APLICAR_ENDURECIMIENTO = 0;/', 'SET @CAMPUS_MT_APLICAR_ENDURECIMIENTO = 1;', file_get_contents($instalador), 1);
    $temporal = tempnam(sys_get_temp_dir(), 'campus_mt_');
    file_put_contents($temporal, $sqlEndurecer);
    $rechazado = false;
    try { ejecutarMigracion($pdo, $temporal); } catch (PDOException $e) { $rechazado = $e->getCode() === '45000'; }
    @unlink($temporal);
    comprobarInstalador($rechazado, 'El modo definitivo se detiene ante relaciones institucionales inválidas');
    $pdo->prepare('UPDATE cursos SET responsable=? WHERE idCurso=?')->execute([$responsable, $idCurso]);
}

echo "Base de ensayo conservada: $base\n";
