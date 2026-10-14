/* Ameen Gym service worker: makes the site installable and keeps static assets fast/offline. */
const VERSION = 'v3';
const STATIC_CACHE = `ameen-gym-static-${VERSION}`;
const OFFLINE_URL = '/offline.html';

const PRECACHE = [
  OFFLINE_URL,
  '/css/app.css',
  '/vendor/fonts/fonts.css',
  '/vendor/fontawesome/css/all.min.css',
  '/js/app.js',
  '/js/app-install.js',
  '/icons/icon-192.png',
  '/icons/icon-512.png',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(STATIC_CACHE)
      // one missing file must not stop the new version from installing
      .then((cache) => Promise.all(PRECACHE.map((url) => cache.add(url).catch(() => null))))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((k) => k !== STATIC_CACHE).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

function isStaticAsset(url) {
  if (url.origin === self.location.origin) {
    return /^\/(css|js|images|icons|vendor)\//.test(url.pathname) || url.pathname === '/favicon.ico';
  }
  return ['cdnjs.cloudflare.com', 'fonts.googleapis.com', 'fonts.gstatic.com', 'img.youtube.com'].includes(url.hostname);
}

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET') return;

  const url = new URL(request.url);

  // Pages are always loaded from the network (they hold personal data);
  // when the device is offline we show a friendly offline page instead.
  if (request.mode === 'navigate') {
    event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));
    return;
  }

  // Static assets: serve from cache, refresh in the background.
  if (isStaticAsset(url)) {
    event.respondWith(
      caches.open(STATIC_CACHE).then((cache) =>
        cache.match(request).then((cached) => {
          const network = fetch(request)
            .then((response) => {
              if (response.ok || response.type === 'opaque') cache.put(request, response.clone());
              return response;
            })
            .catch(() => cached);
          return cached || network;
        })
      )
    );
  }
});
