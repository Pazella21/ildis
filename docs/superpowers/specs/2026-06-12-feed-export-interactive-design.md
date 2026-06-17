# Feed Export Interactive CLI — Design Spec

**Date:** 2026-06-12  
**Status:** Approved (brainstorming)  
**Scope:** Manual filtered document feed exports via new console command

---

## Problem

`FeedController::actionGenerateDocument()` exports every published document to `feed/document.json` with no filtering. Operators need ad-hoc exports filtered by document type, jenis peraturan (`document_type` hierarchy), and date range — without affecting the cron-driven full export.

## Decisions (from brainstorming)

| Question | Choice |
|----------|--------|
| Primary use case | **A** — Manual/ad-hoc only; cron unchanged |
| Output destination | **B** — Separate files under `feed/export/`, never overwrite `document.json` |
| Date field for range | **D** — Operator chooses at runtime (`updated_at`, `tanggal_pengundangan`, or `tanggal_penetapan`) |
| Jenis peraturan filter | **B** — By `document_type` hierarchy via `dokumen_type_id` |
| Architecture | **Approach A** — New `export-document` action + shared private methods; `generate-document` untouched |

---

## Commands

| Command | Purpose | Output |
|---------|---------|--------|
| `php yii feed/generate-document` | Cron / full export (unchanged) | `feed/document.json` |
| `php yii feed/export-document` | Manual filtered export | `feed/export/<name>.json` |

### `export-document` CLI options

| Flag | Alias | Description |
|------|-------|-------------|
| `--tipe` | `-t` | `tipe_dokumen`: 1=Peraturan, 2=Monografi, 3=Artikel, 4=Putusan |
| `--typeId` | | `document_type.id` — filter includes all descendants |
| `--dateField` | | `updated_at`, `tanggal_pengundangan`, or `tanggal_penetapan` |
| `--from` | | Start date (`Y-m-d`) |
| `--to` | | End date (`Y-m-d`) |
| `--output` | `-o` | Override auto-generated filename (resolved under `@feed/export/` only) |
| `--nonInteractive` | `-n` | Skip prompts; use flags only |
| `--yes` | `-y` | Skip confirmation prompt |

Follows the same interactive/non-interactive pattern as `console/controllers/UserController.php`.

### Interactive flow

1. Select tipe dokumen (or "Semua")
2. If Peraturan → select jenis peraturan from `document_type` tree (or "Semua")
3. Select date field (or "Tanpa filter tanggal")
4. Enter from/to dates if a field was chosen
5. Show summary with document count preview
6. Confirm `y/n` → generate

### Auto-generated output naming

Files are written under `@feed/export/`:

```
{tipe-slug}[-{type-slug}][-{from}_{to}].json
```

Examples:

- `peraturan.json` — all published Peraturan
- `peraturan-undang-undang_2024-01-01_2024-12-31.json`
- `monografi-updated_2025-06-01_2025-06-12.json`

Slug rules: lowercase, spaces → `-`, strip unsafe characters.

---

## Architecture

### Shared pipeline (refactored private methods)

```
buildQuery(filters?) → fetchDocuments() → enrichRows() → writeJson(path)
```

- `actionGenerateDocument()` — `buildQuery()` with no filters → `feed/document.json`
- `actionExportDocument()` — resolve filters → `feed/export/<name>.json`

### Base query

```php
DokumenJdih::find()
    ->alias('d')
    ->select([...])  // same fields as current implementation
    ->where(['d.is_publish' => 1])
```

### Filter application (export only)

| Filter | Query condition |
|--------|-----------------|
| `tipe` | `d.tipe_dokumen = :tipe` |
| `typeId` | `d.dokumen_type_id IN (:descendantIds)` via `DocumentType::findOne($id)->descendantTypeIds()` |
| `dateField` + `from` | `d.{field} >= :from` |
| `dateField` + `to` | `d.{field} <= :to` (date fields); `<= :to 23:59:59` for `updated_at` |

`dateField` validated against allowlist: `updated_at`, `tanggal_pengundangan`, `tanggal_penetapan`.

### Jenis peraturan picker (interactive, tipe = Peraturan)

- Load top-level `document_type` rows (same tree as backend peraturan forms)
- On selection, resolve via `DocumentType::descendantTypeIds()` so parent types include children
- Skipped when tipe ≠ Peraturan or operator selects "Semua"

### Enrichment (shared, unchanged)

- Lampiran map → `fileDownload` / `urlDownload`
- Abstrak → `urlAbstrak`
- `urlDetailPeraturan` via `urlManager`
- Static fields: `subjek`, `operasi`, `display`

### Atomic write (shared)

- Write to `{path}.tmp.{pid}` → `rename()` (existing pattern)
- `FileHelper::createDirectory()` for `feed/export/` on first use

---

## Error handling & validation

### Validation

| Input | Rule |
|-------|------|
| `tipe` | Must be 1–4 if provided |
| `typeId` | Must exist in `document_type` |
| `dateField` | Allowlist only; required if `from` or `to` is set |
| `from` / `to` | `Y-m-d` format; `from` ≤ `to` |
| `--nonInteractive` | All filters optional; exports all published if none given |

### Error behavior

| Situation | Behavior |
|-----------|----------|
| Zero matching documents | Warning to stdout, exit error, do not write file |
| JSON encode failure | Log via `Yii::error()`, clean up temp file |
| Write/rename failure | Log, clean up temp file |
| Invalid `typeId` or `dateField` | `Console::error()`, exit before querying |

### Output path safety

- `--output` resolved under `@feed/export/` only
- Reject paths containing `..` or absolute paths outside that directory

---

## Cron & deployment impact

**None.** These files stay unchanged:

- `docker/cron/crontab`
- `install.sh` healthchecks
- `feed/document.json` generation path

Only `generate-document` writes `feed/document.json`.

---

## Testing

Codeception unit tests (console or common):

1. `buildQuery()` with `tipe` filter adds correct `WHERE`
2. `typeId` filter expands to descendant IDs
3. Date range applies to chosen field
4. `resolveOutputPath()` produces expected slug from filter combination
5. `generate-document` regression — still writes to `feed/document.json` with no filters

---

## Files to modify

| File | Change |
|------|--------|
| `console/controllers/FeedController.php` | Refactor shared pipeline; add `actionExportDocument()`, options, interactive prompts |
| `console/tests/unit/FeedControllerTest.php` (new) | Unit tests for query building and path resolution |

## Files not modified

| File | Reason |
|------|--------|
| `docker/cron/crontab` | Cron keeps full export |
| `install.sh` | No deployment change |
| `feed/document.json` | Written only by `generate-document` |
