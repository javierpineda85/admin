# Arquitectura

## Evolución multiinstitución en preparación

El diseño y el estado de las fases están en [multi-institucion.md](multi-institucion.md).
La fase inicial agrega esquema compatible. Las fases 2 y 3 incorporan autenticación,
contexto y roles institucionales en ensayo; el aislamiento académico no está implementado.
El relevamiento de roles, SQL y entradas de controladores está en
[multi-institucion-inventario.md](multi-institucion-inventario.md).

## Resumen

El proyecto usa una estructura PHP clasica con separacion por capas:

- `index.php` funciona como punto de entrada.
- `controladores/` recibe la accion, valida permisos y coordina el flujo.
- `modelos/` resuelve las consultas SQL y el acceso a datos.
- `vistas/` renderiza la interfaz por modulo.
- `css/` contiene la identidad visual del classroom.

## Flujo de peticiones

1. El usuario entra por `index.php`.
2. `config.php` inicia la sesion y define configuracion base.
3. `RutasController` resuelve la vista solicitada mediante `?r=...`.
4. `ControladorPermisos` valida si el rol puede acceder a esa ruta.
5. La vista llama al controlador correspondiente.
6. El controlador usa el modelo para leer o escribir datos.
7. La respuesta vuelve a la vista con mensajes de exito o error.

## Archivos clave

- `controladores/rutas.controller.php`: mapa central de rutas.
- `controladores/permisos.controller.php`: reglas por rol.
- `controladores/autoload.php`: carga de clases.
- `modelos/conexion.php`: conexion PDO.
- `vistas/plantilla.php`: layout general.

## Sesion y autenticacion

- La sesion se crea al iniciar la aplicacion.
- `login`, `forgot` y `logout` son rutas publicas.
- El resto de las rutas requieren una sesion activa.
- El logout destruye la sesion y redirige al login.

## Convenciones generales

- Los nombres de ruta usan formato `kebab-case`.
- Las vistas viven bajo `vistas/paginas/<modulo>/`.
- Las acciones del POST se identifican con `accion`.
- Los mensajes visuales se muestran con Toastify.
- Las cargas de archivos se guardan en `uploads/`.

## Puntos de extension

- Si se agrega un modulo nuevo, hay que registrar:
  - la ruta en `RutasController`,
  - el permiso en `ControladorPermisos`,
  - el controlador en `index.php` o en el autoload,
  - la vista en `vistas/paginas/`.

## Contexto central implementado en fase 2

`ControladorInstitucion` y `ModeloInstituciones` validan contexto antes del HTML,
desde `index.php`. Con `INSTITUCIONES_CONTEXTO_ACTIVO=1` habilitan login, selección
y Campus sobre una base migrada de ensayo. Por defecto el indicador está desactivado
y permanece el flujo habitual. Véase multi-institucion.md.

La fase 3 concentra capacidades en `ControladorPermisos`: toma todos los roles del
contexto, autoriza por su unión y conserva un rol principal estable para las vistas
legacy. Las personas asignables se validan contra membresía y rol activos mediante
`ModeloInstituciones`.

La fase 4 en curso agrega `ModeloTenant` para construir predicados SQL comunes y
validar cadenas de pertenencia. `RutasController` rechaza antes de renderizar IDs
ajenos de cursos, materias, actividades, mensajes y perfiles. Las vistas usan los
modelos con contexto. Alcance y límites en [multi-institucion-fase4.md](multi-institucion-fase4.md).
