/* Thai News service worker v1.0.26 — static app shell and offline fallback */
'use strict';

const CACHE_NAME='thai-news-v1.0.26';
const APP_SHELL=[
  '/offline.html',
  '/manifest.webmanifest',
  '/assets/css/site.css?v=1.0.26',
  '/assets/js/site.js?v=1.0.26',
  '/assets/js/tour.js?v=1.0.26',
  '/assets/fonts/inter-latin.woff2',
  '/assets/fonts/inter-latin-ext.woff2',
  '/assets/fonts/noto-sans-thai.woff2',
  '/img/logo_trans.png?v=1.0.26',
  '/img/pwa/icon-192.png',
  '/img/pwa/icon-512.png',
  '/img/pwa/icon-maskable-512.png',
  '/img/pwa/apple-touch-icon.png',
  '/img/flags/se.svg?v=1.0.26',
  '/img/flags/gb.svg?v=1.0.26',
  '/img/flags/th.svg?v=1.0.26'
];

self.addEventListener('install',event=>{
  event.waitUntil(caches.open(CACHE_NAME).then(cache=>cache.addAll(APP_SHELL)).then(()=>self.skipWaiting()));
});

self.addEventListener('activate',event=>{
  event.waitUntil(
    caches.keys()
      .then(keys=>Promise.all(keys.filter(key=>key.startsWith('thai-news-')&&key!==CACHE_NAME).map(key=>caches.delete(key))))
      .then(()=>self.clients.claim())
  );
});

self.addEventListener('fetch',event=>{
  const request=event.request;
  if(request.method!=='GET')return;
  const url=new URL(request.url);
  if(url.origin!==self.location.origin)return;

  if(request.mode==='navigate'){
    event.respondWith(fetch(request).catch(()=>caches.match('/offline.html')));
    return;
  }

  if(url.pathname.startsWith('/assets/')||url.pathname.startsWith('/img/')||url.pathname==='/manifest.webmanifest'){
    event.respondWith(
      caches.match(request).then(cached=>cached||fetch(request).then(response=>{
        if(response.ok){
          const copy=response.clone();
          caches.open(CACHE_NAME).then(cache=>cache.put(request,copy));
        }
        return response;
      }))
    );
  }
});
