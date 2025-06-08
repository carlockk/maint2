const KEY='offlineQueue';
function enqueueRequest(url, body){
  const q=JSON.parse(localStorage.getItem(KEY)||'[]');
  q.push({url,body});
  localStorage.setItem(KEY,JSON.stringify(q));
}
function flushQueue(){
  if(!navigator.onLine) return;
  let q=JSON.parse(localStorage.getItem(KEY)||'[]');
  if(!q.length) return;
  const next=q[0];
  fetch(next.url,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:next.body})
    .then(r=>r.ok?r.text():Promise.reject())
    .then(()=>{q.shift();localStorage.setItem(KEY,JSON.stringify(q));flushQueue();})
    .catch(()=>{});
}
window.addEventListener('online',flushQueue);
document.addEventListener('DOMContentLoaded',flushQueue);
function sendOrQueue(url,params){
  if(navigator.onLine){
    return fetch(url,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:params});
  }else{
    enqueueRequest(url,params);
    alert('Datos guardados localmente. Se enviarán al recuperar la conexión.');
    return Promise.resolve({ok:true,offline:true});
  }
}
document.addEventListener('submit',e=>{
  if(e.target.classList.contains('offline-form')){
    if(!navigator.onLine){
      e.preventDefault();
      const data=new URLSearchParams(new FormData(e.target)).toString();
      enqueueRequest(e.target.action,data);
      alert('Datos guardados localmente. Se enviarán al recuperar la conexión.');
      e.target.reset();
    }
  }
});
