import * as THREE from 'https://cdn.jsdelivr.net/npm/three@0.180.0/build/three.module.js';
import { RoundedBoxGeometry } from 'https://cdn.jsdelivr.net/npm/three@0.180.0/examples/jsm/geometries/RoundedBoxGeometry.js';

const mount = document.getElementById('electronicsWebgl');

if (mount && window.WebGLRenderingContext) {
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(34, 1, 0.1, 100);
    camera.position.set(0, 0.1, 8.4);
    const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true, powerPreference: 'high-performance' });
    renderer.setPixelRatio(Math.min(devicePixelRatio, 2));
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.25;
    mount.appendChild(renderer.domElement);
    mount.querySelector('.webgl-fallback')?.remove();

    scene.add(new THREE.HemisphereLight(0xbfefff, 0x080410, 2.2));
    const cyan = new THREE.PointLight(0x20dcff, 55, 18, 2);
    cyan.position.set(4, 4, 5);
    scene.add(cyan);
    const violet = new THREE.PointLight(0x7956ff, 48, 16, 2);
    violet.position.set(-4, -2, 3);
    scene.add(violet);

    const phone = new THREE.Group();
    scene.add(phone);
    phone.add(new THREE.Mesh(
        new RoundedBoxGeometry(3.05, 5.9, 0.42, 8, 0.27),
        new THREE.MeshPhysicalMaterial({ color: 0x101722, metalness: 0.88, roughness: 0.2, clearcoat: 1 })
    ));
    const screen = new THREE.Mesh(
        new RoundedBoxGeometry(2.82, 5.62, 0.055, 8, 0.22),
        new THREE.MeshPhysicalMaterial({ color: 0x06101b, emissive: 0x071f38, emissiveIntensity: 1.2, roughness: 0.08, clearcoat: 1 })
    );
    screen.position.z = 0.235;
    phone.add(screen);

    const additive = (color, opacity) => new THREE.MeshBasicMaterial({ color, transparent: true, opacity, blending: THREE.AdditiveBlending });
    const glow = new THREE.Mesh(new THREE.TorusGeometry(0.78, 0.022, 12, 100), additive(0x21dcff, 0.72));
    glow.position.set(0.25, 0.55, 0.08);
    screen.add(glow);
    const glow2 = new THREE.Mesh(new THREE.TorusGeometry(1.1, 0.012, 10, 100), additive(0x7956ff, 0.52));
    glow2.position.set(-0.2, -0.55, 0.09);
    screen.add(glow2);
    const island = new THREE.Mesh(new RoundedBoxGeometry(0.82, 0.18, 0.035, 5, 0.08), new THREE.MeshStandardMaterial({ color: 0x010204 }));
    island.position.set(0, 2.45, 0.29);
    phone.add(island);

    const lensMaterial = new THREE.MeshPhysicalMaterial({ color: 0x03070b, metalness: 0.7, roughness: 0.08, clearcoat: 1 });
    [[-1.03, 2.15], [-1.03, 1.48], [-0.38, 2.15]].forEach(([x, y]) => {
        const lens = new THREE.Mesh(new THREE.CylinderGeometry(0.24, 0.24, 0.1, 48), lensMaterial);
        lens.rotation.x = Math.PI / 2;
        lens.position.set(x, y, -0.28);
        phone.add(lens);
        const glass = new THREE.Mesh(new THREE.CircleGeometry(0.15, 40), new THREE.MeshPhysicalMaterial({ color: 0x123c5d, emissive: 0x071a2b, roughness: 0.05, clearcoat: 1 }));
        glass.position.set(x, y, -0.345);
        glass.rotation.y = Math.PI;
        phone.add(glass);
    });

    const ring = new THREE.Mesh(new THREE.TorusGeometry(3.35, 0.012, 8, 180), additive(0x21dcff, 0.28));
    ring.rotation.x = 1.1;
    scene.add(ring);
    const ringB = new THREE.Mesh(new THREE.TorusGeometry(2.5, 0.012, 8, 160), additive(0x7956ff, 0.3));
    ringB.rotation.set(0.4, 0.8, 0.2);
    scene.add(ringB);

    const particleGeometry = new THREE.BufferGeometry();
    const positions = new Float32Array(240 * 3);
    for (let i = 0; i < positions.length; i += 3) {
        const radius = 3.4 + Math.random() * 2.2;
        const angle = Math.random() * Math.PI * 2;
        positions[i] = Math.cos(angle) * radius;
        positions[i + 1] = (Math.random() - 0.5) * 7;
        positions[i + 2] = Math.sin(angle) * radius - 1;
    }
    particleGeometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    const particles = new THREE.Points(particleGeometry, new THREE.PointsMaterial({ color: 0x45dfff, size: 0.025, transparent: true, opacity: 0.68 }));
    scene.add(particles);

    phone.rotation.set(0.08, -0.5, -0.04);
    let targetX = 0.08;
    let targetY = -0.5;
    let dragging = false;
    let lastX = 0;
    mount.addEventListener('pointermove', (event) => {
        const rect = mount.getBoundingClientRect();
        if (dragging) {
            targetY += (event.clientX - lastX) * 0.008;
            lastX = event.clientX;
        } else {
            targetY = ((event.clientX - rect.left) / rect.width - 0.5) * 0.9;
            targetX = ((event.clientY - rect.top) / rect.height - 0.5) * 0.35;
        }
    });
    mount.addEventListener('pointerdown', (event) => { dragging = true; lastX = event.clientX; renderer.domElement.setPointerCapture(event.pointerId); });
    mount.addEventListener('pointerup', () => { dragging = false; });
    mount.addEventListener('pointerleave', () => { dragging = false; targetX = 0.08; targetY = -0.5; });

    const resize = () => {
        const width = Math.max(1, mount.clientWidth);
        const height = Math.max(1, mount.clientHeight);
        renderer.setSize(width, height, false);
        camera.aspect = width / height;
        camera.updateProjectionMatrix();
    };
    new ResizeObserver(resize).observe(mount);
    resize();

    const clock = new THREE.Clock();
    const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;
    let visible = true;
    new IntersectionObserver(([entry]) => { visible = entry.isIntersecting; }).observe(mount);
    const render = () => {
        requestAnimationFrame(render);
        if (!visible) return;
        const time = clock.getElapsedTime();
        phone.rotation.x += (targetX - phone.rotation.x) * 0.06;
        phone.rotation.y += (targetY - phone.rotation.y) * 0.06;
        if (!reducedMotion) {
            phone.position.y = Math.sin(time * 1.15) * 0.13;
            glow.rotation.z = time * 0.35;
            glow2.rotation.z = -time * 0.24;
            ring.rotation.z = time * 0.12;
            ringB.rotation.y = time * 0.16;
            particles.rotation.y = time * 0.025;
        }
        renderer.render(scene, camera);
    };
    render();
}
