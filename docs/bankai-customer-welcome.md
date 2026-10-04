# Bankai customer welcome

The `/restaurants/bankai-sushi` menu now opens a branded welcome dialog on the first visit. It collects the customer's name, WhatsApp number, address and GPS location, with an explicit option to set location at checkout. Delivery orders still require coordinates through the existing checkout validation.

This is a customer-details form, not authenticated account access or verification of WhatsApp ownership. Details are stored on the current device under `ozman.restaurant.{shopId}.customer.v1`, automatically fill checkout, and can be edited through **بياناتي / My details**. Registration itself does not send personal details to a new backend endpoint. The existing order endpoint receives them when an order is placed. No database migration is required.

Notification activation is integrated into the dialog, replacing the separate offer prompt for Bankai only. The customer explicitly presses the activation button and grants the browser permission. Success appears only after the existing push subscription API saves the subscription. This uses the existing **all-store offers** audience, as stated in the interface. Unsupported browsers, denied permissions and failed subscription requests do not prevent registration. API requests retain the existing CSRF protection.

The form supports Arabic, Hebrew and English, keyboard focus and mobile scrolling. It handles invalid saved data, unavailable local storage and delayed GPS callbacks. Changing the delivery address clears the old coordinates to avoid using a previous destination.

## Files to upload

- `resources/views/front/restaurant_menu.blade.php`
- `resources/views/front/partials/restaurant_customer_welcome.blade.php`
- `public/restaurant-customer-welcome.js`

JavaScript is embedded from the Git-managed file by Blade to support the existing hosting layout with a separate `public_html`. Upload all three together. No deployment was performed.

## Validation

- `RestaurantCustomerWelcomeTest`: language coverage, Bankai-only scope and suppression of the duplicate notification prompt.
- Existing restaurant and offer-notification tests: 35 tests, 29 passed. Six existing dashboard/driver presentation assertions also fail when run against the pre-change menu; no new failing tests.
- Local Chrome desktop (1440×1000) and mobile (390×844): first visit, return visit, profile edit, Arabic digits and phone validation, checkout autofill, location denial/success and late callback, notification denial, subscription failure and success, malformed/blocked local storage, RTL/LTR layout and no JavaScript exceptions.
- Browser permission and subscription scenarios were simulated locally, with subscription POSTs sent to a local test server. Real device permission dialogs and push delivery need checking after the user's deployment. No production order or subscription was submitted.

The local screenshots and browser harness are under `storage/app/audit-20261003/bankai-welcome-*` and `test-bankai-welcome.mjs` (ignored audit artifacts).

Browser behavior references: [notification permission](https://developer.mozilla.org/en-US/docs/Web/API/Notification/requestPermission_static), [GPS permission and location](https://developer.mozilla.org/en-US/docs/Web/API/Geolocation/getCurrentPosition).
