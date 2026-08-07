const CACHE_NAME = 'farmconcept-v2';
const PRECACHE_URLS = [
  'activities.html',
  'manifest.json',
  'assets/icons/icon-192.png',
  'assets/icons/icon-512.png',
  'assets/images/photo-terrarium-featured.png',
  'assets/images/photo-pot-painting.png',
  'assets/images/photo-flower-arranging.png',
  'assets/images/photo-baking-workshop.png',
  'assets/images/photo-market-vegetables.png',
  'assets/images/photo-garden-concert.png',
  'assets/images/photo-messy-play.png',
  'assets/images/photo-dog-run.png'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(PRECACHE_URLS))
  );
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
    )
  );
  self.clients.claim();
});

// Cache-first, falling back to network, then falling back to whatever's cached if offline.
self.addEventListener('fetch', event => {
  if (event.request.method !== 'GET') return;
  event.respondWith(
    caches.match(event.request).then(cached => {
      if (cached) return cached;
      return fetch(event.request).then(response => {
        if (response.ok) {
          const clone = response.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
        }
        return response;
      }).catch(() => cached);
    })
  );
});
