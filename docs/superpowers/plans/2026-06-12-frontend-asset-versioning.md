# Frontend Asset Versioning — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Append automatic `?v=<mtime>` cache-busting query strings to custom frontend CSS/JS files so users pick up asset changes after deploy without hard-refreshing.

**Architecture:** Use Yii2's built-in per-file `appendTimestamp` option in `AppAsset`. Custom files use array syntax with `'appendTimestamp' => true`; vendor files stay plain strings. No new classes, no config changes, no layout changes.

**Tech Stack:** PHP 7.4+/Yii2, Codeception functional tests (`vendor/bin/codecept run -c frontend`)

**Spec:** `docs/superpowers/specs/2026-06-12-frontend-asset-versioning-design.md`

---

## Files Structure

| Action | File | Responsibility |
|--------|------|----------------|
| Modify | `frontend/assets/AppAsset.php` | Per-file `appendTimestamp` on 7 custom assets |
| Create | `frontend/tests/functional/AssetVersioningCest.php` | Assert versioned custom URLs, unversioned vendor URLs |

No changes to layouts, `frontend/config/main.php`, or vendor files.

---

### Task 1: Failing functional test for asset versioning

**Files:**
- Create: `frontend/tests/functional/AssetVersioningCest.php`

- [ ] **Step 1: Write the failing test**

Create `frontend/tests/functional/AssetVersioningCest.php`:

```php
<?php

namespace frontend\tests\functional;

use frontend\tests\FunctionalTester;

class AssetVersioningCest
{
    public function customAssetsHaveCacheBustingQuery(FunctionalTester $I): void
    {
        $I->amOnPage('/');
        $I->seeInSource('style.css?v=');
        $I->seeInSource('main.js?v=');
        $I->seeInSource('lazyload.css?v=');
        $I->dontSeeInSource('bootstrap.min.css?v=');
        $I->dontSeeInSource('aos.js?v=');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/codecept run -c frontend functional AssetVersioningCest -v`

Expected: FAIL — `style.css?v=` not found in page source (assets currently have no query string).

- [ ] **Step 3: Commit**

```bash
git add frontend/tests/functional/AssetVersioningCest.php
git commit -m "test: add failing asset versioning functional test"
```

---

### Task 2: Enable per-file appendTimestamp in AppAsset

**Files:**
- Modify: `frontend/assets/AppAsset.php`

- [ ] **Step 1: Update AppAsset with versioned custom files**

Replace the `$css` and `$js` arrays in `frontend/assets/AppAsset.php` with:

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

public $js = [
    'vendor/bootstrap/js/bootstrap.bundle.min.js',
    ['js/lazyload.js', 'appendTimestamp' => true],
    'vendor/aos/aos.js',
    'vendor/swiper/swiper-bundle.min.js',
    'vendor/php-email-form/validate.js',
    ['js/main.js', 'appendTimestamp' => true],
];
```

Keep `public $jsOptions = ['defer' => true];` and `public $depends` unchanged.

- [ ] **Step 2: Run functional test to verify it passes**

Run: `vendor/bin/codecept run -c frontend functional AssetVersioningCest -v`

Expected: PASS — custom assets include `?v=`, vendor assets do not.

- [ ] **Step 3: Commit**

```bash
git add frontend/assets/AppAsset.php
git commit -m "feat: add cache-busting timestamps to custom frontend assets"
```

---

### Task 3: Manual verification

**Files:** None (verification only)

- [ ] **Step 1: Start dev server**

Run: `php yii serve --port=8080`

- [ ] **Step 2: Verify rendered HTML**

Open `http://localhost:8080/` → View Page Source.

Confirm:
- `style.css?v=<number>` present
- `main.js?v=<number>` present with `defer`
- `bootstrap.min.css` has no `?v=`
- `aos.js` has no `?v=`

- [ ] **Step 3: Verify mtime busting**

Run: `touch frontend/assets/css/style.css`

Reload page source — confirm the `?v=` value on `style.css` changed.

- [ ] **Step 4: Spot-check guestbook layout**

Open a page using `main-buku-tamu.php` layout (if routable) and confirm same versioning behavior.
