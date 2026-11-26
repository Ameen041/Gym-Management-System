/* Himma service worker: makes the site installable, shows phone notifications, and keeps the app working without
   internet: every page the person opens is kept on the phone (their plans and the exercises in them even before they
   open them, see /offline-pack and js/offline.js), with the pictures, styles and scripts it needs. */
const VERSION = 'v9';
const STATIC_CACHE = `himma-static-${VERSION}`; // styles, scripts, fonts, icons
const PICTURES_CACHE = 'himma-pictures';         // exercise pictures and profile photos (public files)
const PAGES_CACHE = 'himma-pages';               // the person's pages: emptied when someone logs out or in
const PRIVATE_CACHE = 'himma-private';           // their private pictures (check-in photos...): emptied with the pages
const KEEP = [STATIC_CACHE, PICTURES_CACHE, PAGES_CACHE, PRIVATE_CACHE];
const OFFLINE_URL = '/offline.html';
const PACK_KEY = '/__offline-pack';              // the version of the last pack kept (in PAGES_CACHE)
const MAX_PAGES = 120;
const MAX_PICTURES = 600;
const MAX_PRIVATE = 150;
// a weak signal: when the network takes longer than this and the page was kept, the kept copy shows (and is updated)
const SLOW_NETWORK_MS = 6000;
// pages that are never kept: the admin, signing in or out, the language switch
const NOT_KEPT = /^\/(admin|login|register|logout|lang|forgot-password|reset-password)(\/|$)/;
// dashboards an installed app can open on when the start page was not kept
const HOMES = ['/trainee-dashboard', '/trainer-dashboard'];

const PRECACHE = [
  OFFLINE_URL,
  '/css/app.css',
  '/css/rtl.css',
  '/css/notifications.css',
  '/vendor/fonts/fonts.css',
  '/vendor/fontawesome/css/all.min.css',
  '/js/app.js',
  '/js/app-install.js',
  '/js/offline.js',
  '/js/push.js',
  '/js/chat.js',
  '/icons/icon-192.png',
  '/icons/icon-512.png',
  '/images/avatar.svg',
];

// raised when the person's pages are emptied, so a pack being kept at that moment doesn't write into the new person's
let generation = 0;

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
      .then((keys) => Promise.all(keys.filter((k) => !KEEP.includes(k)).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  const url = new URL(request.url);
  if (url.origin !== self.location.origin || request.headers.has('range')) return;

  if (request.method !== 'GET') {
    const signInOrOut = request.method === 'POST' && /^\/(logout|login|register|account)$/.test(url.pathname);
    if (request.mode === 'navigate' || signInOrOut) {
      event.respondWith(fetch(request).then((response) => {
        // someone logged out, in, or deleted their account: the pages kept for the last person go
        if (signInOrOut) event.waitUntil(forgetPerson());
        return response;
      // a form sent without internet: a page that says so, instead of the browser's error
      }, () => (request.mode === 'navigate' ? offlinePage('data-not-sent') : Response.error())));
    }
    return;
  }

  if (request.mode === 'navigate') {
    if (NOT_KEPT.test(url.pathname)) {
      event.respondWith(fetch(request).catch(() => offlinePage()));
    } else {
      page(event, request);
    }
    return;
  }

  if (/^\/(css|js|icons|vendor)\//.test(url.pathname) || url.pathname === '/favicon.ico') {
    asset(event, request);
  } else if (/^\/(images|uploads|storage)\/.+\.(webp|png|jpe?g|gif|svg|avif)$/i.test(url.pathname)) {
    picture(event, request, url);
  } else if (request.destination === 'image') {
    privatePicture(event, request);
  }
});

/* ---------- pages: from the network, kept; the kept copy when there is no internet ---------- */

function page(event, request) {
  let keeping = Promise.resolve();
  const network = fetch(request).then((response) => {
    if (isPage(response)) keeping = keepPage(request.url, response.clone());
    return response;
  });
  event.waitUntil(network.then(() => keeping, () => null));
  event.respondWith(answer(request, network));
}

