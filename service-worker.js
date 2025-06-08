const CACHE_NAME='maintcheck-cache-v1';
const ASSETS=[
  '/',
  '/maintcheck/index.php',
  '/maintcheck/style.css',
  '/maintcheck/offline.js'
];
self.addEventListener('install',e=>{
  e.waitUntil(caches.open(CACHE_NAME).then(c=>c.addAll(ASSETS)));
});
self.addEventListener('fetch',e=>{
  if(e.request.method==='GET'){
    e.respondWith(
      caches.match(e.request).then(r=>r||fetch(e.request))
    );
  }
});
