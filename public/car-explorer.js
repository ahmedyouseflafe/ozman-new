import { CaravanDemo } from './caravan-demo.js?v=car-model-factory-1';
import { loadRealCar, buildRealCar } from './car-real-model.js';

// Reuse the existing camera-permission, placement and cleanup lifecycle.
class CarExplorer extends CaravanDemo {
    constructor(element) {
        super(element, { buildModel: () => { throw new Error('Car asset has not loaded'); } });
        this.options = {};
        this.preset = 'outside';
        this.view = {theta:.70, phi:1.12, radius:16};
        this.arScale = .3;
        this.element.querySelectorAll('[data-car-view]').forEach(button => button.addEventListener('click', () => this.selectView(button.dataset.carView)));
        this.element.querySelector('[data-car-reset]').addEventListener('click', () => this.selectView('outside'));
    }
    syncOptions() {
        this.render();
    }
    setMode(mode) {
        super.setMode(mode);
        this.element.querySelectorAll('[data-car-view]').forEach(button=>button.disabled=mode!=='preview');
        this.element.querySelector('[data-car-reset]').disabled=mode!=='preview';
    }
    async startPreview(message=null) {
        const token=++this.token;
        this.teardown();this.setMode('loading');this.say('loading');
        try {
            const asset=await loadRealCar(this.element.dataset.modelUrl);
            if(token!==this.token || !this.dialog.open)return;
            this.asset=asset;
            this.buildModel=T=>buildRealCar(T,asset.source);
        } catch(error) {
            if(token!==this.token || !this.dialog.open)return;
            this.setMode('error');this.say('loadError');return;
        }
        await super.startPreview(message);
        if(this.mode!=='preview')return;
        this.scene.background.set(0xe5eceb);
        for(const node of [...this.scene.children]){
            if(node.type==='GridHelper'){this.scene.remove(node);node.geometry.dispose();node.material.dispose();}
            if(node.isMesh && node.geometry.type==='PlaneGeometry')node.material.color.set(0xe5eceb);
        }
        this.inspectionLight=new this.THREE.PointLight(0xe7f4ff,2.4,3.5,2);
        this.inspectionLight.position.set(0,1.25,-.15);
        this.scene.add(this.inspectionLight);
        this.camera.near=.015;this.camera.updateProjectionMatrix();
        this.render();
    }
    addLights(scene) {
        super.addLights(scene);
        if(!this.asset || !this.renderer?.isWebGLRenderer)return;
        const generator=new this.THREE.PMREMGenerator(this.renderer);
        const room=new this.asset.RoomEnvironment();
        this.environment=generator.fromScene(room,.04);
        scene.environment=this.environment.texture;
        room.dispose();generator.dispose();
        this.renderer.toneMappingExposure=.85;
    }
    selectView(preset) {
        if(this.mode!=='preview')return;
        this.preset=preset;this.rotation=0;if(this.model)this.model.root.rotation.y=0;
        const views={
            outside:{theta:.70,phi:1.12,radius:16},
            inside:{theta:0,phi:1.35,radius:16},
            rear:{theta:Math.PI+.65,phi:1.15,radius:16},
            dashboard:{theta:0,phi:1.4,radius:16},
        };
        this.view={...views[preset]};
        this.element.querySelectorAll('[data-car-view]').forEach(button=>button.setAttribute('aria-pressed',String(button.dataset.carView===preset)));
        this.say(['inside','dashboard'].includes(preset)?'insideHint':'orbit');
        this.syncOptions();
    }
    render() {
        if(this.mode!=='preview'||!this.renderer)return;
        const T=this.THREE, {theta,phi,radius}=this.view;
        const inside=['inside','dashboard'].includes(this.preset);
        if(this.inspectionLight)this.inspectionLight.visible=inside;
        if(inside){
            const passenger=this.preset==='dashboard';
            // Interior orbit turns the viewer's head, not the car around the viewer.
            this.model.root.rotation.y=0;
            const yaw=theta+this.rotation;
            this.camera.fov=Math.max(45,Math.min(90,70+(radius-16)*2));
            const position=new T.Vector3(passenger ? 0 : .42,1.10,passenger ? -.45 : -.35);
            this.camera.position.copy(position);
            this.camera.lookAt(position.add(new T.Vector3(Math.sin(yaw)*Math.cos(phi-1.14),-Math.sin(phi-1.14),Math.cos(yaw)*Math.cos(phi-1.14))));
        } else {
            this.camera.fov=42;
            const target=new T.Vector3(0,.72,0);
            const distance=radius*.43*Math.max(1,1.12/(this.camera.aspect||1));
            this.camera.position.set(target.x+distance*Math.sin(phi)*Math.sin(theta),target.y+distance*Math.cos(phi),target.z+distance*Math.sin(phi)*Math.cos(theta));
            this.camera.lookAt(target);
        }
        this.camera.updateProjectionMatrix();
        this.renderer.render(this.scene,this.camera);
    }
    teardown() {
        super.teardown();
        this.environment?.dispose();this.environment=null;
        this.inspectionLight=null;
    }
}
document.querySelectorAll('[data-car-explorer]').forEach(element=>new CarExplorer(element));