async function answer(request, network) {
  let kept = await caches.open(PAGES_CACHE).then((cache) => cache.match(request.url, { ignoreVary: true }));
  if (!kept && new URL(request.url).pathname === '/') kept = await keptHome();
  if (!kept) return network.catch(() => offlinePage());
  if (self.navigator.onLine === false) return markKept(kept);

  const slow = new Promise((resolve) => setTimeout(resolve, SLOW_NETWORK_MS, 'slow'));
  const first = await Promise.race([network.catch(() => 'failed'), slow]);

  // no answer, or the server is down for an update (5xx): the kept copy
  return first === 'slow' || first === 'failed' || first.status >= 500 ? markKept(kept) : first;
}

function isPage(response) {
  return response.status === 200 && !response.redirected
    && (response.headers.get('content-type') || '').includes('text/html');
}

// a page that sends on to another (Messages, for a client with one coach, opens that chat): kept under its own address
function landsOnPage(response) {
  if (!response.redirected || response.status !== 200 || !(response.headers.get('content-type') || '').includes('text/html')) return false;
  const final = new URL(response.url);

  return final.origin === self.location.origin && !NOT_KEPT.test(final.pathname);
}

async function keepPage(url, response) {
  const at = generation;
  const body = await response.blob();
  const headers = new Headers(response.headers);
  headers.set('X-Kept-At', new Date().toISOString());
  ['content-length', 'content-encoding', 'set-cookie'].forEach((h) => headers.delete(h));
  const cache = await caches.open(PAGES_CACHE);
  if (at !== generation) return;
  await cache.put(url, new Response(body, { status: 200, headers }));
  await trim(cache, MAX_PAGES);
}

// the kept copy says what it is: the page shows "no internet, saved on ..." (js/offline.js)
async function markKept(response) {
  const html = await response.text();
  const at = (response.headers.get('X-Kept-At') || '').replace(/[^0-9TZ:.\-]/g, '');
  const headers = new Headers(response.headers);
  headers.delete('content-length');

  return new Response(html.replace(/<html\b/i, `<html data-offline-copy="${at}"`), { status: 200, headers });
}

async function keptHome() {
  const cache = await caches.open(PAGES_CACHE);
  for (const path of HOMES) {
    const hit = await cache.match(new URL(path, self.location.origin).href, { ignoreVary: true });
    if (hit) return hit;
  }
  return null;
}

