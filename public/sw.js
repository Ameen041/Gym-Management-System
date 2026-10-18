/* Himma service worker: makes the site installable, keeps static assets fast/offline and shows phone notifications. */
const VERSION = 'v6';
const STATIC_CACHE = `himma-static-${VERSION}`;
const OFFLINE_URL = '/offline.html';

const PRECACHE = [
  OFFLINE_URL,
  '/css/app.css',
  '/vendor/fonts/fonts.css',
  '/vendor/fontawesome/css/all.min.css',
  '/js/app.js',
  '/js/app-install.js',
  '/js/push.js',
  '/js/chat.js',
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
  return url.hostname === 'img.youtube.com';
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

// Phone notifications: the server sends {title, body, url, tag}, encrypted for this device.
self.addEventListener('push', (event) => {
  let data = {};
  try {
    data = event.data ? event.data.json() : {};
  } catch (e) {
    data = { body: event.data ? event.data.text() : '' };
  }

  event.waitUntil(self.registration.showNotification(data.title || 'همّة', {
    body: data.body || '',
    icon: '/icons/icon-192.png',
    badge: '/icons/badge-96.png',
    dir: 'auto',
    tag: data.tag || undefined,
    renotify: Boolean(data.tag),
    data: { url: data.url || '/' },
  }));
});

// Tapping a notification opens its page, in the app window if one is open.
self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const target = new URL((event.notification.data && event.notification.data.url) || '/', self.location.origin);
  if (target.origin !== self.location.origin) return;

  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windows) => {
      const same = windows.find((w) => w.url === target.href);
      if (same) return same.focus();
      const any = windows.find((w) => new URL(w.url).origin === target.origin && 'navigate' in w);
      if (any) {
        return any.navigate(target.href).then((w) => (w || any).focus())
          .catch(() => self.clients.openWindow(target.href));
      }
      return self.clients.openWindow(target.href);
    })
  );
});
