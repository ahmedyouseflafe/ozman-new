const mount = document.getElementById('electronicsWebgl');

if (mount) {
    const canvas = document.createElement('canvas');
    const gl = canvas.getContext('webgl2', {
        alpha: true,
        antialias: true,
        powerPreference: 'high-performance',
    });

    if (gl) {
        const vertexSource = `#version 300 es
            in vec2 position;
            void main() { gl_Position = vec4(position, 0.0, 1.0); }
        `;
        const fragmentSource = `#version 300 es
            precision highp float;
            out vec4 fragColor;
            uniform vec2 resolution;
            uniform float time;
            uniform vec2 pointer;

            #define PI 3.14159265359

            mat2 rot(float a) { float c=cos(a),s=sin(a); return mat2(c,-s,s,c); }
            float roundedBox(vec3 p, vec3 b, float r) {
                vec3 q=abs(p)-b+r;
                return min(max(q.x,max(q.y,q.z)),0.0)+length(max(q,0.0))-r;
            }
            float torus(vec3 p, vec2 t) { vec2 q=vec2(length(p.xz)-t.x,p.y); return length(q)-t.y; }

            vec2 mapScene(vec3 p) {
                p.y += sin(time*.8)*.07;
                p.xz *= rot(-.42-pointer.x*.38);
                p.yz *= rot(.08+pointer.y*.16);
                float body=roundedBox(p,vec3(1.12,2.2,.16),.20);
                float screen=roundedBox(p-vec3(0.,0.,.175),vec3(1.01,2.07,.025),.15);
                float island=roundedBox(p-vec3(0.,1.83,.225),vec3(.30,.065,.018),.055);
                float d=body; float id=1.;
                if(screen<d){d=screen;id=2.;}
                if(island<d){d=island;id=3.;}
                return vec2(d,id);
            }

            vec3 normalAt(vec3 p) {
                vec2 e=vec2(.0015,0.);
                float d=mapScene(p).x;
                return normalize(vec3(mapScene(p+e.xyy).x-d,mapScene(p+e.yxy).x-d,mapScene(p+e.yyx).x-d));
            }

            float softGlow(float d,float width){return width/(abs(d)+width);}

            void main() {
                vec2 uv=(gl_FragCoord.xy*2.-resolution.xy)/resolution.y;
                vec3 color=vec3(0.);

                // Holographic rings and technical scan lines behind the phone.
                vec2 ringUv=uv-vec2(-.05,.02);
                float ring=abs(length(ringUv)-1.55);
                color += vec3(.03,.75,1.)*softGlow(ring,.004)*.42;
                float ring2=abs(length((uv-vec2(.12,-.05))*vec2(1.,1.7))-1.15);
                color += vec3(.42,.25,1.)*softGlow(ring2,.003)*.32;
                float scan=step(.985,sin((uv.y+time*.08)*75.)*.5+.5);
                color += vec3(.05,.45,.65)*scan*smoothstep(1.8,.15,abs(uv.x))*.07;

                vec3 ro=vec3(0.,0.,7.2), rd=normalize(vec3(uv,-2.35));
                float travel=0.; vec2 hit=vec2(0.); vec3 p;
                for(int i=0;i<100;i++){
                    p=ro+rd*travel; hit=mapScene(p);
                    if(hit.x<.001||travel>12.) break;
                    travel+=hit.x*.72;
                }

                if(travel<12.) {
                    vec3 n=normalAt(p);
                    vec3 light=normalize(vec3(-3.,4.,5.));
                    float diff=max(dot(n,light),0.);
                    float rim=pow(1.-max(dot(n,-rd),0.),2.8);
                    if(hit.y<1.5) color += vec3(.035,.055,.08)*(diff+.3)+vec3(.12,.75,1.)*rim*.7;
                    else if(hit.y<2.5) {
                        vec2 screenUv=p.xy;
                        float aurora=.5+.5*sin(screenUv.y*2.3+sin(screenUv.x*3.+time*.4));
                        vec3 screenColor=mix(vec3(.015,.07,.13),vec3(.25,.08,.55),aurora);
                        screenColor+=vec3(.02,.65,1.)*softGlow(abs(length(screenUv-vec2(.3,.45))-.62),.016)*.33;
                        screenColor+=vec3(.6,.2,1.)*softGlow(abs(length(screenUv+vec2(.2,.55))-.82),.012)*.25;
                        color += screenColor*(.72+diff*.35)+vec3(.2,.9,1.)*rim*.48;
                    } else color += vec3(.003,.005,.008)+vec3(.1,.7,1.)*rim*.18;
                }

                // Floating spec nodes: camera, 5G, battery and chip motifs.
                for(int i=0;i<5;i++){
                    float fi=float(i);
                    float a=fi*PI*2./5.+time*.15;
                    vec2 node=vec2(cos(a)*1.72,sin(a)*1.02);
                    float nodeRing=abs(length(uv-node)-(.075+.012*sin(time+fi)));
                    color+=mix(vec3(.02,.7,1.),vec3(.5,.25,1.),fi/4.)*softGlow(nodeRing,.006)*.7;
                    color+=vec3(.12,.75,1.)*softGlow(length(uv-node),.003)*.8;
                }

                float vignette=smoothstep(2.1,.42,length(uv*.72));
                color*=.62+.38*vignette;
                color=1.-exp(-color*1.28);
                fragColor=vec4(color,max(max(color.r,color.g),color.b));
            }
        `;

        const compile = (type, source) => {
            const shader = gl.createShader(type);
            gl.shaderSource(shader, source);
            gl.compileShader(shader);
            if (!gl.getShaderParameter(shader, gl.COMPILE_STATUS)) throw new Error(gl.getShaderInfoLog(shader));
            return shader;
        };

        try {
            const program = gl.createProgram();
            gl.attachShader(program, compile(gl.VERTEX_SHADER, vertexSource));
            gl.attachShader(program, compile(gl.FRAGMENT_SHADER, fragmentSource));
            gl.linkProgram(program);
            if (!gl.getProgramParameter(program, gl.LINK_STATUS)) throw new Error(gl.getProgramInfoLog(program));
            gl.useProgram(program);

            const buffer = gl.createBuffer();
            gl.bindBuffer(gl.ARRAY_BUFFER, buffer);
            gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1,-1,3,-1,-1,3]), gl.STATIC_DRAW);
            const position = gl.getAttribLocation(program, 'position');
            gl.enableVertexAttribArray(position);
            gl.vertexAttribPointer(position, 2, gl.FLOAT, false, 0, 0);

            const uniforms = {
                resolution: gl.getUniformLocation(program, 'resolution'),
                time: gl.getUniformLocation(program, 'time'),
                pointer: gl.getUniformLocation(program, 'pointer'),
            };
            mount.prepend(canvas);
            mount.querySelector('.webgl-fallback')?.remove();

            let targetX=0, targetY=0, currentX=0, currentY=0, visible=true, frame=0;
            const resize = () => {
                const ratio=Math.min(window.devicePixelRatio||1,1.6);
                const width=Math.max(1,Math.floor(mount.clientWidth*ratio));
                const height=Math.max(1,Math.floor(mount.clientHeight*ratio));
                if(canvas.width!==width||canvas.height!==height){canvas.width=width;canvas.height=height;gl.viewport(0,0,width,height);}
            };
            const setPointer = event => {
                const rect=mount.getBoundingClientRect();
                targetX=((event.clientX-rect.left)/rect.width-.5)*2;
                targetY=((event.clientY-rect.top)/rect.height-.5)*2;
            };
            mount.addEventListener('pointermove',setPointer,{passive:true});
            mount.addEventListener('pointerleave',()=>{targetX=0;targetY=0});
            new ResizeObserver(resize).observe(mount);
            new IntersectionObserver(([entry])=>{visible=entry.isIntersecting;if(visible&&!frame)frame=requestAnimationFrame(render)}).observe(mount);
            const reduced=matchMedia('(prefers-reduced-motion: reduce)').matches;
            const started=performance.now();
            function render(now){
                frame=0;
                if(!visible)return;
                resize();
                currentX+=(targetX-currentX)*.055; currentY+=(targetY-currentY)*.055;
                gl.uniform2f(uniforms.resolution,canvas.width,canvas.height);
                gl.uniform1f(uniforms.time,reduced?0:(now-started)/1000);
                gl.uniform2f(uniforms.pointer,currentX,currentY);
                gl.drawArrays(gl.TRIANGLES,0,3);
                frame=requestAnimationFrame(render);
            }
            frame=requestAnimationFrame(render);
        } catch (error) {
            console.warn('Electronics WebGL scene could not start.', error);
        }
    }
}
