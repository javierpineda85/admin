# Cambios de Junio 2026

## Resumen de la entrega

Esta fase consolida el rediseño visual del panel, mejora el detalle de materias y amplía la mensajería y las notificaciones para que el uso diario sea más cercano a un classroom real.

## Cambios funcionales

- Rediseño visual global del panel con nueva identidad cromática, tarjetas, tablas, formularios y sidebar unificados.
- Detalle de sección más sólido para estudiantes, docentes y administradores.
- Lecciones con soporte para borradores.
- Carga de varios archivos y varios enlaces en una misma lección o recurso, sin tener que guardar entre cada adjunto.
- Tabs en materias para `Tablón`, `Trabajo de clase`, `Personas` y `Calificaciones`.
- Vista de `Personas` con acceso rápido para enviar mensajes.
- Seguimiento docente con acceso directo a la vista completa de calificaciones.
- Mensajería con adjuntos múltiples, papelera, múltiples destinatarios y envío por sección.
- Regla nueva para estudiantes: pueden escribir a compañeros del mismo curso y también a sus docentes o tutores.
- Campanita del header con notificaciones reales y marcado como leídas.
- Orden de lecciones de la más nueva a la más antigua.
- Paginación para la vista de gestión de lecciones, pensada para materias con cursadas largas.

## Cambios técnicos

- Refactor inicial de `detalle-seccion.php` para empezar a dividirlo en parciales y facilitar mantenimiento.
- Nuevos parciales:
  - `vistas/paginas/materias/detalle-seccion/_personas.php`
  - `vistas/paginas/materias/detalle-seccion/_calificaciones-tab.php`
  - `vistas/paginas/materias/detalle-seccion/_sidebar.php`
- Tabla nueva `notificaciones_lecturas` en `classroom.sql` para persistir el estado de lectura de la campanita.
- Mejoras en consultas de usuarios permitidos para mensajes y datos de tutor dentro de secciones.

## Ajustes visuales destacados

- Dropdown de notificaciones más ancho y legible.
- Badges de tipo de lección con color propio:
  - `Material`: violeta suave.
  - `Tarea`: naranja suave.
  - `Pregunta`: azul suave.
- Footer actualizado con nueva firma visual:
  - `Version 4.13.0626`
  - `Copyright 2026. Desarrollado por The Big Table, potenciado con IA`

## Observaciones

- La documentación ahora refleja el estado actual del sistema, incluyendo tabs académicas, reglas de mensajes y lectura de notificaciones.
- El refactor de `detalle-seccion.php` ya empezó, pero todavía queda margen para seguir separando bloques en próximas fases.
