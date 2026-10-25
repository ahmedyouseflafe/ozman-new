// Local fixture exercising real CSS/JS. Backend permissions are covered by PromoterFollowTest.
import {createServer} from 'node:http';
import {readFileSync} from 'node:fs';
import assert from 'node:assert/strict';

const source = name => readFileSync(new URL('../public/'+name, import.meta.url), 'utf8');
let following = false, calls = 0, failure = 0;
const server = createServer((req, res) => {
    if (req.url === '/follow') {
        calls++;
        assert.equal(req.headers['x-csrf-token'], 'fixture-csrf');
        res.setHeader('Content-Type', 'application/json');
        if (failure) {res.writeHead(failure);res.end('{}');return;}
        following = req.method === 'PUT';
        setTimeout(() => res.end(JSON.stringify({following, followers_count: following ? 1 : 0})), 80);
        return;
    }
    res.setHeader('Content-Type', 'text/html; charset=utf-8');
    res.end(`<!doctype html><html lang="ar" dir="rtl"><meta name="viewport" content="width=device-width,initial-scale=1"><style>${source('promoter-social.css')}</style><main class="social-wrap"><section class="profile-hero"><h1>Rima Ali Salman</h1><div class="follow-control"><form id="promoterFollowForm" action="/follow" data-login="/account"><input type="hidden" name="_token" value="fixture-csrf"><input type="hidden" name="_method" value="PUT"><button class="button" aria-pressed="false">متابعة +</button></form><p id="promoterFollowStatus" role="status"></p></div><div class="profile-stats"><div><strong id="promoterFollowerCount">0</strong><span>متابع</span></div><div><strong>12</strong><span>منشور</span></div><div><strong>2</strong><span>ستوري الآن</span></div><div><strong>7</strong><span>محل مذكور</span></div></div></section></main><script>${source('promoter-follow.js')}</script></html>`);
});
await new Promise(resolve => server.listen(8135, '127.0.0.1', resolve));
let ws;
try {
    const targets = await (await fetch('http://127.0.0.1:9227/json')).json();
    ws = new WebSocket(targets.find(t => t.type === 'page').webSocketDebuggerUrl);
    await new Promise((resolve,reject) => {ws.onopen = resolve;ws.onerror = reject;});
    let id = 0;const pending = new Map();
    ws.onmessage = ({data}) => {const m=JSON.parse(data),p=pending.get(m.id);if(p){pending.delete(m.id);m.error?p.reject(m.error):p.resolve(m.result);}};
    const send=(method,params={})=>new Promise((resolve,reject)=>{const key=++id;pending.set(key,{resolve,reject});ws.send(JSON.stringify({id:key,method,params}));});
    const evaluate=async expression=>{const r=await send('Runtime.evaluate',{expression,returnByValue:true,awaitPromise:true});if(r.exceptionDetails)throw Error(r.exceptionDetails.text);return r.result.value;};
    const until=async expression=>{for(let i=0;i<80;i++){if(await evaluate(expression))return;await new Promise(r=>setTimeout(r,50));}throw Error('Timed out: '+expression);};
    await send('Page.enable');
    for (const width of [320,390,1280]) {
        following=false;failure=0;const before=calls;
        await send('Emulation.setDeviceMetricsOverride',{width,height:800,deviceScaleFactor:1,mobile:width<500});
        await send('Page.navigate',{url:'http://127.0.0.1:8135/?width='+width});
        await until('!!document.getElementById("promoterFollowForm")');
        assert.equal(await evaluate('document.documentElement.scrollWidth<=innerWidth'),true);
        await evaluate(`document.querySelector('button').click();document.querySelector('button').click()`);
        await until(`document.querySelector('button').getAttribute('aria-pressed')==='true'`);
        assert.equal(calls,before+1);
        assert.equal(await evaluate('document.getElementById("promoterFollowerCount").textContent'),'1');
        await evaluate(`document.querySelector('button').click()`);
        await until(`document.querySelector('button').getAttribute('aria-pressed')==='false'`);
        assert.equal(await evaluate('document.getElementById("promoterFollowerCount").textContent'),'0');
        for (const code of [419,429,500]) {
            failure=code;
            await evaluate(`document.querySelector('button').click()`);
            await until(`!document.querySelector('button').disabled && !!document.getElementById('promoterFollowStatus').textContent`);
            assert.equal(await evaluate('document.getElementById("promoterFollowerCount").textContent'),'0');
        }
        console.log(`PASS ${width}px: layout, follow/unfollow, double click, error recovery`);
    }
} finally {
    ws?.close();server.closeAllConnections();await new Promise(resolve=>server.close(resolve));
}
