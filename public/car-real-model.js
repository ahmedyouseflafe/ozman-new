// BMW M4 Competition M Package by SRT Performance, CC BY 4.0.
// See public/models/car-explorer/ATTRIBUTION.md. Original parts are not articulated.
let assetPromise;
export async function loadRealCar(url) {
    if (!assetPromise) assetPromise = (async () => {
        const [T, {GLTFLoader}, {MeshoptDecoder}, {RoomEnvironment}] = await Promise.all([
            import('three'),
            import('three/addons/loaders/GLTFLoader.js'),
            import('three/addons/libs/meshopt_decoder.module.js'),
            import('three/addons/environments/RoomEnvironment.js'),
        ]);
        const loader = new GLTFLoader();
        loader.setMeshoptDecoder(MeshoptDecoder);
        const gltf = await loader.loadAsync(url);
        return {T, source:gltf.scene, RoomEnvironment};
    })().catch(error => {assetPromise = null; throw error;});
    return assetPromise;
}

export function buildRealCar(T, source) {
    const root = new T.Group();
    root.name = 'BMW M4 Competition M Package';
    const model = source.clone(true);
    const geometries=new Map(), materials=new Map(), textures=new Map();
    // Each viewer owns its GPU resources. Closing must not invalidate the cached source.
    const copyMaterial = original => {
        if(materials.has(original))return materials.get(original);
        const copy=original.clone();
        for(const [key,value] of Object.entries(original)){
            if(!value?.isTexture)continue;
            if(!textures.has(value))textures.set(value,value.clone());
            copy[key]=textures.get(value);copy[key].needsUpdate=true;
        }
        materials.set(original,copy);return copy;
    };
    model.traverse(node => {
        if(!node.isMesh)return;
        if(!geometries.has(node.geometry))geometries.set(node.geometry,node.geometry.clone());
        node.geometry=geometries.get(node.geometry);
        node.material=Array.isArray(node.material)?node.material.map(copyMaterial):copyMaterial(node.material);
        node.castShadow=true;node.receiveShadow=true;
    });
    root.add(model);
    root.updateMatrixWorld(true);
    const bounds=new T.Box3().setFromObject(model), size=bounds.getSize(new T.Vector3());
    // This reference asset is normalised to a nominal 4.8 m length, not a measurement.
    const scale=4.8/Math.max(size.x,size.z);
    model.scale.multiplyScalar(scale);
    root.updateMatrixWorld(true);
    const scaled=new T.Box3().setFromObject(model), centre=scaled.getCenter(new T.Vector3());
    model.position.sub(new T.Vector3(centre.x,scaled.min.y,centre.z));
    root.updateMatrixWorld(true);
    return {root,parts:{},setOptions(){},update(){return false;},dimensions:{length:4.8}};
}
