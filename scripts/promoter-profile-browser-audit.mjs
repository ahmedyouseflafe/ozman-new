// Generate the test-only HTML with OZMAN_PROFILE_PREVIEW=1 and PromoterFollowTest.
import {createServer} from 'node:http';
import {readFileSync,writeFileSync} from 'node:fs';
import assert from 'node:assert/strict';
const html=readFileSync(new URL('../storage/app/promoter-profile-preview.html',import.meta.url),'utf8');
let posts=0, success=false;
const server=createServer(async(req,res)=>{
    if(req.method==='POST'){
        let raw='';for await(const part of req)raw+=part;
        posts++;assert.ok(raw.includes('name="kind"\r\n\r\nstory'));assert.ok(raw.includes('filename="test.png"'));
        res.writeHead(success?201:422,{'Content-Type':'application/json'});
        res.end(JSON.stringify(success?{published:true}:{errors:{media:['Test validation error']}}));return;
    }
    res.setHeader('Content-Type','text/html; charset=utf-8');res.end(html);
});
await new Promise(resolve=>server.listen(8136,'127.0.0.1',resolve));
let ws;
try{
    const targets=await(await fetch('http://127.0.0.1:9227/json')).json();
    ws=new WebSocket(targets.find(t=>t.type==='page').webSocketDebuggerUrl);
    await new Promise((resolve,reject)=>{ws.onopen=resolve;ws.onerror=reject;});
    let id=0;const pending=new Map();
    ws.onmessage=({data})=>{const m=JSON.parse(data),p=pending.get(m.id);if(p){pending.delete(m.id);m.error?p.reject(m.error):p.resolve(m.result);}};
    const send=(method,params={})=>new Promise((resolve,reject)=>{const key=++id;pending.set(key,{resolve,reject});ws.send(JSON.stringify({id:key,method,params}));});
    const evaluate=async expression=>{const r=await send('Runtime.evaluate',{expression,returnByValue:true,awaitPromise:true});if(r.exceptionDetails)throw Error(r.exceptionDetails.exception?.description||r.exceptionDetails.text);return r.result.value;};
    const until=async expression=>{for(let i=0;i<100;i++){if(await evaluate(expression))return;await new Promise(r=>setTimeout(r,50));}throw Error('Timed out: '+expression);};
    await send('Page.enable');
    await send('Page.setInterceptFileChooserDialog',{enabled:true});
    for(const width of [320,390,1280]){
        success=false;
        await send('Emulation.setDeviceMetricsOverride',{width,height:850,deviceScaleFactor:1,mobile:width<500});
        await send('Page.navigate',{url:'http://127.0.0.1:8136/?w='+width});
        await until('!!document.getElementById("profileComposer")');
        assert.equal(await evaluate('document.documentElement.scrollWidth<=innerWidth'),true);
        assert.equal(await evaluate('!!document.getElementById("promoterFollowForm")'),false);
        if(width===390){
            await evaluate(`document.documentElement.setAttribute('data-theme','dark')`);
            const shot=await send('Page.captureScreenshot',{format:'png'});
            writeFileSync(new URL('../storage/app/promoter-profile-mobile.png',import.meta.url),Buffer.from(shot.data,'base64'));
        }
        await evaluate(`document.getElementById('reelsTab').click()`);
        assert.equal(await evaluate(`document.getElementById('posts').hidden && !document.getElementById('promoterScreen').hidden`),true);
        await evaluate(`document.getElementById('postsTab').click(); document.querySelector('[data-compose="story"]').click()`);
        await until('document.getElementById("profileComposer").open');
        assert.equal(await evaluate(`document.getElementById('profileComposerForm').elements.kind.value`),'story');
        assert.equal(await evaluate(`(()=>{const r=document.getElementById('profileComposer').getBoundingClientRect();return r.left>=0&&r.right<=innerWidth&&r.height<=innerHeight;})()`),true);
        await evaluate(`document.getElementById('profileComposerForm').action='/upload'; const bytes=Uint8Array.from(atob('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl6LwAAAABJRU5ErkJggg=='),c=>c.charCodeAt(0));const dt=new DataTransfer();dt.items.add(new File([bytes],'test.png',{type:'image/png'}));document.getElementById('composerFile').files=dt.files;document.getElementById('composerFile').dispatchEvent(new Event('change'));`);
        assert.equal(await evaluate(`!!document.querySelector('#composerPreview img') && !document.getElementById('composerPublish').disabled`),true);
        const before=posts;
        await evaluate(`document.getElementById('composerPublish').click();document.getElementById('composerPublish').click()`);
        await until(`document.getElementById('composerStatus').textContent==='Test validation error'`);
        assert.equal(posts,before+1);
        assert.equal(await evaluate('document.getElementById("composerFile").files.length'),1);
        if(width===390){const shot=await send('Page.captureScreenshot',{format:'png'});writeFileSync(new URL('../storage/app/promoter-composer-mobile.png',import.meta.url),Buffer.from(shot.data,'base64'));}
        success=true;
        await evaluate(`document.getElementById('composerPublish').click()`);
        await until(`document.getElementById('profileComposer') && !document.getElementById('profileComposer').open && !document.body.style.overflow`);
        assert.equal(posts,before+2);
        console.log('PASS '+width+'px: owner controls, tabs, inline story preview, validation, double submit, publish/reload');
    }
}finally{ws?.close();server.closeAllConnections();await new Promise(resolve=>server.close(resolve));}
