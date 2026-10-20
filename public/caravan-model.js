// Original Ozman demonstration geometry. Units are metres; this is not a supplier's plan.
// All optional parts share the root transform, so changing them never moves the placed home.
export function buildCaravan(T, options = {}) {
    const root = new T.Group();
    root.name = 'ozman-caravan-demo';
    const parts = {};
    const materials = {
        metal: new T.MeshStandardMaterial({ color: 0x293b3c, roughness: .62, metalness: .35 }),
        trim: new T.MeshStandardMaterial({ color: 0x142223, roughness: .48, metalness: .5 }),
        wood: new T.MeshStandardMaterial({ color: 0xb78350, roughness: .85 }),
        woodLight: new T.MeshStandardMaterial({ color: 0xd2a674, roughness: .9 }),
        stone: new T.MeshStandardMaterial({ color: 0xdbd9cc, roughness: .9 }),
        glass: new T.MeshStandardMaterial({ color: 0x77acb1, roughness: .22, metalness: .4, transparent: true, opacity: .43, side: T.DoubleSide }),
        white: new T.MeshStandardMaterial({ color: 0xf4eee1, roughness: .75 }),
        fabric: new T.MeshStandardMaterial({ color: 0x8c9c85, roughness: 1 }),
        light: new T.MeshStandardMaterial({ color: 0xffe2a1, emissive: 0xffbd63, emissiveIntensity: .7 }),
    };
    const box = (parent, material, size, position) => {
        const mesh = new T.Mesh(new T.BoxGeometry(...size), materials[material]);
        mesh.position.set(...position);
        mesh.castShadow = material !== 'glass';
        mesh.receiveShadow = true;
        parent.add(mesh);
        return mesh;
    };
    const group = (name, position) => {
        const part = new T.Group(); part.name = name; part.position.set(...position);
        root.add(part); parts[name] = part; return part;
    };
    const legs = (p, width, depth) => {
        for (const x of [-width / 2 + .2, width / 2 - .2]) for (const z of [-depth / 2 + .2, depth / 2 - .2]) {
            box(p, 'trim', [.15, .28, .15], [x, .14, z]);
            box(p, 'stone', [.36, .08, .36], [x, .04, z]);
        }
    };
    const frontFrame = (p, x, y, z, w, h, material = 'glass') => {
        box(p, material, [w, h, .04], [x, y, z]);
        for (const dx of [-w / 2, w / 2]) box(p, 'trim', [.065, h + .06, .1], [x + dx, y, z]);
        for (const dy of [-h / 2, h / 2]) box(p, 'trim', [w, .065, .1], [x, y + dy, z]);
    };
    const body = group('body', [0, 0, 0]);
    legs(body, 6, 3);
    box(body, 'trim', [6.08, .2, 3.08], [0, .27, 0]);
    box(body, 'woodLight', [5.9, .08, 2.9], [0, .41, 0]);
    box(body, 'stone', [5.9, 2.42, .1], [0, 1.6, -1.44]);
    box(body, 'metal', [6, 2.5, .1], [0, 1.6, -1.5]);
    for (const x of [-2.95, 2.95]) box(body, 'metal', [.1, 2.5, 3], [x, 1.6, 0]);
    // Standing seams on the cladding.
    for (let x = -2.85; x < 3; x += .24) box(body, 'trim', [.015, 2.47, .025], [x, 1.6, -1.565]);
    for (const x of [-3.012, 3.012]) for (let z = -1.4; z < 1.5; z += .24) box(body, 'trim', [.022, 2.47, .014], [x, 1.6, z]);
    box(body, 'metal', [6.3, .17, 3.4], [0, 2.93, 0]);
    box(body, 'trim', [6.34, .07, 3.44], [0, 3.025, 0]);
    for (let x = -2.9; x <= 3; x += .5) box(body, 'trim', [.018, .025, 3.3], [x, 3.065, 0]);
    // Cedar entrance and two glazed bays; the interior is visible through real openings.
    box(body, 'wood', [1.32, 2.5, .13], [-.35, 1.6, 1.45]);
    for (let x = -.94; x < .31; x += .11) box(body, 'woodLight', [.028, 2.48, .04], [x, 1.6, 1.54]);
    frontFrame(body, -.35, 1.51, 1.55, .88, 2.22, 'trim');
    frontFrame(body, -.35, 1.75, 1.61, .7, 1.55);
    box(body, 'woodLight', [.035, .38, .07], [-.02, 1.25, 1.67]);
    frontFrame(body, -2, 1.57, 1.51, 1.68, 2.32);
    frontFrame(body, 1.65, 1.57, 1.51, 2.45, 2.32);
    box(body, 'trim', [.055, 2.32, .1], [1.65, 1.57, 1.52]);
    box(body, 'light', [.6, .035, .035], [-.35, 2.76, 1.62]);
    // Furniture gives the demo depth without downloaded textures or models.
    box(body, 'wood', [1.8, .25, 1.65], [1.9, .57, -.36]);
    box(body, 'white', [1.78, .23, 1.6], [1.9, .81, -.36]);
    box(body, 'fabric', [1.1, .06, 1.6], [2.19, .95, -.36]);
    box(body, 'white', [.36, .12, .6], [1.22, 1, -.72]);
    box(body, 'white', [.36, .12, .6], [1.22, 1, .03]);
    box(body, 'fabric', [1.5, .35, .7], [-1.85, .65, -.85]);
    box(body, 'fabric', [1.5, .62, .18], [-1.85, .97, -1.18]);
    box(body, 'woodLight', [1, .07, .6], [-1.85, .77, .15]);
    for (const x of [-2.22, -1.48]) box(body, 'trim', [.055, .36, .45], [x, .56, .15]);
    // Attached terrace and steps ground the building.
    box(body, 'wood', [6.3, .18, 1.05], [0, .28, 2.03]);
    for (let z = 1.57; z < 2.56; z += .12) box(body, 'woodLight', [6.27, .035, .085], [0, .39, z]);
    box(body, 'wood', [1.4, .2, .5], [-.35, .1, 2.78]);

    const bathroom = group('bathroom', [3.92, 0, -.5]);
    legs(bathroom, 1.8, 2);
    box(bathroom, 'trim', [1.85, .2, 2.05], [0, .27, 0]);
    box(bathroom, 'wood', [1.8, 2.42, 2], [0, 1.56, 0]);
    for (let x = -.84; x < .9; x += .12) box(bathroom, 'woodLight', [.027, 2.38, .025], [x, 1.56, 1.015]);
    box(bathroom, 'metal', [2, .18, 2.2], [0, 2.83, 0]);
    frontFrame(bathroom, -.15, 1.46, 1.06, .84, 2.14, 'stone');
    box(bathroom, 'trim', [.2, .045, .06], [.13, 1.48, 1.14]);
    // Frosted side window and extractor.
    box(bathroom, 'trim', [.06, .57, .88], [.93, 2.14, -.1]);
    box(bathroom, 'glass', [.07, .47, .78], [.94, 2.14, -.1]);
    box(bathroom, 'trim', [.32, .16, .32], [.35, 3, -.4]);
    box(bathroom, 'wood', [1.1, .2, .38], [-.15, .1, 1.25]);

    const kitchen = group('kitchen', [-4.32, 0, 0]);
    box(kitchen, 'wood', [2.55, .18, 3.1], [0, .28, 0]);
    for (let z = -1.45; z < 1.55; z += .14) box(kitchen, 'woodLight', [2.5, .035, .1], [0, .39, z]);
    for (const x of [-1.18, 1.18]) for (const z of [-1.4, 1.4]) box(kitchen, 'trim', [.08, 2.5, .08], [x, 1.63, z]);
    box(kitchen, 'trim', [2.65, .1, 3.2], [0, 2.91, 0]);
    for (let z = -1.5; z < 1.6; z += .22) box(kitchen, 'woodLight', [2.6, .07, .08], [0, 3, z]);
    box(kitchen, 'metal', [2.12, .77, .66], [0, .79, -1.05]);
    box(kitchen, 'stone', [2.24, .065, .78], [0, 1.21, -1.05]);
    for (const x of [-.7, 0, .7]) {
        box(kitchen, 'wood', [.65, .66, .04], [x, .8, -.698]);
        box(kitchen, 'trim', [.23, .025, .05], [x, 1.02, -.66]);
    }
    box(kitchen, 'trim', [.48, .014, .43], [.65, 1.25, -1.02]);
    box(kitchen, 'stone', [.38, .017, .32], [.65, 1.255, -1.02]);
    box(kitchen, 'trim', [.03, .27, .03], [.65, 1.39, -1.29]);
    box(kitchen, 'trim', [.03, .035, .18], [.65, 1.51, -1.22]);
    box(kitchen, 'trim', [.65, .018, .46], [-.58, 1.25, -1.03]);
    for (const x of [-.76, -.42]) {
        const ring = new T.Mesh(new T.TorusGeometry(.095, .012, 6, 20), materials.stone);
        ring.rotation.x = Math.PI / 2; ring.position.set(x, 1.27, -1.03); kitchen.add(ring);
    }
    const setOptions = ({ bathroom: bath = true, kitchen: cook = false } = {}) => {
        bathroom.visible = Boolean(bath); kitchen.visible = Boolean(cook);
    };
    setOptions(options);
    return { root, parts, setOptions, dimensions: { length: 6, width: 3, height: 3.08 } };
}

export function disposeTree(root) {
    const geometries = new Set(), materials = new Set();
    root.traverse(node => {
        if (node.geometry) geometries.add(node.geometry);
        for (const material of Array.isArray(node.material) ? node.material : [node.material]) if (material) materials.add(material);
    });
    geometries.forEach(geometry => geometry.dispose());
    const textures = new Set();
    materials.forEach(material => {
        for (const value of Object.values(material)) if (value?.isTexture) textures.add(value);
        material.dispose();
    });
    textures.forEach(texture => texture.dispose());
}
