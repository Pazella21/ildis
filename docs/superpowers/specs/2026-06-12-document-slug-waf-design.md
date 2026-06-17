# Document Slug WAF Safety — Design Spec

**Date:** 2026-06-12  
**Status:** Approved (brainstorming)  
**Scope:** Prevent F5 BIG-IP WAF blocks on long document URLs (app-side only)

---

## Problem

Document view URLs use the pattern `/dokumen/{id}-{slug}`. Documents with very long titles produce long slugs in the path. F5 BIG-IP WAF blocks these requests **before they reach Yii**, so in-app 301 redirects cannot recover blocked traffic.

Production symptoms: WAF blocks on long-title documents only. Infra/WAF policy changes are out of scope — fix must be entirely in the application.

### Root cause in codebase

`DocumentSlug::fromJudul()` caps slugs at 80 characters, but two code paths bypass that cap:

1. `Dokumen::getUrlSlug()` returns the database `slug` column as-is (up to 255 characters).
2. `DocumentSlug::resolve()` returns a non-empty DB `slug` without normalization.

Long slugs stored in `document.slug` are emitted in canonical URLs, Open Graph tags, and shared links.

---

## Decisions (from brainstorming)

| Question | Choice |
|----------|--------|
| Production impact | **A** — WAF blocks already occurring on document URLs |
| Affected URLs | **A** — Long-title documents only (long slugs in path) |
| Fix scope | **A** — App-only; no F5 rule changes |
| Canonical URL format | **A** — Keep `/dokumen/{id}-{slug}` for SEO |
| Approach | **Structured peraturan slugs + truncated judul for other types** |

---

## Slug generation rules

Single source of truth: `common/components/DocumentSlug.php`.

### New / updated API

```php
DocumentSlug::fromDocument(
    int $tipeDokumen,
    string $judul,
    ?string $singkatanJenis = null,
    ?string $nomorPeraturan = null,
    ?string $tahunTerbit = null
): string

DocumentSlug::normalize(string $slug): string  // enforce MAX_LENGTH, trim trailing hyphens

DocumentSlug::fromJudul(string $judul): string  // retained; delegates to normalize()
```

### Constants

- `MAX_LENGTH = 60` (reduced from 80)
- Empty result after slugify → `dokumen`

### Generation by document type

| `tipe_dokumen` | Condition | Slug source | Example |
|----------------|-----------|-------------|---------|
| 1 (Peraturan) | `singkatan_jenis`, `nomor_peraturan`, and `tahun_terbit` all non-empty | `Inflector::slug("{singkatan}-{nomor}-{tahun}")` | `pm-7-2026` |
| 1 (Peraturan) | Any metadata missing | Truncated `fromJudul(judul)` | `peraturan-menteri-hukum-nomor-7-tahun-2026-tentang-tata` |
| 2–4 (Monografi, Artikel, Putusan) | Always | Truncated `fromJudul(judul)` | `putusan-mk-nomor-12-tahun-2025-tentang` |

`nomor_peraturan` values with spaces or slashes are normalized by `Inflector::slug()`.

### Manual slug override

If an admin sets `document.slug` in the backend, the value is still passed through `normalize()` on save. No slug may exceed 60 characters in the database or in generated URLs.

### Collision note

Structured slugs like `pm-7-2026` may repeat across documents. URLs remain unique because the numeric `id` prefix is always present (`/dokumen/123-pm-7-2026`).

---

## Enforcement points

All slug reads and writes go through `DocumentSlug`:

| Location | Change |
|----------|--------|
| `DocumentSlug::resolve()` | Normalize DB slug before return; if empty, call `fromDocument()` with row metadata |
| `Dokumen::getUrlSlug()` | Delegate to `DocumentSlug` (never return raw DB slug) |
| `DocumentSlugBehavior` | On insert/update: generate via `fromDocument()` when slug empty; always `normalize()` before save |
| `DocumentViewUrlRule::createUrl()` | No change needed once `resolve()` is fixed |
| View templates / OG canonical URLs | No template change once `getUrlSlug()` is fixed |

### Routing (unchanged)

- Pretty URL: `dokumen/<id:\d+>-<slug:[\w-]+>` → `dokumen/view`
- Fallbacks: `dokumen/view/<id>`, `dokumen/view?id=<id>`
- Redirect: existing 301 when path slug ≠ canonical slug (in `DokumenController::actionView`)

---

## Data migration

### New console command

`php yii document/normalize-slugs`

1. Select rows where `slug` IS NULL, `slug` = `''`, OR `CHAR_LENGTH(slug) > 60`
2. Regenerate slug via `fromDocument()` using `tipe_dokumen`, `judul`, `singkatan_jenis`, `nomor_peraturan`, `tahun_terbit`
3. Log updated count and a sample of before/after values

### Update existing command

`php yii document/backfill-slugs` — use `fromDocument()` instead of `fromJudul()` only.

### Deploy order

1. Deploy application code (normalize on read + write).
2. Run `php yii document/normalize-slugs` on production.
3. Verify previously blocked document URLs load.

---

## Limitations

**Cannot fix in application code:** Requests bearing over-long slugs already in the wild (bookmarks, search-engine indexes, external shares) may still be blocked by WAF before PHP executes. After deploy, the application stops generating long URLs; recovery of old indexed URLs depends on crawler refresh or infra-side URL rewriting (out of scope).

**Informational follow-up:** Request search-engine recrawl of sitemap after backfill.

### Edge cases

| Case | Behavior |
|------|----------|
| Peraturan missing `tahun_terbit` | Fall back to truncated judul slug |
| Very long putusan/monografi judul | 60-char cap; total path ~75 chars with ID |
| Admin sets slug > 60 chars | Truncated on save via `normalize()` |
| Empty judul | Slug `dokumen` |

---

## Testing

### Unit tests (`common/tests` or `common/tests/unit`)

`DocumentSlugTest` covering:

- Long judul → slug length ≤ 60
- Peraturan with full metadata → structured slug (e.g. `pm-7-2026`)
- Peraturan with missing `tahun_terbit` → judul fallback, ≤ 60
- `normalize()` on 200-char input → ≤ 60, no trailing hyphen
- Empty judul → `dokumen`

### Manual verification

1. Identify a document URL previously blocked by WAF.
2. Run `normalize-slugs`.
3. Confirm canonical URL loads and slug segment is ≤ 60 characters.

---

## Files to touch (implementation reference)

| File | Purpose |
|------|---------|
| `common/components/DocumentSlug.php` | `fromDocument()`, `normalize()`, lower `MAX_LENGTH` |
| `common/behaviors/DocumentSlugBehavior.php` | Use `fromDocument()`, always normalize |
| `frontend/models/Dokumen.php` | `getUrlSlug()` delegates to `DocumentSlug` |
| `console/controllers/DocumentController.php` | `actionNormalizeSlugs()`, update `actionBackfillSlugs()` |
| `common/tests/unit/components/DocumentSlugTest.php` | New unit tests |

No routing, controller, or view template changes required beyond model/component layer.
