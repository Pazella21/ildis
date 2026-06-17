# Frontend Asset Versioning Design Spec

**Date:** 2026-06-12
**Status:** Approved

## Problem

After deploying CSS or JavaScript changes to the public frontend, users continue to see stale styles and scripts because browsers cache asset URLs without a cache-busting query string. There is no versioning on URLs served through `frontend\assets\AppAsset` (e.g. `/frontend/assets/css/style.css`). Users must hard-refresh to pick up changes.

## Goals

- Bust browser cache automatically when custom frontend assets change.
- Scope versioning to editable custom files only — not vendor libraries (Bootstrap, AOS, Swiper, etc.).
- Use file modification time (`mtime`) so no manual version bump or deploy script is required.
- Preserve existing CSS/JS load order.

## Non-Goals

- Backend/admin asset versioning.
- CDN or reverse-proxy cache invalidation (problem is browser cache only).
- HTTP `Cache-Control` header changes.
- Versioning hardcoded asset paths in views (favicon, inline images).
- Build pipeline or content-hash filenames.

## Solution

Use Yii2's built-in per-file `appendTimestamp` option in `AppAsset`. Custom CSS/JS entries use array syntax with `'appendTimestamp' => true`; vendor entries remain plain strings. Yii2 appends `?v=<filemtime>` when registering each versioned file.

No new classes, no `assetManager` config changes, no layout changes.

## Current State

- `AppAsset` serves all frontend CSS/JS from `@web/frontend/assets`.
- Four layouts register `AppAsset`: `main.php`, `main-buku-tamu.php`, `mainold.php`, `main3.php`.
- No `assetManager.appendTimestamp` is configured in `frontend/config/main.php`.

## Implementation

### File changed: `frontend/assets/AppAsset.php`

Convert custom asset entries to array syntax with `appendTimestamp`. Vendor entries stay as plain strings.

**Custom files to version (7 total):**

| File | Type |
|------|------|
| `css/plugins.css` | CSS |
| `search/search.css` | CSS |
| `css/lazyload.css` | CSS |
| `css/style.css` | CSS |
| `css/mobile-menu.css` | CSS |
| `js/lazyload.js` | JS |
| `js/main.js` | JS |

**Target `AppAsset` structure:**

```php
public $css = [
    ['css/plugins.css', 'appendTimestamp' => true],
    ['search/search.css', 'appendTimestamp' => true],
    'vendor/aos/aos.css',
    'vendor/bootstrap/css/bootstrap.min.css',
    'vendor/bootstrap-icons/bootstrap-icons.css',
    'vendor/boxicons/css/boxicons.min.css',
    'vendor/swiper/swiper-bundle.min.css',
    ['css/lazyload.css', 'appendTimestamp' => true],
    ['css/style.css', 'appendTimestamp' => true],
    ['css/mobile-menu.css', 'appendTimestamp' => true],
];

public $jsOptions = ['defer' => true];

public $js = [
    'vendor/bootstrap/js/bootstrap.bundle.min.js',
    ['js/lazyload.js', 'appendTimestamp' => true],
    'vendor/aos/aos.js',
    'vendor/swiper/swiper-bundle.min.js',
    'vendor/php-email-form/validate.js',
    ['js/main.js', 'appendTimestamp' => true],
];
```

Keep `public $jsOptions = ['defer' => true]` unchanged. Yii2 merges per-file array options with `$jsOptions`, so custom JS files retain `defer`.

### Files unchanged

- `frontend/views/layouts/*.php` — all layouts already call `AppAsset::register($this)`.
- `frontend/config/main.php` — no config changes.

## Data Flow

1. Layout calls `AppAsset::register($this)`.
2. For each custom asset, Yii2 calls `AssetManager::getAssetUrl($bundle, $file, appendTimestamp: true)`.
3. `getAssetUrl` reads `filemtime` from disk and appends `?v=<timestamp>`.
4. View renders `<link>` / `<script>` tags with versioned URLs for custom files only.
5. When a file is edited on deploy, its mtime changes → new URL → browser fetches fresh content.

**Example rendered output:**

```html
<link href="/frontend/assets/css/style.css?v=1749782400" rel="stylesheet">
<link href="/frontend/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<script src="/frontend/assets/js/main.js?v=1749782400" defer></script>
<script src="/frontend/assets/vendor/aos/aos.js" defer></script>
```

## Error Handling & Edge Cases

| Scenario | Behavior |
|----------|----------|
| File missing on disk | `@filemtime` returns false → URL emitted without `?v=` (graceful fallback) |
| File unreadable | Same — no timestamp, no exception |
| Deploy via `git pull` | Changed files get new mtime → new `?v=` |
| Deploy via `rsync` | mtime updated on transferred files → cache busted |
| Unchanged vendor files | URLs stay identical → browser cache remains valid |
| Future CDN in front | Query-string busting continues to work |

## Testing

### Manual verification

1. Load any frontend page → view source.
2. Confirm custom CSS/JS URLs contain `?v=<number>`.
3. Confirm vendor URLs have no `?v=` parameter.
4. Edit `style.css` → reload → confirm `?v=` value changed and new styles apply.
5. Spot-check `main-buku-tamu.php` layout.

### Optional unit test

Register `AppAsset` in a test view and assert HTML contains `style.css?v=` and does not contain `bootstrap.min.css?v=`. Low priority — manual verification is sufficient.

## Alternatives Considered

| Approach | Why not chosen |
|----------|----------------|
| Global `assetManager.appendTimestamp` | Versions vendor files too (harmless but broader than needed) |
| Split `FrontendCustomAsset` bundle | Breaks interleaved load order; more files for no benefit |
| Deploy-time version in `params.php` | User chose automatic mtime; requires manual/CI step |
| Content-hash filenames | Requires build pipeline; overkill for browser-cache-only problem |
