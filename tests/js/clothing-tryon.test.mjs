import test from 'node:test';
import assert from 'node:assert/strict';
import { garmentPlacement, smoothPlacement } from '../../public/clothing-tryon.js';

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
