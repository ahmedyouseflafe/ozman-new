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

## Real-car reference viewer

The `/cars` showroom now uses the detailed **BMW M4 Competition M Package** model
by SRT Performance, replacing the procedural demonstration car. The binary retains
its original textures and cabin geometry and is hosted through the allowlisted
front-assets route (including on installations with separate `public_html`).

Attribution, source, license and binary SHA-256 are recorded in
`public/models/car-explorer/ATTRIBUTION.md`. Credit and CC BY 4.0 links are also visible
in the viewer. The preview WebP is rendered from the same model.

Supported: exterior orbit/zoom, rear view, driver's seat and dashboard views,
and camera placement using the existing caravan XR lifecycle. It starts at 30%
of the nominal display scale; 100% is an approximate 4.8 m reference length,
not a measurement or verification of a seller's vehicle.

**Asset limitation:** this model is not articulated. Opening the doors, bonnet,
boot and glove compartment is not available; the interface states this and omits
those controls. Interior components are the real model's own meshes/textures,
not invented overlays. A correctly articulated licensed asset is required to
restore opening-part interactions on a realistic car.

The GLB is approximately 5.8 MB and loads on demand. The viewer clones GPU resources
from its cached source so disposal on close does not invalidate subsequent openings.
Closing/backgrounding stops the XR camera; unsupported devices fall back to 3D.

Run browser checks against a local server and a Chrome debugging session:

```powershell
$env:CDP_URL='http://127.0.0.1:9227'
$env:CAR_EXPLORER_TEST_URL='http://127.0.0.1:8031/cars'
node tests/js/car-explorer.browser.mjs
```

Validation: 25 browser checks covering actual geometry/textures, camera viewpoints,
six viewport sizes, reopening, and simulated AR placement/failure/tracking loss/cleanup.
`CarExplorerTest` checks the public page, attribution/limitations and binary/module MIME types.
AR sensor frames are simulated; physical-phone tracking remains to be verified.
No database changes are required.