# Despliegue selectivo en Hostinger

El despliegue Git integrado de hPanel reemplaza el contenido del sitio con el
estado de la rama seleccionada. Para evitar que los datos locales o los archivos
generados por usuarios lleguen a produccion, este proyecto usa un flujo de
GitHub Actions que envia solamente los archivos modificados mediante `rsync`.

El flujo se encuentra en `.github/workflows/deploy-hostinger.yml` y permanece
desactivado hasta completar la configuracion.

## Rutas protegidas

El flujo nunca despliega estas rutas:

- `config.local.php`
- `logs/`
- `img/secciones/`
- `img/usuarios/`
- `uploads/`

Las reglas `.htaccess` de los directorios de adjuntos son la unica excepcion
dentro de `uploads/`.

## Configuracion inicial

1. Descargar una copia de seguridad de los archivos y la base de datos del sitio.
2. En hPanel, habilitar el acceso SSH y agregar una clave publica exclusiva para
   GitHub Actions.
3. En GitHub, crear estos secretos del repositorio:
   - `HOSTINGER_SSH_HOST`: IP o host indicado por hPanel.
   - `HOSTINGER_SSH_PORT`: puerto SSH indicado por hPanel, normalmente `65002`.
   - `HOSTINGER_SSH_USER`: usuario SSH de Hostinger.
   - `HOSTINGER_SSH_PRIVATE_KEY`: clave privada correspondiente.
   - `HOSTINGER_SSH_KNOWN_HOSTS`: huella completa obtenida con
     `ssh-keyscan -p PUERTO HOST`.
4. En GitHub, crear estas variables del repositorio:
   - `HOSTINGER_DEPLOY_PATH`: ruta absoluta del `public_html` del sitio.
   - `HOSTINGER_DEPLOY_ENABLED`: mantener en `false` durante la preparacion.
5. Antes de integrar este cambio en `main`, desconectar el repositorio desde
   hPanel. Los archivos que ya estan publicados permanecen en el servidor.
6. Integrar el cambio en `main`. El primer flujo quedara omitido porque
   `HOSTINGER_DEPLOY_ENABLED` sigue en `false`.
7. Cambiar `HOSTINGER_DEPLOY_ENABLED` a `true`, ejecutar manualmente el flujo
   desde GitHub Actions y verificar el sitio.

## Comportamiento

- Un `push` a `main` inicia el flujo cuando `HOSTINGER_DEPLOY_ENABLED=true`.
- Se comparan el commit anterior y el nuevo.
- Solo se transfieren archivos agregados, copiados, modificados o renombrados.
- Los archivos eliminados no se borran automaticamente del servidor. Esta
  decision evita eliminaciones accidentales en produccion.
- Si el flujo se ejecuta manualmente, se sincronizan todos los archivos
  versionados que no formen parte de una ruta protegida.
