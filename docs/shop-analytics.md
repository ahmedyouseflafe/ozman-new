# Central shop analytics

The super-admin-only dashboard is `/admin/analytics`, also linked as **إحصائيات كل المحلات** in the existing sidebar. It has its own white layout. No employee/merchant permission grants access to this dashboard, its exports, notification reads, or order details.

## Deploy

From the existing application directory on the server:

```sh
git pull --ff-only origin main
php artisan migrate --force
php artisan optimize:clear
```

Stop if any command fails. The migration creates activity/visit/read tables and completion timestamps on orders. It does not reset customers, rewards, products, or orders. Existing completed/delivered orders receive an estimated historical completion date from `delivered_at`, falling back to `updated_at` / `created_at`. No historical sale notifications are generated. New telemetry begins at the recorded activation date.

JavaScript is embedded by Blade from the Git-managed `public` files, so hosts with a separate `public_html` do not need a second asset copy or an npm build.

## Metrics and notifications

- Sales: current `completed` or `delivered` orders, grouped by first completion timestamp, after order discounts. New WhatsApp requests are pending orders, never sales. Cancelling/reopening a completed order excludes it from current sales and records a reversal. A unique sale event key prevents duplicate sale alerts for completed → delivered or repeated saves. This is operational sales reporting, not payment settlement, profit, or a refund ledger.
- Orders/status counts use creation dates; sales use completion dates. Daily periods use the configured application timezone (currently UTC), shown in the dashboard.
- Historical order data is included. No fabricated visits/login history. General orders without a shop remain in overall figures and are flagged.
- Visits: successful server-rendered storefront GETs only, including the Ozman home storefront; excludes recognized bots, admin previews, failures and assets. Session-held random identity is HMAC-hashed; IP addresses and raw session IDs are not stored. Cached/offline page views are not counted. Different devices/cookie resets can count separately, so no unverified visitor-to-customer conversion percentage is shown.
- Customer registration records, estimated purchasing customers (phone per shop), and owner authentication events are distinct metrics. An owner with several shops contributes a login to each owned active shop. Customer contact form submission does not pretend to be authenticated customer login.
- Activities include order creation/status/financial-field changes, product creation/price/category/availability changes/deletion, shop status, screens, advertisements and categories. Eloquent observers cover normal application saves, not direct database writes or bulk SQL updates.
- The persistent notification bell appears on existing admin headers/sidebars and the new dashboard. Polls every 15 seconds while visible; optional sound is enabled by user interaction. Events persist when the tab closes, but this is **not background device push**. Read state is isolated per admin, and marking read only advances through the last fetched event ID.
- Dashboard refreshes every minute and after incoming order events. Filters, activity pagination, order detail dialogs, top products, previous-period comparison and UTF-8 CSV export are included. CSV guards against spreadsheet formula injection.

## Storefront coverage

Restaurants, Elegance, electronics and the existing general checkout already persist orders. Sweets, clothing and furniture previously linked straight to WhatsApp; these now POST `/stores/{shop:slug}/orders` before opening the shop chat. The server resolves product ownership, active status, prices, quantities and fashion options. Browser retries reuse a random token; a unique stored digest and transaction prevent duplicate submissions. Changes to a payload using the same token are rejected. No WhatsApp message is sent automatically, and unsupported service/inquiry flows are not counted as product sales.

## Validation

- `ShopAnalyticsTest`, `CatalogCheckoutTest`, `CosmeticsCustomerRewardsTest`, `RestaurantPurchaseWheelTest`, `ResponsiveDashboardPermissionsTest`, `AdminShopDashboardImpersonationTest`: pass.
- Browser audit on an isolated local SQLite database: white layout, 320/390/768/1440 viewport widths, overflow checks, order modal, shop/activity filters, notification read state, all three newly tracked storefront checkouts, idempotent retry, and a real status change appearing through the live notification feed. No WhatsApp navigation or production orders.
- The existing `RestaurantOrderingTest` suite has six pre-existing view assertions failing. Running it against the original HEAD versions of all touched shared runtime files reproduces exactly the same six failures. The new analytics tests and the restaurant purchase-wheel regression suite pass. No unrelated restaurant UI assertions were changed.

Browser scripts in `scripts/shop-analytics-browser-audit.mjs` and `scripts/catalog-analytics-browser-audit.mjs` use localhost:8032 and Chrome CDP:9227. Local seed/action scripts and screenshots are ignored under `storage/app`; never run fixture actions against production.
