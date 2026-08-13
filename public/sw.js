/* Service worker ของหน้ากิจกรรมสาธารณะ (/activities) เท่านั้น
   ==========================================================================
   ไฟล์นี้อยู่ที่ราก scope จึงครอบทั้งโดเมน — รวมถึงหน้าหลังบ้านทุกหน้า

   เวอร์ชันก่อนหน้าใช้กลยุทธ์ cache-first กับ "ทุกคำขอ GET" ผลคือเมื่อหน้าใด
   ถูกแคชไปแล้ว เบราว์เซอร์จะส่งของเก่าคืนตลอดไปโดยไม่ถามเซิร์ฟเวอร์อีกเลย
   แก้โค้ด แก้ CSS ต่อ ?v= หรือกด Ctrl+F5 ก็ไม่มีผล เพราะคำขอไม่เคยออกจากเครื่อง
   (อาการ "หน้าจอไม่เปลี่ยนเลย" ที่ตามหาอยู่หลายรอบ)

   เวอร์ชันนี้:
     1. ไม่ยุ่งกับหน้าหลังบ้าน สคริปต์ สไตล์ และคำขอข้อมูลใด ๆ — ปล่อยผ่านไปที่เครือข่ายตรง ๆ
     2. เอกสาร HTML ของหน้าสาธารณะใช้ network-first เพื่อให้ได้ของใหม่เสมอ
        แล้วค่อยถอยไปใช้ของในแคชเมื่อออฟไลน์
     3. cache-first เหลือไว้เฉพาะรูปภาพและไอคอนที่ precache ไว้ ซึ่งไม่เปลี่ยนบ่อย

   ขึ้นเลข CACHE_NAME ทุกครั้งที่แก้ไฟล์นี้ ไม่งั้นแคชชุดเก่าจะไม่ถูกล้าง
   ========================================================================== */

const CACHE_NAME = 'farmconcept-v3';

const PRECACHE_URLS = [
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

/* เส้นทางที่ service worker ต้องไม่แตะเลย — ระบบหลังบ้านทั้งหมดและไฟล์ที่แก้บ่อย
   ข้อมูลหลังบ้านเป็นข้อมูลส่วนบุคคลด้วย ไม่ควรถูกเก็บลงแคชของเบราว์เซอร์ตั้งแต่แรก */
const BYPASS = [
  '/admin',
  '/login',
  '/logout',
  '/build/',
  '/assets/js/',
  '/assets/css/'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(PRECACHE_URLS))
  );

  /* เข้าแทนที่ตัวเก่าทันที ไม่ต้องรอปิดแท็บทั้งหมดก่อน */
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys()
      .then(keys => Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', event => {
  const request = event.request;

  if (request.method !== 'GET') return;

  const url = new URL(request.url);

  /* ข้ามคำขอข้ามโดเมน (ฟอนต์ CDN) และเส้นทางที่ห้ามแตะ */
  if (url.origin !== self.location.origin) return;
  if (BYPASS.some(prefix => url.pathname.startsWith(prefix))) return;

  /* เอกสาร HTML — เอาของใหม่ก่อนเสมอ ถอยไปใช้แคชเมื่อออฟไลน์ */
  if (request.mode === 'navigate' || request.destination === 'document') {
    event.respondWith(
      fetch(request)
        .then(response => {
          if (response.ok) {
            const clone = response.clone();
            caches.open(CACHE_NAME).then(cache => cache.put(request, clone));
          }

          return response;
        })
        .catch(() => caches.match(request))
    );

    return;
  }

  /* รูปภาพและไอคอนที่ precache ไว้ — cache-first เพราะไม่เปลี่ยนบ่อยและหนัก */
  if (request.destination === 'image') {
    event.respondWith(
      caches.match(request).then(cached => cached || fetch(request).then(response => {
        if (response.ok) {
          const clone = response.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(request, clone));
        }

        return response;
      }))
    );

    return;
  }

  /* ที่เหลือปล่อยผ่านไปที่เครือข่ายตามปกติ ไม่แคช */
});
