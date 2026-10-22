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
- Activities include order creation/status/data changes, product creation/edits/deletion and images/offers, shop updates, screens, advertisements, categories, stories, wheel settings/prizes, welcome-wheel spins/redemptions and salon bookings. First storefront visit per browser/session per shop per day generates an alert; reloads still count as views but do not generate repeated visit alerts. Eloquent observers cover normal application saves, not direct database writes or bulk SQL updates. Automated wheel quota bookkeeping is excluded. There are no fabricated customer login, payment or delivery events.
- The persistent notification bell appears on existing admin headers/sidebars and the new dashboard, now including **all recorded event types**. It polls every 15 seconds while visible; optional foreground sound is enabled by user interaction. Read state is isolated per admin, and marking read only advances through the last fetched event ID. Background device push is available separately through the device controls described below.
- Dashboard refreshes every minute and after incoming order events. Filters, activity pagination, order detail dialogs, top products, previous-period comparison and UTF-8 CSV export are included. CSV guards against spreadsheet formula injection.

## Storefront coverage

Restaurants, Elegance, electronics and the existing general checkout already persist orders. Sweets, clothing and furniture previously linked straight to WhatsApp; these now POST `/stores/{shop:slug}/orders` before opening the shop chat. The server resolves product ownership, active status, prices, quantities and fashion options. Browser retries reuse a random token; a unique stored digest and transaction prevent duplicate submissions. Changes to a payload using the same token are rejected. No WhatsApp message is sent automatically, and unsupported service/inquiry flows are not counted as product sales.

## Validation

- `ShopAnalyticsTest`, `CatalogCheckoutTest`, `CosmeticsCustomerRewardsTest`, `RestaurantPurchaseWheelTest`, `ResponsiveDashboardPermissionsTest`, `AdminShopDashboardImpersonationTest`: pass.
- Browser audit on an isolated local SQLite database: white layout, 320/390/768/1440 viewport widths, overflow checks, order modal, shop/activity filters, notification read state, all three newly tracked storefront checkouts, idempotent retry, and a real status change appearing through the live notification feed. No WhatsApp navigation or production orders.
- The existing `RestaurantOrderingTest` suite has six pre-existing view assertions failing. Running it against the original HEAD versions of all touched shared runtime files reproduces exactly the same six failures. The new analytics tests and the restaurant purchase-wheel regression suite pass. No unrelated restaurant UI assertions were changed.

Browser scripts in `scripts/shop-analytics-browser-audit.mjs` and `scripts/catalog-analytics-browser-audit.mjs` use localhost:8032 and Chrome CDP:9227. Local seed/action scripts and screenshots are ignored under `storage/app`; never run fixture actions against production.

## Installed administration app and background notifications

The administration PWA starts at `/admin/analytics`, with manifest identity `/admin/analytics` and service-worker scope `/admin/`. It does not unregister, replace or reuse a merchant/storefront worker or its subscriptions. Its worker never caches private pages, reports, orders or customer details. Clicking a notification opens the relevant order or activity within analytics; an existing storefront window is never navigated away.

Active super-admin login now enables Laravel remember-me automatically, and opening analytics upgrades an existing authenticated session to remembered login. This uses Laravel's secure remember token, not a client-side authenticated flag. The browser/PWA may have separate storage and require its own first login. Explicit logout revokes remember-me and removes **only this device's** admin subscription, including when the session was recalled from a persistent cookie. Closing the page or letting the ordinary session expire does not remove the subscription. Device permissions, clearing site data, browser policies, role/account changes or credential/token revocation can still require login/reactivation.

After deploying the migration:

1. Open `/admin/analytics` on the actual phone and log in as the main administrator.
2. Tap **تثبيت تطبيق الإدارة**. On iPhone/iPad with iOS/iPadOS 16.4+, add the page to the Home Screen from Safari's share menu and open that icon. Permission cannot be granted by the server or from an ordinary iPhone browser tab.
3. Tap **تفعيل إشعارات الجوال**, then allow notifications. Repeat per device that should receive alerts.
4. Tap **إرسال إشعار تجريبي**. The UI reports provider acceptance, not proof of display. Verify the actual banner/sound on the device, and test a real shop action with the app closed. No real phone delivery has been confirmed by the automated tests.
5. Silent mode, Focus/Do Not Disturb, notification settings, connectivity and OS/browser restrictions control sound and delivery. The application requests system sound (`silent:false`); it cannot force sound or choose a WhatsApp-style custom notification tone.

Sources: [WebKit Home Screen Web Push](https://webkit.org/blog/13878/), [Apple Web Push](https://developer.apple.com/documentation/usernotifications/sending-web-push-notifications-in-web-apps-and-browsers), [notification silent option](https://developer.mozilla.org/en-US/docs/Web/API/Notification/silent).

### Server delivery

Every new tracked activity creates a durable outbox entry per registered active super-admin device, unique on `(subscription_id,event_id)`. Old history is not replayed upon subscription. Delivery begins after the originating HTTP response; failed or interrupted sends are retried by `admin:send-activity-push`. Transaction rollbacks also roll back associated events/outbox rows. Transient failures use backoff, with 10 attempts maximum; expired subscriptions and subscriptions for disabled/non-admin accounts are removed. Stable notification tags reduce duplicate display if the process dies between provider acceptance and recording success. This is at-least-once delivery, not a guarantee that every operating system displays every alert.

**Enable Laravel's scheduler every minute on the host** for reliable retries and background/console-generated events. If the site's scheduler already runs, no second cron entry is needed. In Hostinger Cron Jobs, use the server's PHP binary and the absolute project path (the same directory where `php artisan` works):

```sh
* * * * * /ABSOLUTE/PATH/TO/php /ABSOLUTE/PROJECT/PATH/artisan schedule:run
```

The five timing fields belong in the scheduler fields when the host has a separate command input; do not paste the placeholder paths literally. In the existing SSH project directory, `pwd` and `command -v php` show the two needed paths. The implementation does not configure the remote server's cron automatically.

To inspect the configured schedule or run one delivery batch manually from that directory:

```sh
php artisan schedule:list
php artisan admin:send-activity-push
```

Reuses the existing Web Push VAPID configuration. Preserve `storage/app/private/web-push-vapid.json` or the configured VAPID keys across deployments. Replacing keys invalidates existing subscriptions; there is no need to generate new keys for this admin PWA. Provider endpoint validation accepts HTTPS push endpoints from Apple, Mozilla, FCM and Windows, rejects private/unknown hosts, and disables redirects. Subscription secrets are hidden from model serialization and absent from device-status responses. Lock-screen payloads include the shop/activity/order number and amount, without customer address/phone.

Added verification: `AdminActivityPushTest` covers admin-only device binding, endpoint validation, isolation, rollback, retries, expired subscriptions, role revocation, remember-cookie restoration, device logout and public PWA assets. `tests/js/admin-activity-sw.test.mjs` covers background rendering, safe click navigation and offline privacy. `scripts/admin-push-browser-audit.mjs` verifies the real local worker/manifest, 320/390/768/1440 layouts, enable/test/disable controls with a mocked transport, direct-tap permission requests and iPhone installation guidance. It does not send live push messages.
