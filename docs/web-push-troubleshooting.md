# Web Push activation and delivery

The storefront, merchant, restaurant, driver and administration clients share
`public/web-push-client.js`. Blade includes it inline so it also works on hosts
whose `public_html` is separate from the Git-managed `public` directory.

Activation waits for the **selected** service worker to activate, shares concurrent
browser subscription requests, replaces subscriptions with an outdated VAPID key,
and requires a JSON `registered: true` response before showing success. Failed
registration can be retried. HTTP 419 refreshes the session CSRF token once through
`/csrf-token`, then repeats the original request without disabling CSRF protection.
The `/admin/` worker and its subscription remain separate from the `/` shop worker.
The iOS storefront skip option is preserved.

## Production checks

After deploying the changes:

```sh
php artisan optimize:clear
php artisan web-push:diagnose
```

The diagnostic prints extension availability, a public-key fingerprint, table
readability and admin delivery counts. It checks VAPID signing locally, sends no
notifications and never prints private keys, subscription endpoints or tokens.
It may initialize the persistent key file if no keys have ever been configured.
If tables/columns are missing, review and apply pending migrations. If signing
fails, check the configured key pair and file permissions; **do not delete or
regenerate the production key file to troubleshoot existing subscriptions**.

The existing `admin:send-activity-push` scheduled command should run every minute.
Verify the Hostinger cron executes `artisan schedule:run` from the correct project
using the intended PHP version. A queue backlog is separate from browser activation.

The error on the activation dialog now identifies the stage:

- `worker-failed` / `worker-timeout`: worker loading, installation or activation.
- `push-service` / `push-timeout`: browser connection to its push provider.
- `permission-denied`: site permission is blocked by the browser.
- `http-419`: session/cookies could not be refreshed.
- `http-429`: rate limit; wait before retrying.
- `http-500` / `http-503`: server error/configuration/database; inspect server logs.
- `invalid-response`: HTML, a redirect to login, or JSON without registration confirmation.

A saved subscription does not prove delivery. Use the existing administration
test-notification button with the receiving device, then test with the screen
locked. Sound and visibility also depend on the device's notification settings.

## Verification

```sh
node --test tests/js/web-push-client.test.mjs tests/js/merchant-pwa-sw.test.mjs
php artisan test --filter='OfferPushNotificationTest|AdminActivityPushTest|MerchantPwaTest|PwaIsolationTest|WebPushTransportTest|RestaurantCustomerWelcomeTest'
```

With a local Chrome debugging session on port 9227:

```sh
node scripts/web-push-browser-audit.mjs
```

The browser audit uses a local fixture on port 8134, real service-worker activation
and HTTP requests, a simulated push provider, expired-CSRF recovery, three viewport
sizes and iOS skip. It does not contact production or send real notifications.
The transport test uses the actual WebPush encryption/signing implementation and
a simulated HTTP provider returning acceptance, expiration and retry responses.
