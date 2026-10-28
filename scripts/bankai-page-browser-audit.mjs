// Render fixture: OZMAN_BANKAI_PREVIEW=1 with RestaurantNotificationRewardTest.
// All requests stay local; no real subscription, order or wheel spin is sent.
import {createServer} from 'node:http';
import {readFileSync,existsSync} from 'node:fs';
import assert from 'node:assert/strict';
const root=new URL('../',import.meta.url), origin='http://127.0.0.1:8138';
let saves=0, rewardCalls=0, expired=false, failSave=false;
const reward={title:'Welcome',welcome_id:'1',selected_index:null,segments:[{label:'A',color:'#00cfe8',discount_type:'percent',discount_value:10},{label:'B',color:'#087f93',discount_type:'percent',discount_value:10}]};
const inject=`<script>PushManager.prototype.getSubscription=async()=>null;PushManager.prototype.subscribe=async()=>({toJSON:()=>({endpoint:'https://fcm.googleapis.com/test',keys:{p256dh:'test',auth:'test'}})});</script>`;
let html=readFileSync(new URL('storage/app/bankai-preview.html',root),'utf8').replaceAll('http://localhost',origin).replaceAll('http:\\/\\/localhost',origin.replaceAll('/','\\/')).replace('</head>',inject+'</head>');
const server=createServer(async(req,res)=>{
 const path=new URL(req.url,origin).pathname;
 if(path==='/offers/push/subscription') {saves++;if(failSave){res.writeHead(503,{'Content-Type':'application/json'});res.end('{}');return;}expired=false;res.setHeader('Content-Type','application/json');res.end('{"registered":true}');return;}
 if(path.endsWith('/signup-reward')) {rewardCalls++;res.writeHead(expired?403:200,{'Content-Type':'application/json'});res.end(JSON.stringify(expired?{}:{reward}));return;}
 if(req.method!=='GET'){res.writeHead(405);res.end();return;}
 if(path.startsWith('/restaurants/')) {res.setHeader('Content-Type','text/html; charset=utf-8');res.end(html);return;}
 const file=new URL('public/'+path.replace(/^\//,'').replace(/^front-assets\//,''),root);
 if(file.href.startsWith(new URL('public/',root).href) && existsSync(file) && path.endsWith('.js')) {res.setHeader('Content-Type','application/javascript');res.end(readFileSync(file));return;}
 res.writeHead(404);res.end();
});
await new Promise(r=>server.listen(8138,'127.0.0.1',r));
let ws;
try {
 const tabs=await(await fetch('http://127.0.0.1:9227/json')).json();ws=new WebSocket(tabs.find(t=>t.type==='page').webSocketDebuggerUrl);await new Promise(r=>ws.onopen=r);
 let seq=0;const waiting=new Map(),errors=[];
 ws.onmessage=({data})=>{const m=JSON.parse(data);if(m.method==='Runtime.exceptionThrown')errors.push(m.params.exceptionDetails.text);const p=waiting.get(m.id);if(p){waiting.delete(m.id);m.error?p.reject(m.error):p.resolve(m.result);}};
 const send=(method,params={})=>new Promise((resolve,reject)=>{const id=++seq;waiting.set(id,{resolve,reject});ws.send(JSON.stringify({id,method,params}));});
 const run=async expression=>{const r=await send('Runtime.evaluate',{expression,returnByValue:true,awaitPromise:true});if(r.exceptionDetails)throw Error(r.exceptionDetails.exception?.description);return r.result.value;};
 const until=async e=>{for(let i=0;i<100;i++){if(await run(e))return;await new Promise(r=>setTimeout(r,50));}throw Error('Timeout: '+e);};
 await send('Page.enable');await send('Runtime.enable');
 for(const width of [320,390,768,1440]){
  await send('Browser.setPermission',{permission:{name:'notifications'},setting:'granted',origin});
  await send('Emulation.setDeviceMetricsOverride',{width,height:850,deviceScaleFactor:1,mobile:width<500});
  await send('Page.navigate',{url:origin+'/restaurants/bankai-sushi?w='+width});
  await until('window.OzmanOfferNotifications?.ready() && window.OzmanSignupReward');
  await until('!document.getElementById("signupWheelReopen").hidden');
  await run('document.querySelectorAll("dialog[open]").forEach(d=>d.close())');
  assert.equal(await run('document.documentElement.scrollWidth<=innerWidth'),true,'page overflow '+width);
  await run('document.getElementById("mobileCart").click()');
  assert.equal(await run('document.body.classList.contains("cart-open")'),true);
  assert.equal(await run('(()=>{const r=document.getElementById("cartPanel").getBoundingClientRect();return r.width<=innerWidth;})()'),true,'cart width');
  await run('document.getElementById("cartClose").click();document.querySelector(".meal").click()');
  await until('document.getElementById("mealModal").open');
  assert.equal(await run('(()=>{const r=document.getElementById("mealModal").getBoundingClientRect();return r.left>=0&&r.right<=innerWidth;})()'),true,'meal width');
  await run('document.getElementById("modalClose").click()');
  expired=true;const before=saves;
  await run('document.getElementById("signupWheelReopen").click()');
  await until('document.getElementById("shopSignupWheel").open');
  assert.equal(saves,before+1,'rebind expired server session');
  await run('document.getElementById("signupWheelClose").click()');
  console.log('PASS rendered Bankai page, cart, meal dialog, wheel/session recovery '+width+'px');
 }
 failSave=true;
 await send('Page.navigate',{url:origin+'/restaurants/bankai-sushi?failed-save'});
 await until('!!document.querySelector("[data-offer-push-prompt]")');
 assert.equal(await run('OzmanOfferNotifications.ready()'),false);
 assert.equal(await run('OzmanRestaurantCustomer.token()'),'');
 failSave=false;await run('document.querySelector("[data-offer-push-enable]").click()');
 await until('OzmanOfferNotifications.ready()');
 await until('!document.getElementById("signupWheelReopen").hidden');
 await send('Browser.setPermission',{permission:{name:'notifications'},setting:'denied',origin});
 await run('window.dispatchEvent(new Event("focus"))');
 assert.equal(await run('document.getElementById("notificationGiftEnable").hidden'),false);
 assert.equal(await run('document.getElementById("signupWheelReopen").hidden'),true);
 assert.equal(await run('OzmanSignupReward.discount(100)'),0);
 assert.equal(errors.length,0,errors.join('\n'));
 console.log('PASS failed save/retry and revoked permission clears gift display');
} finally {ws?.close();server.closeAllConnections();await new Promise(r=>server.close(r));}
