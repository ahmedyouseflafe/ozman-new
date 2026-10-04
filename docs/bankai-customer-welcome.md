# Bankai customer welcome

The `/restaurants/bankai-sushi` menu now opens a branded welcome dialog on the first visit. It collects the customer's name, WhatsApp number, address and GPS location, with an explicit option to set location at checkout. Delivery orders still require coordinates through the existing checkout validation.

This is a customer-details form, not authenticated account access or verification of WhatsApp ownership. Saving now POSTs to `/restaurants/{shop:slug}/customer-registration`, validates the fields and saves a customer record in the existing `visitor_registrations` table, linked to Bankai through `shop_id`. The welcome dialog closes only after a successful server response. No new database migration is required.

Customers appear in the site dashboard under **تسجيلات الزوار**, with their name, WhatsApp link, address, map coordinates/link, restaurant and registration date. The list can be filtered by shop and searched by customer, phone, address or shop name. Existing dashboard permissions remain in force; there is no public endpoint for reading customer details.

Details are also remembered under `ozman.restaurant.{shopId}.customer.v1`, automatically fill checkout, and can be edited through **بياناتي / My details**. A random browser secret is stored separately; the server stores its scoped SHA-256 digest. Retries and edits from the same browser update one record. Another browser using the same phone number cannot overwrite that record: phone numbers are not authentication. Clearing browser storage or using another device can create another registration.

Customers whose details exist only in the previous local version see the prefilled form on their next visit and must press Save to register with the site. The interface states that details are sent to Ozman for Bankai. Existing local data cannot appear in the dashboard before that visit. Checkout edits synchronize to the same record; failures remain pending locally and show a retry message instead of claiming successful registration.

Notification activation is integrated into the dialog, replacing the separate offer prompt for Bankai only. The customer explicitly presses the activation button and grants the browser permission. Success appears only after the existing push subscription API saves the subscription. This uses the existing **all-store offers** audience, as stated in the interface. Unsupported browsers, denied permissions and failed subscription requests do not prevent registration. API requests retain the existing CSRF protection.

The form supports Arabic, Hebrew and English, keyboard focus and mobile scrolling. It handles invalid saved data, unavailable local storage and delayed GPS callbacks. Changing the delivery address clears the old coordinates to avoid using a previous destination.

## Files to upload

- `resources/views/front/restaurant_menu.blade.php`
- `resources/views/front/partials/restaurant_customer_welcome.blade.php`
- `public/restaurant-customer-welcome.js`
- `app/Http/Controllers/RestaurantCustomerRegistrationController.php`
- `app/Http/Controllers/VisitorRegistrationAdminController.php`
- `resources/views/admin/visitor_registrations/index.blade.php`
- `routes/web.php`

JavaScript is embedded from the Git-managed file by Blade to support the existing hosting layout with a separate `public_html`. Upload these together. If route/view caches are enabled, refresh them using the project's usual deployment procedure (`php artisan optimize:clear`). No deployment was performed.

## Validation

- Current welcome/registration and offer-notification tests: **13 passed, 91 assertions**. Covers real SQLite persistence and dashboard output, retry/edit deduplication, invalid fields/coordinates, Arabic phone digits, generated map URLs, untrusted fields, isolation from other restaurants, same-phone overwrite protection, search/filter and access restrictions.
- Earlier restaurant regression run: six existing dashboard/driver presentation assertions also failed against the pre-change menu. Those unrelated views were not changed here.
- Local Chrome desktop (1440×1000) and mobile (390×844): first visit, return visit, profile edit, Arabic digits and phone validation, checkout autofill, location denial/success and late callback, notification denial, subscription failure and success, malformed/blocked local storage, RTL/LTR layout and no JavaScript exceptions.
- Browser registration scenarios also covered failed saves, duplicate submit clicks, retry with the same secret, confirmation of old local profiles and checkout synchronization. Browser HTTP and permission scenarios were simulated locally. Database persistence and dashboard access were exercised separately through Laravel feature tests. Real device permission dialogs and push delivery need checking after the user's deployment. No production registration, order or subscription was submitted.

The local screenshots and browser harness are under `storage/app/audit-20261003/bankai-welcome-*` and `test-bankai-welcome.mjs` (ignored audit artifacts).

Browser behavior references: [notification permission](https://developer.mozilla.org/en-US/docs/Web/API/Notification/requestPermission_static), [GPS permission and location](https://developer.mozilla.org/en-US/docs/Web/API/Geolocation/getCurrentPosition).
