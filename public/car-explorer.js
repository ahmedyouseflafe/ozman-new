import { CaravanDemo } from './caravan-demo.js?v=car-model-factory-1';
import { buildDemoCar } from './car-explorer-model.js';

// Reuse the existing camera-permission, placement and cleanup lifecycle.
class CarExplorer extends CaravanDemo {
    constructor(element) {
        super(element, { buildModel: buildDemoCar });
        this.options = { driverDoor:false, passengerDoor:false, hood:false, trunk:false, glovebox:false };
        this.preset = 'outside';
        this.view = {theta:.70, phi:1.12, radius:16};
        this.arScale = .3;
        this.element.querySelectorAll('[data-car-view]').forEach(button => button.addEventListener('click', () => this.selectView(button.dataset.carView)));
        this.element.querySelector('[data-car-reset]').addEventListener('click', () => {
            Object.keys(this.options).forEach(key => this.options[key] = false);
            this.selectView('outside'); this.syncOptions();
        });
        this.syncOptions();
    }
    syncOptions() {
        this.model?.setOptions(this.options);
        let count=0;
        this.element.querySelectorAll('[data-part]').forEach(button => {
            const opened=Boolean(this.options[button.dataset.part]); if(opened)count++;
            button.setAttribute('aria-pressed',String(opened));
            button.querySelector('[data-part-label]').textContent=opened?'مفتوح · اضغط للإغلاق':'مغلق · اضغط للفتح';
            button.querySelector('b').textContent=opened?'−':'+';
        });
        this.element.querySelector('[data-caravan-summary]').textContent=count?`${count} أجزاء مفتوحة`:'كل الأجزاء مغلقة';
        this.animateParts();
    }
    animateParts() {
        cancelAnimationFrame(this.partFrame);
        let previous=performance.now();
        const tick=now=>{
            if(!this.model || !['preview','ar'].includes(this.mode))return;
            const moving=this.model.update(Math.min((now-previous)/1000,.05));previous=now;
            this.render();
            if(moving)this.partFrame=requestAnimationFrame(tick);
        };
        if(matchMedia('(prefers-reduced-motion: reduce)').matches){
            this.model?.update(1);this.render();
        } else this.partFrame=requestAnimationFrame(tick);
    }
    setMode(mode) {
        super.setMode(mode);
        this.element.querySelectorAll('[data-car-view]').forEach(button=>button.disabled=mode!=='preview');
        this.element.querySelectorAll('[data-part],[data-car-reset]').forEach(button=>button.disabled=!['preview','ar'].includes(mode));
    }
    async startPreview(message=null) {
        await super.startPreview(message);
        if(this.mode!=='preview')return;
        this.scene.background.set(0xe5eceb);
        this.camera.near=.015;this.camera.updateProjectionMatrix();
        this.render();
    }
    selectView(preset) {
        if(this.mode!=='preview')return;
        this.preset=preset;this.rotation=0;if(this.model)this.model.root.rotation.y=0;
        const views={
            outside:{theta:.70,phi:1.12,radius:16},
            inside:{theta:0,phi:1.14,radius:16},
            engine:{theta:.35,phi:.65,radius:7},
            luggage:{theta:Math.PI+.35,phi:.8,radius:8},
            dashboard:{theta:0,phi:1.3,radius:16},
        };
        this.view={...views[preset]};
        if(preset==='engine')this.options.hood=true;
        if(preset==='luggage')this.options.trunk=true;
        if(preset==='dashboard')this.options.glovebox=true;
        this.element.querySelectorAll('[data-car-view]').forEach(button=>button.setAttribute('aria-pressed',String(button.dataset.carView===preset)));
        this.say(['inside','dashboard'].includes(preset)?'insideHint':'orbit');
        this.syncOptions();
    }
    render() {
        if(this.mode!=='preview'||!this.renderer)return;
        const T=this.THREE, {theta,phi,radius}=this.view;
        const inside=['inside','dashboard'].includes(this.preset);
        if(inside){
            const passenger=this.preset==='dashboard';
            // Interior orbit turns the viewer's head, not the car around the viewer.
            this.model.root.rotation.y=0;
            const yaw=theta+this.rotation;
            this.camera.fov=Math.max(45,Math.min(90,70+(radius-16)*2));
            const position=new T.Vector3(passenger ? .43 : -.46,passenger ? 1.12 : 1.22,passenger ? -.12 : -.26);
            this.camera.position.copy(position);
            this.camera.lookAt(position.add(new T.Vector3(Math.sin(yaw)*Math.cos(phi-1.14),-Math.sin(phi-1.14),Math.cos(yaw)*Math.cos(phi-1.14))));
        } else {
            this.camera.fov=42;
            const target=new T.Vector3(0,.72,0);
            if(this.preset==='engine')target.set(0,.78,1.35);
            if(this.preset==='luggage')target.set(0,.72,-1.65);
            const distance=radius*.43*Math.max(1,1.12/(this.camera.aspect||1));
            this.camera.position.set(target.x+distance*Math.sin(phi)*Math.sin(theta),target.y+distance*Math.cos(phi),target.z+distance*Math.sin(phi)*Math.cos(theta));
            this.camera.lookAt(target);
        }
        this.camera.updateProjectionMatrix();
        this.renderer.render(this.scene,this.camera);
    }
    teardown() {
        cancelAnimationFrame(this.partFrame);
        super.teardown();
    }
}
document.querySelectorAll('[data-car-explorer]').forEach(element=>new CarExplorer(element));
