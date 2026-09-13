<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

function ejecutarMigracion(PDO $pdo, string $archivo): void
{
    $delimitador = ';';
    $buffer = '';
    foreach (file($archivo) as $linea) {
        if (preg_match('/^DELIMITER\s+(\S+)/i', trim($linea), $m)) {
            $delimitador = $m[1];
            continue;
        }
        if (trim($linea) === '' || str_starts_with(ltrim($linea), '--')) { continue; }
        $buffer .= $linea;
        if (str_ends_with(rtrim($buffer), $delimitador)) {
            $stmt = $pdo->query(substr(rtrim($buffer), 0, -strlen($delimitador)));
            if ($stmt) {
                while ($stmt->nextRowset()) {}
                $stmt->closeCursor();
            }
            $buffer = '';
        }
    }
    if (trim($buffer) !== '') { throw new RuntimeException('SQL incompleto al finalizar el archivo'); }
}
