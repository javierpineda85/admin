# Actividades version 5 - release 5.16.0626

Esta documentacion acompana el cierre funcional de las etapas 1 y 2 del modulo de actividades.

## Alcance inicial

La primera fase integra el modulo base de actividades con visibilidad publica o privada.

Tipos disponibles:

- Multiple choice.
- Verdadero / falso.
- Completar espacios.
- Recurso externo embebible, por ejemplo Wordwall.

Configuracion evaluativa:

- Intentos permitidos por estudiante. Usar `1` para examenes de intento unico.
- Explicacion opcional por pregunta para mostrar feedback cuando la respuesta es incorrecta.

Visibilidad:

- `privada`: solo usuarios del curso asociado.
- `publica`: aparece en el listado publico.
- `oculta`: no aparece listada, pero funciona con enlace directo.

## Rutas internas

- `index.php?r=listado-actividades`
- `index.php?r=crear-actividad`
- `index.php?r=editar-actividad&idActividad=ID`
- `index.php?r=ver-actividad&idActividad=ID`
- `index.php?r=resultados-actividad&idActividad=ID`

## Ruta publica

- Listado publico: `index.php?r=actividad-publica`
- Actividad directa: `index.php?r=actividad-publica&slug=SLUG`

El slug se genera automaticamente desde el titulo y no se expone como campo editable para docentes.

## Integracion con WordPress

Para el home de `mentemotion.com`, la integracion recomendada en esta etapa es enlazar o embeber las actividades publicas del campus.

Opciones:

- Agregar botones en WordPress hacia `https://mentemotion.com/admin/index.php?r=actividad-publica`.
- Enlazar actividades destacadas con `https://mentemotion.com/admin/index.php?r=actividad-publica&slug=SLUG`.
- Crear una pagina de WordPress "Actividades" e insertar un bloque HTML con un iframe al listado publico.

Mas adelante se puede agregar una regla de reescritura para URLs limpias como `/actividad/SLUG`, pero no es necesario para validar la funcionalidad.

## Fase 3A - Banco de actividades

Se agrega una capa inicial de reutilizacion para docentes y administradores:

- `index.php?r=banco-actividades`: banco de plantillas.
- Accion `Guardar como plantilla` desde el listado de actividades.
- Accion `Duplicar` para crear una copia editable.
- Accion `Usar plantilla` para convertir una plantilla en una nueva actividad de trabajo.

Cada plantilla se guarda en la misma tabla `actividades`, diferenciada por `esPlantilla = 1`.

## Fase 3B - Banco operativo

Se agregan mejoras de gestion para que el banco funcione como herramienta diaria:

- Plantillas `personales` o `institucionales`.
- Filtros por tipo, estado, visibilidad y alcance.
- Actividades publicas marcables como `destacadas`.
- Acciones rapidas desde menu contextual:
  - copiar enlace publico
  - copiar embed
  - alternar destacado
  - cambiar alcance de plantilla
  - mover plantilla al listado de trabajo
