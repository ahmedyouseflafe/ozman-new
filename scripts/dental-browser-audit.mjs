import {createServer} from 'node:http';
import {readFileSync,writeFileSync} from 'node:fs';
import assert from 'node:assert/strict';
const root=new URL('../',import.meta.url);
const html=readFileSync(new URL('storage/app/dental-preview.html',root),'utf8');
const server=createServer((req,res)=>{res.setHeader('Content-Type','text/html; charset=utf-8');res.end(html);});
await new Promise(r=>server.listen(8139,'127.0.0.1',r));
let ws;
try {
 const tabs=await(await fetch('http://127.0.0.1:9227/json')).json();ws=new WebSocket(tabs.find(t=>t.type==='page').webSocketDebuggerUrl);await new Promise(r=>ws.onopen=r);
 let n=0;const pending=new Map();ws.onmessage=({data})=>{const m=JSON.parse(data),p=pending.get(m.id);if(p){pending.delete(m.id);m.error?p.reject(m.error):p.resolve(m.result);}};
 const send=(method,params={})=>new Promise((resolve,reject)=>{const id=++n;pending.set(id,{resolve,reject});ws.send(JSON.stringify({id,method,params}));});
 const run=async expression=>(await send('Runtime.evaluate',{expression,returnByValue:true})).result.value;
 await send('Page.enable');
 for(const width of [320,390,768,1440]) {
  await send('Emulation.setDeviceMetricsOverride',{width,height:900,deviceScaleFactor:1,mobile:width<500});
  await send('Page.navigate',{url:'http://127.0.0.1:8139/?w='+width});
  for(let i=0;i<40;i++){if(await run('document.readyState==="complete" && !!document.querySelector(".hero")'))break;await new Promise(r=>setTimeout(r,50));}
  assert.equal(await run('document.documentElement.scrollWidth<=innerWidth'),true,'overflow '+width);
  assert.equal(await run('getComputedStyle(document.querySelector(".hero")).display'),'grid','real rendered styles loaded');
  assert.equal(await run('!!document.querySelector("#services .service-card")'),true);
  assert.equal(await run('document.querySelector(".actions a").getAttribute("href")'), '#contact');
  if(width===390 || width===1440){const shot=await send('Page.captureScreenshot',{format:'png',captureBeyondViewport:true});writeFileSync(new URL('storage/app/dental-'+width+'.png',root),Buffer.from(shot.data,'base64'));}
  console.log('PASS dental layout and service/contact links '+width+'px');
 }
} finally {ws?.close();server.closeAllConnections();await new Promise(r=>server.close(r));}
