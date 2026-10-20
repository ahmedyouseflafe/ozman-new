# Car marketplace design preview

`/cars` is the visual prototype requested before implementing seller registration and real listings.
It uses six explicitly labelled examples in `CarMarketPreviewController`, never production listings or market pricing.
The page is marked `noindex, nofollow`. A “سوق السيارات” entry in the homepage shop-section carousel links directly to `/cars`; it is navigation only and does not add a fictional shop to the store data.

Implemented preview interactions:
- Search by name, brand, city, or year; new/used, body, city, brand and maximum-price filters.
- Featured, price and year sorting, empty state, and reset.
- Favorite examples stored in the visitor's browser under a separate preview key.
- Car detail dialog, account form mockup, and listing form mockup with local photo preview.
- The account and listing forms use `method="dialog"` and client-side interception. They never create accounts, send credentials, upload photos or publish listings. Form values are cleared on close.

Deployment: normal Git pull and `php artisan optimize:clear`. No migration or database write is required.
Assets are served by an allowlisted controller route so separate `public_html` hosting works without copying images manually.
CSS and JS are inlined from the repository files, following the existing storefront deployment approach.

Asset provenance:
- `hero.png`: generated with the imagegen skill for this prototype. The car is illustrative and unbranded.
- Demonstration car photos downloaded from Unsplash image CDN, cropped by the provider:
  - `coupe.jpg`: https://images.unsplash.com/photo-1555215695-3004980ad54e
  - `city.jpg`: https://images.unsplash.com/photo-1549317661-bd32c8ce0db2
  - `sport.jpg`: https://images.unsplash.com/photo-1503376780353-7e6692767b70
  - `classic.jpg`: https://images.unsplash.com/photo-1494976388531-d1058494cdd8
  - `performance.jpg`: https://images.unsplash.com/photo-1552519507-da3b142c6e3d
These photos do not verify the example year, condition, price, mileage, city, seller, or other specifications. Real listings must use their sellers' own photos and data.

Verification: local browser rendering at 320, 390, 600, 768, 1024 and 1440 pixels; all images loaded, no horizontal page/dialog overflow or JavaScript exceptions. Checked new/used filters, combined empty state, Arabic search, ascending price, favorites, details, and no network submission from the prototype listing form.

Next phase agreed with the user: real accounts and owner-managed new/used car listings, published immediately without moderation. This is not implemented by this design-only change.

## Interactive car demonstration

The separate “استكشف السيارة التوضيحية” section on `/cars` now opens an original,
unbranded procedural coupe. It is intentionally not attached to any sample listing:
its geometry, engine, dimensions and interior do not describe those cars.

- Two outward-opening doors, bonnet, luggage lid and glove compartment, animated at their hinge pivots.
- Exterior orbit/zoom, driver's seat, dashboard, engine and luggage inspection views.
- Interior instruments and navigation illustration are drawn locally on canvas.
- Mobile camera placement reuses the caravan's XR engine lifecycle with an injected model factory.
  It starts at 30% scale; the visitor can select the model's approximate full scale.
- Loading/camera failure recovery, focus/scroll restoration, camera shutdown on close/background,
  and disposal of generated geometry, materials and instrument textures.
- The Three.js and XR engine versions remain pinned to those already used by the caravan.
  The engine's attribution/license link appears inside the viewer.

Run browser checks against a local server and a Chrome debugging session:

```powershell
$env:CDP_URL='http://127.0.0.1:9227'
$env:CAR_EXPLORER_TEST_URL='http://127.0.0.1:8031/cars'
node tests/js/car-explorer.browser.mjs
```

Validation: 32 browser checks, including six viewport sizes, actual rendered door changes,
hinge directions, inspection controls, and simulated AR failure/placement/tracking loss/cleanup.
`CarExplorerTest` and the existing caravan page/asset tests passed (4 tests, 58 assertions).
AR sensor frames are simulated: actual camera tracking and placement still require a physical-phone test.
The preview is a simplified demonstration, not a photorealistic reconstruction or vehicle condition inspection.
No database changes are required.
