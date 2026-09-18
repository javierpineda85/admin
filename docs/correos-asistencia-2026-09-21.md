# Correos y asistencia por curso

## Remitente y enlace público

En `config.local.php`, agregar o ajustar estas constantes (sin duplicar las existentes):

```php
define('MAIL_FROM_EMAIL', 'campus@tu-dominio.com');
define('MAIL_FROM_NAME', 'Nombre de tu institución');
define('APP_BASE_URL', 'https://tu-dominio.com/admin');
```

Usar la dirección autorizada por el servicio de correo del hosting y la URL real del campus, sin barra final. También se admiten variables de entorno con esos nombres. `config.local.php` tiene precedencia y no debe publicarse en Git. Cambiar estas constantes no configura el transporte SMTP: el proyecto sigue enviando con `mail()` de PHP y el transporte del servidor.

Las notificaciones de lecciones y actividades ahora usan HTML con estilos en línea, contenido escapado y botón **Ver aquí**. El enlace no se imprime como texto. El remitente y la URL de recuperación de contraseña también respetan la misma configuración.

## Asistencia por curso

Ejecutar `sql/2026-09-21_asistencias_curso.sql` en cada base del Campus antes de habilitar la funcionalidad. La migración es repetible, crea dos tablas InnoDB y no modifica datos anteriores. Sin migración, la pantalla informa que aún no está habilitada.

- Entrada: `index.php?r=asistencias`; planilla: `index.php?r=asistencia-curso&idCurso=ID`.
- Una sola planilla por curso y fecha. Pueden gestionarla administradores de la institución, el responsable del curso y docentes/tutores de sus materias.
- Todos los estudiantes elegibles comienzan presentes. Reabrir la fecha conserva el tema, los estados y las observaciones existentes.
- La elegibilidad considera la fecha de alta y baja. Las bajas posteriores conservan sus registros históricos.
- Se mantienen filtros por año, mes y texto, y totales por estudiante. El estudiante ve el resumen por curso.
- Los registros anteriores por materia están separados, disponibles para consulta y no se suman a los nuevos totales. No se infiere un estado diario a partir de materias con posibles diferencias.
- Los cambios de contexto institucional, los ID de otro curso y el token de formulario se validan antes de guardar.

La dirección definitiva del remitente, su nombre visible y la URL pública deben proporcionarse para reemplazar los valores locales. La validación del HTML no implica entrega verificada en un buzón real.

## Verificación local

- Migración aplicada en `classroom_mt_preview`.
- `tests/correos_publicaciones.php`: escape de HTML, destino del botón, URL no visible y remitente configurable.
- `tests/asistencias_curso.php`: base sintética, migración repetida, creación y reapertura sin duplicados, conservación de estados, bajas históricas, resumen por curso, roles, aislamiento y flujo HTTP de creación/guardado.
- La ejecución ampliada de `tests/multi_institucion_recursos.php` se detuvo en la comprobación HTTP del listado de integrantes de la institución. Ese resultado no permite declarar aprobada toda la suite de multiinstitución; las pruebas específicas anteriores sí finalizaron correctamente.
