# Cierre de integracion WordPress

## Estado validado

- `profejavierpineda@gmail.com` ingresa al Campus usando credenciales de WordPress.
- El usuario queda sincronizado en Campus como `ADMINISTRADOR`.
- Los usuarios de prueba de WordPress pueden ingresar y luego ser inscriptos desde Campus.
- La autenticacion local sigue disponible en modo `HYBRID`.

## Configuracion recomendada

En produccion:

```php
define('AUTH_MODE', strtoupper((string) config_env('AUTH_MODE', 'HYBRID')));
define('AUTH_DEBUG', '0');
define('WP_ROOT_PATH', '/home/u515462975/domains/mentemotion.com/public_html');
```

El modo `HYBRID` permite convivir con usuarios locales durante la transicion. Cuando la integracion este completamente probada, se puede evaluar pasar a `WORDPRESS`.

## Administrador como docente

Los usuarios con rol `ADMINISTRADOR` tambien pueden seleccionarse como docente o tutor de una materia. Esto permite que el administrador principal gestione el Campus y dicte clases con la misma cuenta.

Los estudiantes pueden enviar mensajes a sus docentes o tutores aunque ese usuario tenga rol `ADMINISTRADOR`.

## Perfiles

Se corrigio la edicion de perfiles para guardar:

- DNI
- telefono
- fecha de nacimiento
- domicilio
- provincia
- contenido de "Sobre mi"

Si un usuario sincronizado desde WordPress no tiene fila en `perfiles`, Campus la crea automaticamente al guardar.

## Debug

El debug de autenticacion solo debe activarse para diagnostico puntual:

```php
define('AUTH_DEBUG', '1');
```

Luego debe volver a:

```php
define('AUTH_DEBUG', '0');
```

El archivo `logs/auth-debug.log` puede contener emails y datos internos de autenticacion, por lo que no debe quedar guardado en produccion.
