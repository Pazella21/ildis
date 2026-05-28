# Footer CMS Design Spec

**Date:** 2026-05-28
**Status:** Approved

## Problem

The ILDIS footer has hardcoded link sections (LAYANAN: Pengaduan/Penilaian, TENTANG: Beranda/FAQ/Kontak Kami) and social media icons (Facebook, Instagram, Twitter/X, YouTube). Admins cannot add, remove, reorder, or edit these without changing PHP templates. Social media URLs are managed through `frontend_config` key-value rows with hardcoded IDs — fragile and disconnected from the footer structure.

## Solution

Add two new tables (`footer_section`, `footer_link`) with full backend CRUD, update the footer template to query them dynamically, and provide a migration script that carries existing `frontend_config` social media data forward.

## Data Model

### `footer_section`

| Column | Type | Description |
|--------|------|-------------|
| `id` | PK auto-increment | |
| `title` | varchar(255) | Section heading (e.g., "LAYANAN", "TENTANG") |
| `type` | enum('nav', 'social') | Navigation section vs. social media section |
| `sort_order` | int | Display order of sections |
| `status` | tinyint(1) | 1 = active, 0 = inactive |
| `created_at` | datetime | |
| `updated_at` | datetime | |

### `footer_link`

| Column | Type | Description |
|--------|------|-------------|
| `id` | PK auto-increment | |
| `section_id` | int FK → footer_section.id | Parent section |
| `label` | varchar(255) | Display text (e.g., "Pengaduan") |
| `url` | varchar(500) | Link target |
| `icon_class` | varchar(100) nullable | Bootstrap Icons class for social links (e.g., "bi bi-facebook") |
| `sort_order` | int | Display order within section |
| `status` | tinyint(1) | 1 = active, 0 = inactive |
| `open_in_new_tab` | tinyint(1) | Open link in new tab |
| `created_at` | datetime | |
| `updated_at` | datetime | |

## Seed Data

Migrated from current hardcoded content:

- Section "LAYANAN" (type=nav, order=1)
  - "Pengaduan" → `#`
  - "Penilaian" → `#`
- Section "TENTANG" (type=nav, order=2)
  - "Beranda" → `/`
  - "FAQ" → `#`
  - "Kontak Kami" → `#`
- Section "MEDIA SOSIAL" (type=social, order=3)
  - "Facebook" → value from `frontend_config` ID 13, icon `bi bi-facebook`
  - "Instagram" → value from `frontend_config` ID 15, icon `bi bi-instagram`
  - "Twitter/X" → `#` (currently hardcoded), icon `bi bi-twitter-x`
  - "YouTube" → value from `frontend_config` ID 14, icon `bi bi-youtube`

## Admin Interface

- **`FooterSectionController`** — CRUD for sections. Index shows title, type badge, sort order, link count, status, actions (View/Edit/Links/Delete).
- **`FooterLinkController`** — CRUD for links. Filter by section. Fields: section (dropdown), label, URL, icon_class (with preview for social type), open_in_new_tab checkbox, sort_order, status.
- **Admin sidebar:** New "Footer" menu group with "Sections" and "Links" items.
- Sorting via integer sort_order fields (consistent with existing admin patterns).

## Frontend Rendering

- Footer template eagerly loads active sections with active links via `FooterSection::find()->where(['status' => 1])->orderBy(['sort_order' => SORT_ASC])->with(['activeLinks'])->all()`.
- `nav`-type sections render in middle columns with `<h6>` heading + `<ul>` of links.
- `social`-type sections render as icon links in the bottom-right.
- Same HTML structure, same CSS classes (`footer-link`, `footer-social`, `bphn-footer`) — visually identical output.
- Institution info (name, address, phone, email, logo) remains in `frontend_config` — no changes.
- Links with `open_in_new_tab = 1` get `target="_blank" rel="noopener noreferrer"`.
- Yii2 query caching (3600s) with cache invalidation on section/link CRUD updates.

## Error Handling & Edge Cases

- **Zero active sections:** Fall back to current hardcoded footer content.
- **Empty section:** Section heading hidden if no active links.
- **Missing icon_class:** Nav type ignores it; social type falls back to `bi bi-link-45deg`.
- **Twitter/X:** `icon_class = bi bi-twitter-x`. If Bootstrap Icons version doesn't include it, handle with inline SVG in template (same as current).

## Migration Script

- `safeUp()`: Create `footer_section` and `footer_link` tables → insert LAYANAN section + 2 links → insert TENTANG section + 3 links → insert MEDIA SOSIAL section → read `frontend_config` IDs 13, 14, 15 and insert as social links → insert Twitter/X row with `#` URL.
- `safeDown()`: Drop `footer_link`, then `footer_section`. Old `frontend_config` rows remain unchanged (not deleted).
- Existing `frontend_config` social media rows (IDs 13-15) become dormant but are not removed — safe for rollback.

## Scope Exclusions

- Institution info block, visitor analytics strip, copyright line — all remain as-is via `frontend_config`.
- No changes to `FrontendConfigController` or existing admin views.