async function offlinePage(mark) {
  const page = await caches.match(OFFLINE_URL, { ignoreVary: true });
  if (!page) return new Response('<h1>Offline</h1>', { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' } });
  if (!mark) return page;
  const html = await page.text();

  return new Response(html.replace(/<html\b/i, `<html ${mark}`), { status: 200, headers: { 'Content-Type': 'text/html; charset=utf-8' } });
}

/* ---------- styles and scripts: kept, refreshed in the background ---------- */

function asset(event, request) {
  event.respondWith(caches.open(STATIC_CACHE).then((cache) =>
    cache.match(request, { ignoreVary: true }).then((cached) => {
      const network = fetch(request).then((response) => {
        if (response.ok) event.waitUntil(cache.put(request, response.clone()).then(() => dropOldVersions(cache, request.url)));
        return response;
      });
      if (cached) {
        event.waitUntil(network.catch(() => null));
        return cached;
      }
      // offline after an update: an older version of the file is better than none
      return network.catch(() => cache.match(request, { ignoreSearch: true, ignoreVary: true }).then((any) => any || Response.error()));
    })
  ));
}

// app.css?v=1, app.css?v=2...: only the newest is kept
async function dropOldVersions(cache, href) {
  const now = new URL(href);
  for (const key of await cache.keys()) {
    const old = new URL(key.url);
    if (old.pathname === now.pathname && old.search !== now.search) await cache.delete(key);
  }
}

/* ---------- pictures ---------- */

// Uploaded pictures get a new name when they change, so the kept one is always right; the pictures shipped with
// the app (images/) are refreshed in the background.
function picture(event, request, url) {
  const renamedOnChange = /^\/(uploads|storage)\//.test(url.pathname);
  event.respondWith(caches.open(PICTURES_CACHE).then((cache) =>
    cache.match(request, { ignoreVary: true, ignoreSearch: true }).then((cached) => {
      const network = fetch(request).then((response) => {
        if (response.ok) event.waitUntil(cache.put(request, response.clone()).then(() => trim(cache, MAX_PICTURES)));
        return response;
      });
      if (cached) {
        if (!renamedOnChange) event.waitUntil(network.catch(() => null));
        return cached;
      }
      // the page shows its own stand-in (js/app.js)
      return network.catch(() => new Response('', { status: 504, statusText: 'Offline' }));
    })
  ));
}

// pictures behind a login (check-in photos, payment receipts): from the network, kept with the person's pages
function privatePicture(event, request) {
  event.respondWith(fetch(request).then((response) => {
    if (response.ok && !response.redirected) {
      event.waitUntil(caches.open(PRIVATE_CACHE).then((cache) => cache.put(request, response.clone()).then(() => trim(cache, MAX_PRIVATE))));
    }
    return response;
  }).catch(() => caches.open(PRIVATE_CACHE)
    .then((cache) => cache.match(request, { ignoreVary: true }))
    .then((kept) => kept || new Response('', { status: 504, statusText: 'Offline' }))));
}

/* ---------- the pack: the person's plans and the exercises in them, kept before they are opened ---------- */

self.addEventListener('message', (event) => {
  const data = event.data || {};
  if (data.type === 'offline-pack' && data.pack && typeof data.pack.version === 'string') {
    event.waitUntil(keepPack(data.pack));
  }
});

async function keepPack(pack) {
  const at = generation;
  const pages = await caches.open(PAGES_CACHE);
  const pictures = await caches.open(PICTURES_CACHE);
  const packKey = new URL(PACK_KEY, self.location.origin).href;
  const last = await pages.match(packKey);
  // the same pack as last time: only what is missing is fetched (a page dropped to make room, say)
  const same = last ? (await last.text()) === pack.version : false;

  const jobs = []
    .concat((pack.pages || []).map((path) => ['page', path]))
    .concat((pack.files || []).map((path) => ['file', path]));

  await inTurns(jobs, 3, async ([kind, path]) => {
    if (at !== generation || typeof path !== 'string') return;
    const url = new URL(path, self.location.origin);
    if (url.origin !== self.location.origin) return;
    const cache = kind === 'page' ? pages : pictures;
    if ((same || kind === 'file') && await cache.match(url.href, { ignoreVary: true })) return;
    try {
      const response = await fetch(url.href, { credentials: 'same-origin', headers: kind === 'page' ? { Accept: 'text/html' } : {} });
      if (kind === 'page') {
        if (isPage(response) || landsOnPage(response)) await keepPage(url.href, response);
      } else if (response.ok) {
        await cache.put(url.href, response);
      }
    } catch (e) {
      // no internet half-way: the rest comes next time
    }
  });

  if (at === generation) {
    await pages.put(packKey, new Response(pack.version));
    await trim(pictures, MAX_PICTURES);
  }
}

// a few at a time, so the phone's connection stays usable
async function inTurns(jobs, size, work) {
  let next = 0;
  const worker = async () => {
    while (next < jobs.length) await work(jobs[next++]);
  };
  await Promise.all(Array.from({ length: size }, worker));
}

async function forgetPerson() {
  generation++;
  await Promise.all([caches.delete(PAGES_CACHE), caches.delete(PRIVATE_CACHE)]);
}

// the oldest go first; the pack's version stays
async function trim(cache, max) {
  const keys = (await cache.keys()).filter((key) => new URL(key.url).pathname !== PACK_KEY);
  for (let i = 0; i < keys.length - max; i++) await cache.delete(keys[i]);
}

/* ---------- phone notifications ---------- */

// The server sends {title, body, url, tag}, encrypted for this device.
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
