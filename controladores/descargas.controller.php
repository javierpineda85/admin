<?php
require_once __DIR__ . '/../modelos/mensajes.modelo.php';
require_once __DIR__ . '/../modelos/lecciones.modelo.php';
require_once __DIR__ . '/../modelos/materias.modelo.php';

class ControladorDescargas
{
    public static function crtResolverArchivo($tipo, $id)
    {
        if (($_SESSION['logueado'] ?? false) !== true) {
            throw new RuntimeException('Acceso institucional denegado.');
        }
        $tipo = strtolower(trim((string) $tipo));
        $id = (int) $id;
        $idUsuario = (int) ($_SESSION['usuario']['id'] ?? 0);
        if ($id <= 0 || $idUsuario <= 0) {
            throw new RuntimeException('Acceso institucional denegado.');
        }

        if ($tipo === 'mensaje') {
            $registro = ModeloMensajes::mdlAdjuntoPorId($id, $idUsuario);
            if (!$registro) { throw new RuntimeException('Acceso institucional denegado.'); }
            return self::archivoPermitido($registro, 'mensajes');
        }

        if ($tipo === 'entrega') {
            $registro = ModeloLecciones::mdlBuscarAdjuntoEntregaPorId($id);
            if (!$registro) { throw new RuntimeException('Acceso institucional denegado.'); }
            $entrega = ModeloLecciones::mdlBuscarEntregaPorId((int) $registro['id_entrega']);
            self::exigirAccesoEntrega($entrega, $idUsuario);
            return self::archivoPermitido($registro, 'lecciones');
        }

        if ($tipo === 'entrega-legacy') {
            $entrega = ModeloLecciones::mdlBuscarEntregaPorId($id);
            self::exigirAccesoEntrega($entrega, $idUsuario);
            return self::archivoPermitido([
                'rutaArchivo' => $entrega['urlArchivo'] ?? '',
                'nombreOriginal' => 'Archivo entregado',
                'mimeType' => null,
                'tamanoArchivo' => null,
            ], 'lecciones');
        }

        if ($tipo === 'recurso') {
            $recurso = ModeloLecciones::mdlBuscarRecursoPorId($id);
            if (!$recurso) { throw new RuntimeException('Acceso institucional denegado.'); }
            $leccion = ModeloLecciones::mdlBuscarLeccionPorId((int) $recurso['id_leccion']);
            $idSeccion = (int) ($leccion['id_modulo'] ?? 0);
            $materia = ModeloMaterias::mdlBuscarMateriaPorId($idSeccion);
            $idCurso = (int) ($materia['id_curso'] ?? 0);
            $autorizado = ControladorPermisos::esAdministrador()
                || (ControladorPermisos::esDocente()
                    && ControladorLecciones::crtSeccionAsignadaDocente($idSeccion, $idUsuario))
                || (ControladorPermisos::esEstudiante()
                    && ModeloLecciones::mdlEstudianteEnCurso(ControladorPermisos::idEstudianteContexto(), $idCurso));
            if (!$autorizado) {
                throw new RuntimeException('Acceso institucional denegado.');
            }
            return self::archivoPermitido([
                'rutaArchivo' => $recurso['urlRecurso'] ?? '',
                'nombreOriginal' => $recurso['tituloRecurso'] ?? '',
                'mimeType' => null,
                'tamanoArchivo' => null,
            ], 'lecciones');
        }

        throw new RuntimeException('Acceso institucional denegado.');
    }

    private static function exigirAccesoEntrega($entrega, $idUsuario)
    {
        if (!$entrega) { throw new RuntimeException('Acceso institucional denegado.'); }
        if (ControladorPermisos::esAdministrador()) { return; }
        if (ControladorPermisos::esDocente()
            && ControladorLecciones::crtSeccionAsignadaDocente((int) $entrega['id_seccion'], $idUsuario)) { return; }
        if (ControladorPermisos::esEstudiante()
            && (int) $entrega['id_estudiante'] === ControladorPermisos::idEstudianteContexto()) { return; }
        throw new RuntimeException('Acceso institucional denegado.');
    }

    private static function archivoPermitido(array $registro, $carpeta)
    {
        $raiz = realpath(__DIR__ . '/../uploads/' . $carpeta);
        $ruta = realpath(__DIR__ . '/../' . ltrim(str_replace('\\', '/', (string) ($registro['rutaArchivo'] ?? '')), '/'));
        if (!$raiz || !$ruta || !is_file($ruta) || strpos($ruta, $raiz . DIRECTORY_SEPARATOR) !== 0) {
            throw new RuntimeException('Archivo no disponible.');
        }
        $nombre = trim((string) ($registro['nombreOriginal'] ?? ''));
        if ($nombre === '') { $nombre = basename($ruta); }
        return [
            'ruta' => $ruta,
            'nombre' => basename(str_replace(["\r", "\n"], '', $nombre)),
            'mime' => (string) ($registro['mimeType'] ?? ''),
            'tamano' => filesize($ruta),
        ];
    }

    public static function crtProcesar()
    {
        if (($_GET['r'] ?? '') !== 'descargar-archivo') { return; }
        try {
            $archivo = self::crtResolverArchivo($_GET['tipo'] ?? '', $_GET['id'] ?? 0);
            $mime = $archivo['mime'] !== '' ? $archivo['mime'] : 'application/octet-stream';
            header('Cache-Control: no-store, private');
            header('Pragma: no-cache');
            header('Content-Type: '.$mime);
            header('Content-Length: '.(int)$archivo['tamano']);
            header("Content-Disposition: attachment; filename*=UTF-8''".rawurlencode($archivo['nombre']));
            header('X-Content-Type-Options: nosniff');
            readfile($archivo['ruta']);
            exit;
        } catch (Throwable $e) {
            http_response_code(strpos($e->getMessage(), 'disponible') !== false ? 404 : 403);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'No se pudo acceder al archivo solicitado.';
            exit;
        }
    }
}
