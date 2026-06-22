# Campus como web app instalable

El campus incluye una Progressive Web App (PWA) para crear un acceso instalado en computadoras y dispositivos moviles.

## Componentes

- `manifest.webmanifest`: nombre, colores, iconos, alcance y accesos rapidos.
- `service-worker.js`: cache del shell estatico y pantalla offline.
- `offline.html`: respuesta neutra cuando no hay conexion.
- `js/pwa-install.js`: registro del service worker y flujo de instalacion.
- `pwa/icons/`: iconos de 192, 512 y 512 maskable.

## Instalacion

- Chrome y Edge: el boton `Instalar app` abre el dialogo nativo cuando esta disponible.
- Safari en iPhone o iPad: el boton explica el flujo `Compartir > Agregar a pantalla de inicio`.
- Otros navegadores: se muestran las instrucciones para instalar o crear un acceso directo.

## Seguridad y cache

- Las paginas autenticadas se solicitan siempre a la red y no se guardan en cache.
- No se cachean mensajes, calificaciones, avatares, banners ni archivos subidos.
- Solo se almacenan CSS, JavaScript, plugins, fuentes, iconos PWA y la pantalla offline.

## Requisitos de produccion

- El sitio debe funcionar bajo HTTPS.
- `manifest.webmanifest`, `service-worker.js`, `offline.html` y `pwa/icons/` deben publicarse junto con el campus.
- Cuando cambien recursos precacheados, incrementar `CACHE_VERSION` en `service-worker.js`.
