import test from 'node:test';
import assert from 'node:assert/strict';
import { garmentPlacement, smoothPlacement, garmentMesh, foregroundArms, smoothLandmarks } from '../../public/clothing-tryon.js';

const pose = () => {
    const points = Array.from({ length: 33 }, () => ({ x: 0, y: 0, visibility: 0 }));
    points[11] = { x: 0.65, y: 0.3, visibility: 0.99 };
    points[12] = { x: 0.35, y: 0.3, visibility: 0.99 };
    points[23] = { x: 0.6, y: 0.65, visibility: 0.99 };
    points[24] = { x: 0.4, y: 0.65, visibility: 0.99 };
    return points;
};
test('flat MediaPipe landmarks place an upright shirt on the shoulder midpoint', () => {
    const fit = garmentPlacement(pose(), 720, 960, 1);
    assert.ok(fit);
    assert.equal(fit.x, 360);
    assert.equal(fit.y, 288);
    assert.equal(fit.angle, 0); // Anatomical left/right must not rotate it 180 degrees.
    assert.ok(fit.width > 216);
});
test('shirt follows translation and distance, and tilts with the shoulder line', () => {
    const points = pose();
    const original = garmentPlacement(points, 720, 960, 1);
    for (const p of points) { p.x = 0.5 + (p.x - 0.5) * 1.2 + 0.1; p.y += 0.05; }
    const moved = garmentPlacement(points, 720, 960, 1);
    assert.ok(Math.abs(moved.x - original.x - 72) < 0.001);
    assert.ok(Math.abs(moved.y - original.y - 48) < 0.001);
    assert.ok(Math.abs(moved.width / original.width - 1.2) < 0.001);
    points[11].y += 0.06;
    assert.ok(garmentPlacement(points, 720, 960, 1).angle > 0);
});
test('missing or low confidence shoulders hide the shirt instead of showing a fixed fallback', () => {
    assert.equal(garmentPlacement(undefined, 720, 960, 1), null);
    const points = pose();
    points[11].visibility = 0.1;
    assert.equal(garmentPlacement(points, 720, 960, 1), null);
    points[11].visibility = 0.99;
    points[11].x = NaN;
    assert.equal(garmentPlacement(points, 720, 960, 1), null);
});
test('hips adapt torso length; cropped hips retain a usable shoulder-based fit', () => {
    const points = pose();
    const short = garmentPlacement(points, 720, 960, 1);
    points[23].y += 0.1; points[24].y += 0.1;
    assert.ok(garmentPlacement(points, 720, 960, 1).height > short.height);
    points[23].visibility = 0;
    assert.ok(garmentPlacement(points, 720, 960, 1));
});
test('smoothing damps jitter without reversing movement', () => {
    const start = garmentPlacement(pose(), 720, 960, 1);
    const next = { ...start, x: start.x + 100, width: start.width + 50 };
    const smoothed = smoothPlacement(start, next);
    assert.ok(smoothed.x > start.x && smoothed.x < next.x);
    assert.ok(smoothed.width > start.width && smoothed.width < next.width);
    assert.deepEqual(smoothPlacement(null, next), next);
});

const armedPose = () => {
    const points = pose();
    for (const point of points) point.z = 0;
    points[13] = { x: 0.74, y: 0.52, z: -0.1, visibility: 0.99 };
    points[14] = { x: 0.26, y: 0.52, z: -0.1, visibility: 0.99 };
    points[15] = { x: 0.76, y: 0.73, z: -0.2, visibility: 0.99 };
    points[16] = { x: 0.24, y: 0.73, z: -0.2, visibility: 0.99 };
    return points;
};
const meshFor = points => garmentMesh(points, garmentPlacement(points, 720, 960, 1), 720, 960);
const vertex = (mesh, u, v) => mesh.vertices.find(p => p.u === u && p.v === v);

test('raising one elbow lifts its sleeve without moving the other sleeve or torso', () => {
    const points = armedPose();
    const resting = meshFor(points);
    points[13] = { ...points[13], x: 0.9, y: 0.13 };
    const raised = meshFor(points);
    assert.ok(vertex(raised, 0.88, 0.48).y < vertex(resting, 0.88, 0.48).y - 80);
    assert.deepEqual(vertex(raised, 0.12, 0.48), vertex(resting, 0.12, 0.48));
    assert.deepEqual(vertex(raised, 0.5, 0.48), vertex(resting, 0.5, 0.48));
    assert.deepEqual(vertex(raised, 0.76, 0.82), vertex(resting, 0.76, 0.82));
});
test('both sleeves follow opposite arm movements, including an arm across the chest', () => {
    const points = armedPose();
    const before = meshFor(points);
    points[13] = { ...points[13], x: 0.46, y: 0.4 };
    points[14] = { ...points[14], x: 0.1, y: 0.1 };
    const after = meshFor(points);
    assert.notDeepEqual(vertex(after, 0.88, 0.48), vertex(before, 0.88, 0.48));
    assert.notDeepEqual(vertex(after, 0.12, 0.48), vertex(before, 0.12, 0.48));
    assert.ok(after.vertices.every(p => Number.isFinite(p.x) && Number.isFinite(p.y)));
    assert.equal(after.triangles.length, 96);
});
test('lost elbow confidence reverts that sleeve and never keeps a stale arm pose', () => {
    const points = armedPose();
    const hidden = armedPose(); hidden[13].visibility = 0;
    const smoothed = smoothLandmarks(points, hidden);
    assert.equal(smoothed[13].visibility, 0);
    const noArms = pose();
    assert.deepEqual(vertex(meshFor(smoothed), 0.88, 0.48), vertex(meshFor(noArms), 0.88, 0.48));
});
test('foreground forearms follow wrists while arms behind the torso remain covered', () => {
    const points = armedPose();
    const front = foregroundArms(points, 720, 960);
    assert.equal(front.length, 2);
    assert.equal(front[0].to.x, points[15].x * 720);
    points[13].z = points[15].z = 0.3;
    assert.equal(foregroundArms(points, 720, 960).length, 1);
    points[14].visibility = 0;
    assert.equal(foregroundArms(points, 720, 960).length, 0);
});
test('a forearm crossing the torso depth plane only restores its visible section', () => {
    const points = armedPose();
    points[13].z = 0.3;
    points[15].z = -0.3;
    const arm = foregroundArms(points, 720, 960)[0];
    assert.ok(arm.from.y > points[13].y * 960);
    assert.ok(arm.from.y < points[15].y * 960);
    assert.equal(arm.to.y, points[15].y * 960);
});
