// Original, unbranded demonstration geometry. Not a scan of an advertised car.
// Metres: x = width, y = height, +z = front. Moving parts use real hinge pivots.
export function buildDemoCar(T, initial = {}) {
    const root = new T.Group();
    root.name = 'Ozman demonstration coupe';
    const parts = {}, targets = {};
    const material = (color, options = {}) => new T.MeshStandardMaterial({ color, ...options });
    const paint = material(0x176979, { metalness: .65, roughness: .28 });
    const dark = material(0x172128, { roughness: .7 });
    const rubber = material(0x12181c, { roughness: .93 });
    const silver = material(0xb9c9cc, { metalness: .85, roughness: .24 });
    const leather = material(0xc1956d, { roughness: .85 });
    const glass = material(0x90c5d2, { transparent: true, opacity: .23, depthWrite: false, side: T.DoubleSide, roughness: .15 });
    const white = material(0xe6fcff, { emissive: 0x94d4e5, emissiveIntensity: 1.2 });
    const red = material(0xc52137, { emissive: 0x9d1020, emissiveIntensity: .6 });
    function mesh(parent, geometry, mat, position) {
        const node = new T.Mesh(geometry, mat);
        node.position.set(...position); node.castShadow = mat !== glass; node.receiveShadow = mat !== glass;
        parent.add(node); return node;
    }
    function box(parent, size, position, mat, bevel = .025) {
        const [w, h, d] = size, r = Math.min(bevel, w / 4, h / 4, d / 4);
        if (!r) return mesh(parent, new T.BoxGeometry(w, h, d), mat, position);
        const s = new T.Shape();
        s.moveTo(-w/2+r, -h/2); s.lineTo(w/2-r, -h/2);
        s.quadraticCurveTo(w/2, -h/2, w/2, -h/2+r); s.lineTo(w/2, h/2-r);
        s.quadraticCurveTo(w/2, h/2, w/2-r, h/2); s.lineTo(-w/2+r, h/2);
        s.quadraticCurveTo(-w/2, h/2, -w/2, h/2-r); s.lineTo(-w/2, -h/2+r);
        s.quadraticCurveTo(-w/2, -h/2, -w/2+r, -h/2);
        const geo = new T.ExtrudeGeometry(s, { depth: d-2*r, bevelEnabled: true, bevelSize:r, bevelThickness:r, bevelSegments:2, steps:1, curveSegments:3 });
        geo.translate(0, 0, -d/2+r);
        return mesh(parent, geo, mat, position);
    }
    function bar(parent, a, b, radius, mat) {
        const start = new T.Vector3(...a), end = new T.Vector3(...b);
        const node = mesh(parent, new T.CylinderGeometry(radius, radius, start.distanceTo(end), 10), mat, start.clone().add(end).multiplyScalar(.5).toArray());
        node.quaternion.setFromUnitVectors(new T.Vector3(0,1,0), end.sub(start).normalize());
        return node;
    }
    function panel(parent, corners, mat) {
        const geo = new T.BufferGeometry();
        geo.setAttribute('position', new T.Float32BufferAttribute(corners.flat(), 3));
        geo.setIndex([0,1,2,0,2,3]); geo.computeVertexNormals();
        return mesh(parent, geo, mat, [0,0,0]);
    }
    function hinge(key, position, axis, openAngle) {
        const group = new T.Group(); group.position.set(...position); root.add(group);
        group.name = key; group.userData = { part:key, axis, openAngle };
        parts[key] = group; targets[key] = 0; return group;
    }
    box(root, [1.74,.14,4.35], [0,.38,0], dark);
    box(root, [1.65,.09,2.25], [0,.49,-.12], rubber);
    // Open cabin and storage bays: no opaque block under the movable lids.
    for (const side of [-1,1]) {
        box(root,[.14,.2,3.9],[side*.87,.48,0],paint);
        for (const z of [-1.52,1.52]) {
            const fender = new T.Shape();
            fender.moveTo(-.66,.35); fender.lineTo(-.66,.88); fender.quadraticCurveTo(0,1.05,.66,.88);
            fender.lineTo(.66,.35); fender.lineTo(.44,.35);
            fender.absarc(0,.38,.44,0,Math.PI,false); fender.lineTo(-.66,.35);
            const geo = new T.ExtrudeGeometry(fender,{depth:.16,bevelEnabled:false,curveSegments:24});
            // Shape x is the car's z; extrusion points toward the centre.
            const node=mesh(root,geo,paint,[side*.94,0,z]);
            node.rotation.y=-side*Math.PI/2;
            const wheel=new T.Group(); wheel.position.set(side*.91,.39,z); root.add(wheel);
            const tire=mesh(wheel,new T.CylinderGeometry(.37,.37,.23,40),rubber,[0,0,0]); tire.rotation.z=Math.PI/2;
            const rim=mesh(wheel,new T.CylinderGeometry(.275,.275,.245,32),silver,[0,0,0]); rim.rotation.z=Math.PI/2;
            const inset=mesh(wheel,new T.CylinderGeometry(.232,.232,.25,32),dark,[0,0,0]); inset.rotation.z=Math.PI/2;
            for(let i=0;i<5;i++){
                const a=i*Math.PI*2/5;
                const spoke=box(wheel,[.025,.055,.43],[side*.132,0,0],silver,.008); spoke.rotation.x=a;
            }
            const cap=mesh(wheel,new T.CylinderGeometry(.067,.067,.27,24),silver,[0,0,0]); cap.rotation.z=Math.PI/2;
        }
    }
    box(root,[1.81,.32,.23],[0,.60,2.15],paint,.065);
    box(root,[1.15,.19,.05],[0,.59,2.29],dark);
    for(const x of [-.73,-.56,.56,.73]) box(root,[.14,.065,.045],[x,.82,2.25],white,.012);
    for(let x=-.5;x<=.51;x+=.1) box(root,[.025,.14,.035],[x,.59,2.325],silver,.005);
    box(root,[1.82,.32,.24],[0,.61,-2.13],paint,.055);
    for(const x of [-.65,.65]) box(root,[.38,.08,.035],[x,.84,-2.27],red,.015);
    box(root,[1.43,.10,1.34],[0,1.48,-.25],paint,.04);
    for(const side of [-1,1]){
        bar(root,[side*.86,.95,.94],[side*.70,1.47,.41],.035,paint);
        bar(root,[side*.84,.96,-1.37],[side*.70,1.47,-.9],.05,paint);
        bar(root,[side*.70,1.47,.41],[side*.70,1.47,-.9],.035,paint);
    }
    panel(root,[[-.86,.95,.94],[.86,.95,.94],[.70,1.46,.41],[-.70,1.46,.41]],glass);
    panel(root,[[.84,.96,-1.37],[-.84,.96,-1.37],[-.70,1.46,-.90],[.70,1.46,-.90]],glass);
    for(const side of [-1,1]){
        const door=hinge(side===-1?'driverDoor':'passengerDoor',[side*.88,.55,.83],'y',-side*1.12);
        box(door,[.105,.44,1.52],[0,.23,-.76],paint);
        box(door,[.035,.31,1.33],[-side*.065,.24,-.76],leather,.012);
        bar(door,[0,.47,0],[-side*.17,.90,-.43],.023,paint);
        bar(door,[-side*.17,.90,-.43],[-side*.17,.90,-1.46],.023,paint);
        bar(door,[-side*.17,.90,-1.46],[0,.47,-1.52],.023,paint);
        panel(door,[[0,.47,0],[0,.47,-1.52],[-side*.17,.90,-1.46],[-side*.17,.90,-.43]],glass);
        box(door,[.04,.035,.20],[side*.075,.37,-1.25],silver,.008);
        box(door,[.25,.12,.25],[side*.13,.53,-.06],paint,.035);
        box(door,[.21,.085,.012],[side*.13,.53,-.19],silver,.015);
    }
    // Engine bay and a bonnet pivoted at the windscreen.
    box(root,[1.52,.08,1.15],[0,.56,1.5],dark);
    const hood=hinge('hood',[0,.96,.88],'x',-1.03);
    box(hood,[1.57,.07,1.32],[0,-.02,.66],paint,.025);
    box(hood,[1.34,.015,1.1],[0,-.065,.66],dark,.01);
    box(root,[.66,.27,.67],[0,.73,1.50],silver,.035);
    box(root,[.60,.075,.57],[0,.91,1.5],dark,.02);
    for(const x of [-.20,-.08,.04,.16]) box(root,[.035,.025,.40],[x,.958,1.5],silver,.004);
    box(root,[.27,.22,.36],[-.57,.71,1.25],dark);
    box(root,[.07,.025,.06],[-.57,.835,1.22],red,.003);
    box(root,[.23,.23,.28],[.57,.72,1.27],material(0xd8dfd5));
    for(const x of [-.43,.43]) bar(root,[x,.77,1.7],[x*.6,.83,1.35],.045,rubber);
    box(root,[1.12,.20,.09],[0,.69,2.05],dark);
    // Rear luggage compartment.
    box(root,[1.48,.06,.82],[0,.55,-1.72],rubber);
    for(const side of [-1,1])box(root,[.06,.32,.78],[side*.73,.72,-1.72],rubber);
    box(root,[1.48,.32,.05],[0,.72,-1.30],rubber);
    const trunk=hinge('trunk',[0,.97,-1.31],'x',1.18);
    box(trunk,[1.57,.075,.88],[0,0,-.44],paint);
    box(trunk,[1.35,.018,.68],[0,-.055,-.44],rubber,.01);
    // Four upholstered seats, stitching, centre console, and dashboard.
    for(const x of [-.46,.46])for(const z of [-.12,-.91]){
        box(root,[.56,.18,.52],[x,.64,z],leather,.06);
        const back=box(root,[.56,.47,.14],[x,.92,z-.20],leather,.045); back.rotation.x=-.10;
        box(root,[.30,.18,.12],[x,1.24,z-.23],leather,.035);
        for(const offset of [-.19,.19])box(root,[.012,.37,.012],[x+offset,.94,z-.12],silver,.003);
    }
    box(root,[.25,.23,1.15],[0,.64,.08],dark);
    box(root,[.22,.06,.34],[0,.79,-.08],leather);
    bar(root,[0,.78,.3],[0,.9,.32],.022,silver);
    box(root,[.07,.06,.09],[0,.92,.32],dark);
    box(root,[1.54,.20,.30],[0,.97,.69],dark,.035);
    box(root,[.34,.21,.035],[0,1.14,.52],dark,.016);
    for(const x of [-.57,.57])for(let i=0;i<4;i++)box(root,[.19,.012,.02],[x,.96+i*.025,.525],silver,.003);
    box(root,[.37,.16,.035],[-.45,1.12,.525],dark,.015);
    // Original canvas instrument graphics, drawn locally; no image downloads.
    function display(width,height,x,y,z,draw) {
        const canvas=document.createElement('canvas');canvas.width=512;canvas.height=256;
        const ctx=canvas.getContext('2d');ctx.fillStyle='#091d27';ctx.fillRect(0,0,512,256);draw(ctx);
        const texture=new T.CanvasTexture(canvas);texture.colorSpace=T.SRGBColorSpace;
        const mat=new T.MeshBasicMaterial({map:texture});
        const node=mesh(root,new T.PlaneGeometry(width,height),mat,[x,y,z]);
        node.rotation.y=Math.PI;
    }
    display(.34,.14,-.45,1.12,.503,ctx=>{
        for(const cx of [135,377]){
            ctx.strokeStyle='#3dd4df';ctx.lineWidth=8;ctx.beginPath();ctx.arc(cx,130,88,.7,Math.PI*2-.7);ctx.stroke();
            for(let i=0;i<9;i++){const a=.7+i*(Math.PI*2-1.4)/8;ctx.strokeStyle='#bdd4dc';ctx.lineWidth=3;ctx.beginPath();ctx.moveTo(cx+70*Math.cos(a),130+70*Math.sin(a));ctx.lineTo(cx+81*Math.cos(a),130+81*Math.sin(a));ctx.stroke();}
            ctx.strokeStyle='#ff986c';ctx.lineWidth=5;ctx.beginPath();ctx.moveTo(cx,130);ctx.lineTo(cx-48,172);ctx.stroke();
        }
        ctx.fillStyle='#fff';ctx.textAlign='center';ctx.font='bold 38px sans-serif';ctx.fillText('0',135,140);ctx.fillText('P',377,140);
        ctx.font='16px sans-serif';ctx.fillText('km/h',135,167);ctx.fillText('DEMO',377,167);
    });
    display(.30,.17,0,1.14,.498,ctx=>{
        ctx.strokeStyle='#254a58';ctx.lineWidth=9;
        for(let i=0;i<6;i++){ctx.beginPath();ctx.moveTo(i*100-100,0);ctx.lineTo(i*100+100,256);ctx.stroke();ctx.beginPath();ctx.moveTo(0,i*65);ctx.lineTo(512,i*65);ctx.stroke();}
        ctx.strokeStyle='#2de0e3';ctx.lineWidth=10;ctx.beginPath();ctx.moveTo(80,230);ctx.lineTo(170,140);ctx.lineTo(300,140);ctx.lineTo(390,40);ctx.stroke();
        ctx.fillStyle='#fff';ctx.font='bold 22px sans-serif';ctx.fillText('OZMAN / DEMO',24,33);
    });
    bar(root,[-.46,.99,.57],[-.46,1.05,.32],.045,dark);
    const steering=mesh(root,new T.TorusGeometry(.16,.024,10,40),rubber,[-.46,1.08,.31]); steering.rotation.x=.20;
    box(root,[.24,.025,.035],[-.46,1.08,.31],silver,.005);
    box(root,[.07,.07,.05],[-.46,1.08,.31],dark);
    // A genuine recess behind a bottom-hinged glove compartment.
    box(root,[.43,.02,.19],[.47,.75,.67],rubber,.006);
    box(root,[.43,.20,.02],[.47,.84,.77],rubber,.006);
    for(const x of [.26,.68])box(root,[.02,.20,.19],[x,.84,.67],rubber,.006);
    const glovebox=hinge('glovebox',[.47,.74,.51],'x',-1.1);
    box(glovebox,[.45,.20,.035],[0,.10,0],dark,.015);
    box(glovebox,[.13,.018,.018],[0,.17,-.025],silver,.003);
    function setOptions(options, snap=false){
        for(const [key,part] of Object.entries(parts)){
            targets[key]=options[key]?part.userData.openAngle:0;
            if(snap)part.rotation[part.userData.axis]=targets[key];
        }
    }
    function update(dt){
        let moving=false;
        for(const [key,part] of Object.entries(parts)){
            const axis=part.userData.axis, delta=targets[key]-part.rotation[axis];
            if(Math.abs(delta)>.001){part.rotation[axis]+=delta*Math.min(1,dt*9);moving=true;}
            else part.rotation[axis]=targets[key];
        }
        return moving;
    }
    setOptions(initial,true);
    return {root,parts,setOptions,update,dimensions:{length:4.6,width:1.96,height:1.54}};
}
