# Promoter pages

Independent `promoter` role; this does not reuse marketer permissions. Public directory: `/promoters`. A public profile lives at `/promoters/{slug}`. Super administrators manage accounts at `/promoter/accounts` (also linked in the admin sidebar). Promoters sign in through `/login` and are redirected to their own studio at `/promoter`.

Saving the profile derives its canonical URL from the display name. Duplicate names receive a numeric suffix. Previously used URLs remain reserved to the same profile and redirect directly to its current URL; disabled pages remain unavailable at old URLs too. The `2026_10_23_000002` migration updates existing profiles to match their current names and preserves their original URLs. Its rollback removes historical redirects but keeps the latest canonical names.

## Deployment

```sh
git pull --ff-only origin main
php artisan migrate --force
php artisan optimize:clear
```

The migration extends the user role enum and creates `promoter_profiles` and `promoter_posts`. No production sample accounts or posts are created. From **حسابات المروّجات**, create a name, unique page slug, email and password, then share credentials with the account owner. Administrators can disable an account/page or reset its password. Account disabling blocks existing sessions from the studio and makes public content unavailable.

## Publishing

- Profile avatar and biography; permanent image/video posts; image/video stories visible for 24 hours. Expiry is checked on requests and needs no cron. Expired stories remain in the owner's archive until deleted.
- Optional active-shop association adds a link to the shop; content is never inserted into shop stories, advertisements or main screens.
- Uploaded videos appear in the page's player/playlist (latest 30), and alongside images in the paginated post grid. Playback begins by user interaction; subsequent videos advance when playback ends. Sound is controlled by the visitor.
- Stories support next/previous and pause; image stories advance after seven seconds. Video stories advance when playback ends. Viewing pauses when the page is hidden.
- Native modal dialogs keep media within the viewport, trap focus, restore focus and prevent background scrolling.
- Supported media: JPEG/PNG/WebP and MP4/WebM, maximum 20 MiB per post. Avatar/optional video cover: 5 MiB. MP4 with H.264/AAC is preferable for broad mobile playback; there is no transcoding. Server PHP settings must allow these sizes (`upload_max_filesize` at least `20M`, `post_max_size` at least `32M`) and sufficient upload time. Proxy/web-server limits must also permit the request.
- Files are on the private local disk. Guarded media endpoints enforce profile publication and expiry, or owner/admin preview authorization. They return private/no-store responses with range support for video. No public storage symlink is needed for these uploads. Deleting a post removes its files; already downloaded copies cannot be revoked.

## Validation

`tests/Feature/PromoterPageTest.php` covers account creation and login, role/profile isolation, upload validation, shop association, no shop-display publication, expiry/media access, profile replacement, and deletion. Browser audit fixtures are local-only and excluded from Git.

Rollback drops profile/post records and converts promoter accounts to customer accounts. It does not remove uploaded private files. Back up records/files before any rollback; normal deployment requires only `migrate`.
