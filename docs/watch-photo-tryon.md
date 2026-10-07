# Watch photo preview

Elegance Perfumes now has a demo watch card above its catalog. The reference is an AI-generated, unbranded silver watch with a blue dial, not an inventory product. Customers can upload a wrist photo or capture one after a five-second countdown. Generation is explicit and requires consent to send both images to FASHN.

## Enable after uploading

Keep the existing server-side `FASHN_API_KEY`. Add the cosmetics shop to the existing allowlist, preserving other enabled shops, for example:

```dotenv
FASHN_ENABLED_SHOPS=ahmed-lafe,elegance-perfumes
```

Refresh Laravel's configuration cache using the usual deployment procedure (`php artisan config:cache`). Never put the API key into frontend JavaScript or Git. Include the new demo PNG when uploading. No migration is needed.

The watch endpoint always uses Try-On Max, 2K, quality mode, one output: **4 FASHN credits per generation** according to https://docs.fashn.ai/api-reference/tryon-max . Capturing/uploading without generating makes no provider request. Limits: six generation requests per hour per client; polling has a separate limit. Images must be JPEG/PNG/WebP, up to 6 MB, 256–4096 pixels per dimension.

## Verification and limitations

Automated provider tests use fake HTTP responses and do not assess generated image quality. Browser camera tests use a synthetic local stream. Before making the feature generally available, test a real wrist photo: one bare wrist, back of hand and forearm visible, even lighting. Check watch orientation, bracelet contact, dial detail and preservation of the hand. This produces a generated still image, not live AR or a physical sizing guarantee.
