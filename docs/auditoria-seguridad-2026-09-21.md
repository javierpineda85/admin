# Auditoría de seguridad del Campus — 21/09/2026

## Alcance

La revisión combinó análisis del código PHP/MVC, pruebas contra bases descartables, pruebas HTTP locales y solicitudes pasivas de lectura al sitio publicado `https://campus.mentemotion.com/`. No se ejecutaron cargas destructivas, explotación de cuentas, fuerza bruta ni extracción de datos de producción.

## Hallazgos corregidos

- El flujo anterior de recuperación cambiaba credenciales sin acreditar el control del correo. Se reemplazó por tokens aleatorios de un solo uso, con hash en base, vencimiento, CSRF, respuesta uniforme y revocación de sesiones previas.
- La autenticación local aceptaba el hash almacenado como si fuera una contraseña. Ahora exige `password_verify`, actualiza hashes antiguos y limita intentos de acceso y recuperación.
- Las cargas de mensajes y recursos confiaban en la extensión o en el MIME enviado por el navegador. Ahora verifican contenido, extensión, tamaño, cantidad y nombres, y se sirven mediante el controlador autorizado.
- Los mensajes y contenidos enriquecidos podían conservar atributos activos. Ahora pasan por una lista permitida de etiquetas y atributos; scripts, eventos, estilos activos y esquemas peligrosos se eliminan.
- Se añadieron controles de origen para POST, cookies `HttpOnly` y `SameSite=Lax`, CSP, `nosniff`, política de referencia, política de permisos, HSTS en HTTPS productivo y respuestas dinámicas sin caché.
- PDO usa consultas preparadas nativas. Se retiró la API genérica que ejecutaba SQL arbitrario, se cerraron identificadores dinámicos de usuarios y se corrigió una consulta de integración WordPress incompatible con parámetros nativos.
- Dos volcados SQL que incluían registros de usuarios dejaron de estar versionados. Las copias locales se conservaron e ignoraron. Las pantallas HTML heredadas de AdminLTE también salieron del sitio y quedaron bloqueadas por Apache por compatibilidad con despliegues incrementales.

## Contraste pasivo con producción antes del despliegue

La portada respondió correctamente y redirigió al login. La CDN entregó HTTPS y respuestas sin caché. En ese momento la cookie publicada todavía no mostraba `HttpOnly` ni `SameSite`, se exponía `X-Powered-By: PHP/8.3.33`, y la CSP sólo contenía `upgrade-insecure-requests`. Las rutas `/login.html` y `/forgot-password.html` seguían públicas; los volcados SQL, `.git`, pruebas, migraciones y reglas `.htaccess` no resultaron descargables. Este estado corresponde a la versión publicada previa a integrar la rama `seguridad-del-sitio`.

## Dependencias del navegador

El proyecto conserva librerías copiadas dentro del repositorio y no posee un manifiesto que permita una auditoría automática reproducible. Se identificaron jQuery 3.6.0, Bootstrap 4.6.1 y Summernote 0.8.20. Las versiones actuales de referencia son jQuery 4.0.0, Bootstrap 5.3.8 y Summernote 0.9.0; las dos primeras implican cambios mayores de compatibilidad. Summernote 0.8.20 tiene además un reporte público de XSS basado en contenido HTML. La sanitización incorporada reduce esa exposición del lado servidor, pero conviene planificar la actualización de las tres dependencias con pruebas visuales completas.

- jQuery: https://github.com/jquery/jquery/releases
- Bootstrap: https://github.com/twbs/bootstrap/releases
- Summernote: https://github.com/summernote/summernote/releases
- Reporte sobre Summernote 0.8.20: https://github.com/summernote/summernote/issues/4638

## Límites y acciones operativas

- Los volcados eliminados siguen existiendo en el historial anterior de Git. Si el repositorio fue compartido fuera del equipo autorizado, se debe reescribir el historial y revisar la rotación de credenciales o datos que pudieran estar presentes.
- La seguridad de producción sólo podrá confirmarse después del despliegue, verificando nuevamente cabeceras, cookies, rutas heredadas, recuperación de correo y cargas reales.
- No se inspeccionó la configuración interna de Hostinger, sus copias de seguridad, permisos del usuario MySQL, firewall, retención de logs ni acceso SSH. Esos controles requieren acceso al panel o al servidor.
- El resultado reduce vulnerabilidades demostrables, pero no constituye garantía de invulnerabilidad. Debe repetirse tras cambios de autenticación, permisos, archivos o infraestructura.
