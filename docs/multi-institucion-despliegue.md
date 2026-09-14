# Despliegue multiinstitución

Esta guía describe el paso desde una instalación legacy hacia el modo
multiinstitución. Las migraciones no deben ejecutarse sobre producción sin backup
verificado y una ventana de mantenimiento: el DDL de MySQL produce commits
implícitos y las tablas académicas históricas incluyen motores MyISAM.

## Preparación

1. Confirmar que la rama desplegada contiene el esquema y el código compatibles.
2. Tomar un backup completo de la base y de `uploads/`.
3. Probar la restauración del backup en una base separada.
4. Ejecutar el diagnóstico y conservar su salida para el informe de regularización:

   ```bash
   mysql campus -e "source sql/2026-09-14_multi_institucion_00_diagnostico.sql"
   ```

5. Resolver emails vacíos o duplicados. La expansión se detiene si encuentra
   identidades globales ambiguas.

## Orden de aplicación

Ejecutar las migraciones históricas que la instalación todavía necesite y luego:

1. `2026-09-14_multi_institucion_01_expandir.sql`
2. `2026-09-14_multi_institucion_02_asistencias.sql`
3. `2026-09-14_multi_institucion_03_catalogos_calificacion.sql`

La expansión crea MenteMotion, asocia usuarios y cursos existentes, conserva
`usuarios.rol` como legacy y no asigna SuperAdmin a ninguna cuenta. Es reanudable;
no debe repetirse una migración histórica que contenga `DROP TABLE`.

## Validación antes de activar

Comprobar en la base migrada:

- Todos los cursos tienen `id_institucion` y apuntan a MenteMotion.
- Cada email identifica una sola identidad global.
- Las membresías y roles iniciales coinciden con `usuarios.rol` sólo para
  ADMINISTRADOR, DOCENTE y ESTUDIANTE.
- GESTOR y cualquier código no soportado no recibieron permisos por inferencia.
- No existen relaciones académicas incoherentes sin informe de regularización.

Ejecutar además el diagnóstico de membresías relacionadas. Cada incidencia debe
ser cero antes de continuar:

```bash
mysql campus -e "source sql/2026-09-14_multi_institucion_05_validar_relaciones.sql"
```

El diagnóstico revisa responsables, docentes, tutores, inscripciones,
mensajería, autores, entregas, calificaciones y asistencia contra la membresía
de la institución del recurso. El endurecimiento vuelve a verificar estas
relaciones y aborta si aparece alguna durante el corte.

Ejecutar la batería sintética antes de cambiar configuración:

```bash
php tests/multi_institucion_migracion.php
php tests/multi_institucion_contexto.php
php tests/multi_institucion_recursos.php
php tests/multi_institucion_superadmin.php
CAMPUS_TEST_BASE=<base_sintetica> php tests/multi_institucion_legacy.php
```

## Activación gradual

Mantener `INSTITUCIONES_CONTEXTO_ACTIVO=0` mientras se realizan las validaciones.
Cuando la base migrada y el código estén listos, activar el indicador en el
entorno de ensayo y repetir el smoke test con una cuenta de cada caso:

- una sola membresía;
- dos instituciones con roles diferentes;
- administrador institucional;
- cuenta sin membresías;
- SuperAdmin sin membresía académica.

Verificar login LOCAL, WORDPRESS e HYBRID, selección de institución, cambio desde
el header, cursos, materias, mensajes, archivos, asistencia y calificaciones.

## Endurecimiento posterior al corte

Ejecutar `2026-09-14_multi_institucion_04_endurecer.sql` sólo después de validar
el delta que pudo crear el modo legacy y obtener cero incidencias en el diagnóstico
de relaciones. El script aborta antes de alterar columnas si encuentra filas sin
tenant, emails duplicados o relaciones sin membresía institucional. Si termina correctamente:

- los contextos institucionales explícitos pasan a `NOT NULL`;
- `usuarios.email` queda protegido por unicidad estructural;
- el marcador `multi_institucion_04_endurecer` permite reanudar sin duplicar cambios.

No ejecutar este paso mientras existan procesos legacy que todavía creen cursos,
mensajes o actividades sin institución.

## SuperAdmin y WordPress

Asignar `usuarios.esSuperAdmin=1` sólo mediante un procedimiento administrativo
controlado y registrado. El rol WordPress `administrator` no concede ese atributo
ni crea membresías. La autenticación WordPress identifica la cuenta; Campus sigue
administrando membresías y roles académicos.

## Rollback y observabilidad

Las migraciones DDL no ofrecen rollback transaccional integral. Ante un error:

1. detener la activación del indicador;
2. conservar los logs y la salida del diagnóstico;
3. restaurar backup si la estructura o los datos quedaron incompletos;
4. corregir el informe de regularización y repetir sobre una copia;
5. no borrar manualmente membresías, roles ni archivos para “deshacer” la prueba.

Después de activar, monitorear errores de contexto, respuestas 403 inesperadas,
fallos de autenticación WordPress y rutas de descarga. Las cuentas sintéticas y
las bases de ensayo no son datos de producción.
