# Fix Homepage Card Badge Counts for Published Documents Only

**Date**: 2026-05-27
**Status**: Approved

## Problem

The public frontend homepage displays badge counts on document-type cards (Peraturan, Monografi, Artikel, Putusan) and legal-status cards (Berlaku, Tidak Berlaku). These counts include unpublished documents (`is_publish = 0`), making them inconsistent with the listing pages that filter by `is_publish = 1`.

Users see inflated counts on the homepage, then click through to find fewer documents than advertised.

## Root Cause

- `DocumentQuery::total($id)` counts all documents of a given type with no `is_publish` filter.
- The Berlaku/Tidak Berlaku count queries in `index.php` also lack the `is_publish` filter.

## Solution

### 1. Update `DocumentQuery::total()` method

**File**: `frontend/models/DocumentQuery.php`

Add `is_publish = 1` filter so all callers of `total()` only count published documents:

```php
public function total($id)
{
    return $this->andWhere(['tipe_dokumen' => $id])
        ->andWhere(['is_publish' => 1])
        ->count();
}
```

### 2. Update status count queries in homepage view

**File**: `frontend/views/site/index.php`

Add `is_publish` filter to both Berlaku and Tidak Berlaku queries:

```php
$totalBerlaku      = Dokumen::find()->where(['status' => 'Berlaku', 'is_publish' => 1])->count();
$totalTidakBerlaku = Dokumen::find()->where(['status' => 'Tidak Berlaku', 'is_publish' => 1])->count();
```

## Files Changed

| File | Change |
|------|--------|
| `frontend/models/DocumentQuery.php` | Add `is_publish = 1` to `total()` method |
| `frontend/views/site/index.php` | Add `is_publish = 1` to Berlaku/Tidak Berlaku count queries |

## Impact

- Homepage badge counts will match what users see on listing pages.
- No changes to listing/search behavior.
- `total()` is only called from `index.php`, so no other consumers are affected.