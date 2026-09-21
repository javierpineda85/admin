# Integracion WordPress

## Objetivo

Permitir que `campus.mentemotion.com` comparta acceso con los usuarios registrados en WordPress y TutorLMS, sin romper el login local de desarrollo.

## Modos disponibles

- `LOCAL`: usa la tabla `usuarios` de Campus.
- `WORDPRESS`: valida credenciales contra `wp_users` y `wp_usermeta`.
- `HYBRID`: intenta primero login local y luego WordPress.

## Configuracion

Las credenciales ya no deben quedar fijas dentro de `modelos/conexion.php`. Ahora se leen desde `config.php` o variables de entorno:

- `AUTH_MODE`
- `DB_HOST`
- `DB_PORT`
- `DB_NAME`
- `DB_USER`
- `DB_PASSWORD`
- `WP_DB_HOST`
- `WP_DB_PORT`
- `WP_DB_NAME`
- `WP_DB_USER`
- `WP_DB_PASSWORD`
- `WP_TABLE_PREFIX`
- `WP_ROOT_PATH`
- `WP_SUPER_ADMIN_EMAILS`
- `AUTH_DEBUG`

## Roles mapeados

- Si el mail pertenece a `WP_SUPER_ADMIN_EMAILS`, el usuario entra como `ADMINISTRADOR`.
- Si tiene rol `administrator` en WordPress, entra como `ADMINISTRADOR`.
- Si tiene marca de instructor aprobada en TutorLMS, entra como `DOCENTE`.
- Si tiene marca de estudiante en TutorLMS, entra como `ESTUDIANTE`.

## Sincronizacion

Cuando un usuario inicia sesion correctamente desde WordPress:

1. Campus busca si ya existe por `wpUserId` o por email.
2. Si existe, actualiza nombre, apellido, email, rol, estado activo y origen.
3. Si no existe, crea el usuario local automaticamente.
4. Si el perfil no existe, genera un perfil basico para no romper las vistas internas.

## Registro directo desde Campus

Cuando el modo es `LOCAL` o `HYBRID` y el contexto institucional está activo, la
pantalla de login ofrece **Registrarme** junto a **Olvidé mi contraseña**. El alta
crea una identidad local activa con contraseña cifrada, pero no asigna rol ni
membresía institucional. Después del registro, el usuario inicia sesión y Campus
le informa que la administración de su institución debe habilitarle el acceso.

En modo `WORDPRESS`, el registro directo permanece deshabilitado porque la fuente
de identidad es WordPress.

## Base de datos

La tabla `usuarios` incorpora:

- `wpUserId`
- `origenAuth`

Si la base ya estaba creada, Campus intenta agregar esas columnas automaticamente la primera vez que autentica contra WordPress.

## Nota sobre hashes antiguos

Si WordPress tiene contraseñas antiguas con hash tipo `phpass`, es importante definir `WP_ROOT_PATH` apuntando a la instalacion real de WordPress para poder cargar `wp-includes/class-phpass.php`.

## Ensayo multiinstitución (fase 2)

Con `INSTITUCIONES_CONTEXTO_ACTIVO=1`, WordPress sincroniza identidad sin conceder
roles, membresías ni SuperAdmin. Una cuenta nueva queda sin acceso institucional.
Se rechazan cuentas inactivas e identidades ambiguas. La conducta anterior de este
documento corresponde al modo legacy, desactivado el nuevo contexto por defecto.
Consultar multi-institucion.md para los límites y las pruebas locales del ensayo.
