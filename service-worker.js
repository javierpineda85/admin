const CACHE_VERSION = 'mentemotion-campus-v1';
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const OFFLINE_URL = './offline.html';
const PRECACHE = [
  OFFLINE_URL,
  './css/adminlte.min.css',
  './css/classroom-theme.css',
  './js/adminlte.min.js',
  './pwa/icons/icon-192.png',
  './pwa/icons/icon-512.png'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then((cache) => cache.addAll(PRECACHE))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(
        keys.filter((key) => key.startsWith('mentemotion-campus-') && key !== STATIC_CACHE)
          .map((key) => caches.delete(key))
      ))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET') {
    return;
  }

  const url = new URL(request.url);
  if (url.origin !== self.location.origin) {
    return;
  }

  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request).catch(() => caches.match(OFFLINE_URL))
    );
    return;
  }

  const esRecursoEstatico = ['style', 'script', 'image', 'font'].includes(request.destination);
  const rutasEstaticas = ['/css/', '/js/', '/plugins/', '/pwa/', '/webfonts/'];
  const rutaPermitida = rutasEstaticas.some((ruta) => url.pathname.includes(ruta));
  if (!esRecursoEstatico || !rutaPermitida) {
    return;
  }

  event.respondWith(
    caches.match(request).then((cached) => {
      const network = fetch(request).then((response) => {
        if (response && response.ok) {
          const copia = response.clone();
          caches.open(STATIC_CACHE).then((cache) => cache.put(request, copia));
        }
        return response;
      });

      return cached || network;
    })
  );
});
