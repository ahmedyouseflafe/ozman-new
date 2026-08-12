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
            float cylinderZ(vec3 p,float r,float h){vec2 d=abs(vec2(length(p.xy),p.z))-vec2(r,h);return min(max(d.x,d.y),0.)+length(max(d,0.));}
            float hash(float n){return fract(sin(n*91.3458)*47453.5453);}
            vec2 phoneEdge(float t){
                float side=floor(t*4.); float q=fract(t*4.);
                if(side<1.) return vec2(mix(-.92,.92,q),1.83);
                if(side<2.) return vec2(.92,mix(1.83,-1.83,q));
                if(side<3.) return vec2(mix(.92,-.92,q),-1.83);
                return vec2(-.92,mix(-1.83,1.83,q));
            }

            vec2 mapScene(vec3 p) {
                p.y += sin(time*.8)*.07;
                p.xz *= rot(-.48-pointer.x*.42-sin(time*.34)*1.02);
                p.yz *= rot(.08+pointer.y*.16);
                float body=roundedBox(p,vec3(1.10,2.2,.205),.22);
                float screen=roundedBox(p-vec3(0.,0.,.220),vec3(1.005,2.085,.018),.16);
                float island=roundedBox(p-vec3(0.,1.83,.225),vec3(.30,.065,.018),.055);
                float rail=roundedBox(p-vec3(-1.115,.55,0.),vec3(.018,.32,.10),.014);
                float button=roundedBox(p-vec3(1.115,.42,0.),vec3(.018,.45,.10),.014);
                float backGlass=roundedBox(p-vec3(0.,-.38,-.222),vec3(.94,1.18,.018),.17);
                float plateau=roundedBox(p-vec3(0.,1.43,-.265),vec3(1.04,.60,.095),.18);
                float lensA=cylinderZ(p-vec3(-.67,1.70,-.405),.285,.105);
                float lensB=cylinderZ(p-vec3(-.67,1.13,-.405),.285,.105);
                float lensC=cylinderZ(p-vec3(-.08,1.415,-.405),.285,.105);
                float lenses=min(lensA,min(lensB,lensC));
                float d=body; float id=1.;
                if(screen<d){d=screen;id=2.;}
                if(island<d){d=island;id=3.;}
                if(min(rail,button)<d){d=min(rail,button);id=4.;}
                if(backGlass<d){d=backGlass;id=5.;}
                if(plateau<d){d=plateau;id=6.;}
                if(lenses<d){d=lenses;id=7.;}
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

                // Every cycle starts as a cloud of energy, then assembles into the phone.
                float cycle=min(time,4.2);
                float assembled=smoothstep(1.2,3.5,cycle);
                float reveal=smoothstep(.42,1.,assembled);
                for(int i=0;i<72;i++){
                    float fi=float(i), seed=hash(fi+4.);
                    float angle=seed*PI*2.+time*(.25+hash(fi)*.35);
                    vec2 cloud=vec2(cos(angle)*(1.55+hash(fi+8.)*1.4),sin(angle)*(1.+hash(fi+19.)*1.45));
                    cloud+=vec2(sin(time*.8+fi)*.18,cos(time*.55+fi*.7)*.12);
                    vec2 target=phoneEdge(fract(seed+fi*.618));
                    vec2 particle=mix(cloud,target,smoothstep(.08,.94,assembled));
                    float spark=.0045/(length(uv-particle)+.003);
                    float twinkle=.55+.45*sin(time*4.+fi*1.7);
                    color+=mix(vec3(1.,.22,.025),vec3(1.,.68,.18),hash(fi+2.))*spark*twinkle*(1.-reveal*.68);
                }

                vec3 ro=vec3(0.,0.,7.2), rd=normalize(vec3(uv,-2.35));
                float travel=0.; vec2 hit=vec2(0.); vec3 p;
                for(int i=0;i<100;i++){
                    p=ro+rd*travel; hit=mapScene(p);
                    if(hit.x<.001||travel>12.) break;
                    travel+=hit.x*.72;
                }

                if(travel<12. && reveal>.01) {
                    vec3 n=normalAt(p);
                    vec3 light=normalize(vec3(-3.,4.,5.));
                    float diff=max(dot(n,light),0.);
                    float rim=pow(1.-max(dot(n,-rd),0.),2.8);
                    if(hit.y<1.5) {
                        float brushed=.018*sin(p.y*170.)+.012*sin(p.x*220.);
                        color += (vec3(.52,.12,.025)+brushed)*(diff+.42)+vec3(1.,.42,.08)*rim*.92;
                        color += pow(max(dot(reflect(rd,n),light),0.),42.)*vec3(1.,.72,.4)*1.5;
                    }
                    else if(hit.y<2.5) {
                        vec2 screenUv=p.xy;
                        float aurora=.5+.5*sin(screenUv.y*2.3+sin(screenUv.x*3.+time*.4));
                        vec3 screenColor=mix(vec3(.003,.002,.001),vec3(.24,.035,.004),aurora);
                        screenColor+=vec3(1.,.22,.025)*softGlow(abs(length(screenUv-vec2(.3,.45))-.62),.016)*.48;
                        screenColor+=vec3(1.,.62,.12)*softGlow(abs(length(screenUv+vec2(.2,.55))-.82),.012)*.34;
                        float glass=pow(max(dot(reflect(rd,n),light),0.),22.);
                        float edgeShade=smoothstep(1.0,.72,abs(screenUv.x))*smoothstep(2.05,1.7,abs(screenUv.y));
                        color += (screenColor*(.72+diff*.35)*(.78+.22*edgeShade)+glass*vec3(1.,.65,.32))*reveal+vec3(1.,.34,.06)*rim*.52;
                    } else if(hit.y<3.5) color += vec3(.002,.003,.005)+vec3(.08,.32,.48)*rim;
                    else if(hit.y<4.5) color += vec3(.55,.14,.035)*(diff+.30)+vec3(1.,.5,.12)*rim;
                    else if(hit.y<5.5) {
                        color += vec3(.72,.18,.045)*(.62+diff*.68)+vec3(1.,.48,.12)*rim*.78;
                        color += pow(max(dot(reflect(rd,n),light),0.),30.)*vec3(1.,.62,.3);
                    } else if(hit.y<6.5) {
                        color += vec3(.66,.145,.03)*(.58+diff*.82)+vec3(1.,.48,.1)*rim;
                    } else {
                        float lensGlow=pow(max(dot(reflect(rd,n),light),0.),18.);
                        color += vec3(.001,.001,.001)+vec3(.025,.045,.065)*diff+vec3(.35,.62,.82)*lensGlow;
                        color += vec3(.12,.24,.34)*rim*.8;
                    }
                    color*=reveal;
                }

                color=1.-exp(-color*1.28);
                float alpha=smoothstep(.055,.13,max(max(color.r,color.g),color.b));
                fragColor=vec4(color,alpha);
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
            gl.clearColor(0, 0, 0, 0);

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
            const started=performance.now();
            function render(now){
                frame=0;
                if(!visible)return;
                resize();
                currentX+=(targetX-currentX)*.055; currentY+=(targetY-currentY)*.055;
                gl.uniform2f(uniforms.resolution,canvas.width,canvas.height);
                gl.uniform1f(uniforms.time,(now-started)/1000);
                gl.uniform2f(uniforms.pointer,currentX,currentY);
                gl.clear(gl.COLOR_BUFFER_BIT);
                gl.drawArrays(gl.TRIANGLES,0,3);
                frame=requestAnimationFrame(render);
            }
            frame=requestAnimationFrame(render);
        } catch (error) {
            console.warn('Electronics WebGL scene could not start.', error);
        }
    }
}
