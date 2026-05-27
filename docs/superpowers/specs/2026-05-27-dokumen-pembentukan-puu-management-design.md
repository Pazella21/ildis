# Dokumen Pembentukan PUU — Backend Management Page Design

**Date:** 2026-05-27
**Author:** Brainstorming session
**Status:** Draft, pending implementation plan
**Depends on:** `2026-05-26-dokumen-pembentukan-puu-design.md` (already implemented)

---

## 1. Goal

Create a dedicated backend (admin) CRUD page for managing Dokumen Pembentukan PUU documents. The page mirrors the existing Monografi Hukum management but is scoped to `legislation_formation` document types only, with full label rebranding to "Dokumen Pembentukan PUU".

All labels, titles, breadcrumbs, and column headers say "Dokumen Pembentukan PUU" — no "Monografi" labels are shown to the user. Under the hood, documents are still Monografi records (`tipe_dokumen = 2`) in the `document` table.

## 2. Non-goals

- No changes to the frontend (public) site — that was covered in the prior spec.
- No new database tables or columns — reuses the existing `document` table with `jenis_peraturan` filtering.
- No new document types — the 9 tagged `document_type` rows already exist.
- No changes to the existing Monografi controller/views — they remain untouched.
- No sub-entity tabs beyond Pengarang, Subyek, Lampiran, and Eksemplar (the 4 essential ones). Peraturan Terkait, Dokumen Terkait, Hasil Uji Materi, and Status are excluded.

## 3. Architecture

### 3.1 Controller: `DokumenPembentukanPuuController`

**File:** `backend/controllers/DokumenPembentukanPuuController.php`

Mirrors `MonografiController` but applies a permanent scope filter. Every query adds:

```php
$query->andWhere(['tipe_dokumen' => 2])
      ->andWhere(['jenis_peraturan' => DocumentType::groupTypeNames(DocumentGroup::LEGISLATION_FORMATION)]);
```

This ensures only `legislation_formation` monografi documents are ever visible, regardless of how the user arrives at the action.

**Actions:**

| Action | Purpose |
|--------|---------|
| `actionIndex` | Grid listing with `DokumenPembentukanPuuSearch` |
| `actionView($id)` | Detail page with 5 tabs: Data Utama, Pengarang (T.E.U), Subyek, Lampiran, Eksemplar |
| `actionCreate` | Create form with `jenis_peraturan` scoped to `legislation_formation` types only |
| `actionUpdate($id)` | Update form, same scope |
| `actionDelete($id)` | Delete with FK constraint check |
| `actionInactive($id)` | Set `is_publish = 0` (unverify) |
| Sub-CRUD actions | Pengarang, Subyek, Lampiran, Eksemplar — same models as Monografi |

The controller delegates to the same models (`DataPengarang`, `DataSubyek`, `DataLampiran`, `Eksemplar`, `Pengarang`) that MonografiController uses. The sub-entity views are rendered from the same `views/monografi/` subdirectories (teu/, subyek/, lampiran/, eksemplar/) since they are model-agnostic — they work on `$id` (document ID) and don't reference "Monografi" in their content.

### 3.2 Search Model: `DokumenPembentukanPuuSearch`

**File:** `backend/models/DokumenPembentukanPuuSearch.php`

Extends the same pattern as `MonografiSearch` but with two critical differences:

1. **Base query** scopes to `legislation_formation` types only:
   ```php
   $groupNames = DocumentType::groupTypeNames(DocumentGroup::LEGISLATION_FORMATION);
   $query = Monografi::find()
       ->where(['tipe_dokumen' => 2])
       ->andWhere(['jenis_peraturan' => $groupNames]);
   ```

2. **`jenis_peraturan` filter dropdown** is scoped to `legislation_formation` types only (via `DocumentType::findByGroup()`), not all `parent_id = 2` types.

3. **Virtual `documentTypeId`** attribute works identically to `MonografiSearch` — resolves to an exact `jenis_peraturan` match.

### 3.3 Views: `backend/views/dokumen-pembentukan-puu/`

**Directory:** `backend/views/dokumen-pembentukan-puu/`

Cloned from `views/monografi/` with the following changes per file:

| Source | Target | Changes |
|--------|--------|---------|
| `index.php` | `index.php` | Title: "Dokumen Pembentukan PUU". Panel heading: "Data Dokumen Pembentukan PUU". `jenis_peraturan` column label: "Jenis Dokumen". Filter: `DocumentType::findByGroup()` instead of `JenisPeraturan::find()->where(['parent_id'=>2])`. Action column URLs: `dokumen-pembentukan-puu/*`. Breadcrumb label: "Dokumen Pembentukan PUU". |
| `view.php` | `view.php` | Breadcrumb label: "Dokumen Pembentukan PUU". Tabs: keep Data Utama, Pengarang (T.E.U), Subyek, Lampiran, Eksemplar. Remove Log User tab (not needed). All sub-view render paths point to `monografi/` subdirectories (teu/, subyek/, lampiran/, eksemplar/) since they are model-agnostic. |
| `_detail.php` | `_detail.php` | Label: "Jenis Dokumen" instead of "Jenis Monografi". Label: "Judul Dokumen" instead of "Judul Monografi". Back button URL: `['index']` (relative to this controller). |
| `create.php` | `create.php` | Title: "Tambah Data Dokumen Pembentukan PUU". Breadcrumb: "Dokumen Pembentukan PUU". |
| `_form-create.php` | `_form-create.php` | `jenis_peraturan` dropdown scoped to `DocumentType::findByGroup(DocumentGroup::LEGISLATION_FORMATION)` mapped by `name, name`. Breadcrumb label: "Dokumen Pembentukan PUU". |
| `_form-update.php` | `_form-update.php` | Same scoping as `_form-create.php`. Breadcrumb label: "Dokumen Pembentukan PUU". |
| `update.php` | `update.php` | Title: "Ubah Data Dokumen Pembentukan PUU". Breadcrumb: "Dokumen Pembentukan PUU". |

**Sub-entity views** (teu/, subyek/, lampiran/, eksemplar/) are **NOT copied** — the controller renders them from the existing `views/monografi/` paths since they contain no "Monografi" labels and work on `$id` alone.

### 3.4 Sidebar Update

**File:** `backend/views/layouts/leftside.php` (already modified in prior work)

Change the PUU children URLs from `/monografi/index` to `/dokumen-pembentukan-puu/index`:

```php
$puuChildren = array_map(static function (DocumentType $t) {
    return [
        'label' => $t->name,
        'url' => [
            '/dokumen-pembentukan-puu/index',
            'DokumenPembentukanPuuSearch[documentTypeId]' => $t->id,
        ],
    ];
}, $puuTypes);
```

### 3.5 RBAC

**Migration file:** `console/migrations/m260527_120000_add_dokumen_pembentukan_puu_rbac.php`

Create new route permissions mirroring `MonografiController` actions:

| Permission | Description |
|------------|-------------|
| `/dokumen-pembentukan-puu/index` | View listing |
| `/dokumen-pembentukan-puu/create` | Create new document |
| `/dokumen-pembentukan-puu/view` | View detail |
| `/dokumen-pembentukan-puu/update` | Update document |
| `/dokumen-pembentukan-puu/delete` | Delete document |
| `/dokumen-pembentukan-puu/inactive` | Unverify document |
| `/dokumen-pembentukan-puu/tambah-pengarang` | Add author |
| `/dokumen-pembentukan-puu/ubah-pengarang` | Edit author |
| `/dokumen-pembentukan-puu/hapus-pengarang` | Delete author |
| `/dokumen-pembentukan-puu/view-pengarang` | View author detail |
| `/dokumen-pembentukan-puu/tambah-subyek` | Add subject |
| `/dokumen-pembentukan-puu/ubah-subyek` | Edit subject |
| `/dokumen-pembentukan-puu/hapus-subyek` | Delete subject |
| `/dokumen-pembentukan-puu/view-subyek` | View subject detail |
| `/dokumen-pembentukan-puu/tambah-lampiran` | Add attachment |
| `/dokumen-pembentukan-puu/ubah-lampiran` | Edit attachment |
| `/dokumen-pembentukan-puu/hapus-lampiran` | Delete attachment |
| `/dokumen-pembentukan-puu/tambah-eksemplar` | Add copy |
| `/dokumen-pembentukan-puu/ubah-eksemplar` | Edit copy |
| `/dokumen-pembentukan-puu/hapus-eksemplar` | Delete copy |

Also add a wildcard permission `/dokumen-pembentukan-puu/*`.

**Role assignments:**

| Role | Permissions |
|------|------------|
| `superadmin` | `/dokumen-pembentukan-puu/*` |
| `pustakawan` | All individual permissions (no wildcard, no delete) |

**Menu table entry:**

Add a row to the `menu` table under parent "Dokumen Hukum" (id=16):

```sql
INSERT INTO `menu` (`name`, `parent`, `route`, `order`, `data`)
VALUES ('Dokumen Pembentukan PUU', 16, '/dokumen-pembentukan-puu/index', 15, X'66612066612d66696c652d746578742d6f');
```

This makes it appear as a child of "Dokumen Hukum" in the RBAC menu alongside Peraturan, Monografi Hukum, etc. The dynamic sub-items (individual document types) remain in the sidebar as they are now — this menu entry provides the route-level permission link.

> **Note:** The dynamic sub-items (Naskah Akademik, Penelitian Hukum, etc.) are NOT menu table entries. They are rendered dynamically via `DocumentType::findByGroup()`. The menu entry above is only for RBAC permission routing.

## 4. Data flow

### 4.1 Index grid

```
User clicks sidebar sub-item (e.g. "Penelitian Hukum")
  → URL: /dokumen-pembentukan-puu/index?DokumenPembentukanPuuSearch[documentTypeId]=78
  → DokumenPembentukanPuuSearch::search()
    → Base: WHERE tipe_dokumen=2 AND jenis_peraturan IN (groupTypeNames)
    → Plus: if documentTypeId=78, AND jenis_peraturan='PENELITIAN HUKUM'
  → Grid shows filtered documents
  → jenis_peraturan dropdown: only legislation_formation types
```

### 4.2 Create

```
User clicks "Tambah Data"
  → /dokumen-pembentukan-puu/create
  → Form pre-sets tipe_dokumen = 2 (hidden field)
  → jenis_peraturan dropdown: DocumentType::findByGroup(LEGISLATION_FORMATION)
  → On save: Monografi model stores record with tipe_dokumen=2 and the selected jenis_peraturan name
```

### 4.3 View detail

```
User clicks "View" on a document
  → /dokumen-pembentukan-puu/view?id=123
  → Controller loads Monografi model by id
  → Verifies jenis_peraturan is in groupTypeNames (throws 404 if not)
  → Renders view with 5 tabs: Data Utama, Pengarang, Subyek, Lampiran, Eksemplar
```

### 4.4 Scoping enforcement

The controller must reject documents that don't belong to the legislation_formation group:

```php
private function findModel($id)
{
    $model = Monografi::findOne($id);
    if (!$model || !in_array($model->jenis_peraturan, DocumentType::groupTypeNames(DocumentGroup::LEGISLATION_FORMATION))) {
        throw new NotFoundHttpException('Dokumen tidak ditemukan.');
    }
    return $model;
}
```

This prevents URL manipulation (`/dokumen-pembentukan-puu/view?id=999`) from accessing a regular Monografi document.

## 5. Code surface summary

### New files

| File | Purpose | Approx. LOC |
|------|---------|-------------|
| `backend/controllers/DokumenPembentukanPuuController.php` | Full CRUD controller + 4 sub-entity CRUD sections | ~500 |
| `backend/models/DokumenPembentukanPuuSearch.php` | Search model with group-scoped base query | ~150 |
| `backend/views/dokumen-pembentukan-puu/index.php` | Grid listing view | ~250 |
| `backend/views/dokumen-pembentukan-puu/view.php` | Detail view with 5 tabs | ~90 |
| `backend/views/dokumen-pembentukan-puu/_detail.php` | Detail attributes partial | ~105 |
| `backend/views/dokumen-pembentukan-puu/create.php` | Create page wrapper | ~18 |
| `backend/views/dokumen-pembentukan-puu/_form-create.php` | Create form | ~195 |
| `backend/views/dokumen-pembentukan-puu/update.php` | Update page wrapper | ~18 |
| `backend/views/dokumen-pembentukan-puu/_form-update.php` | Update form | ~195 |
| `console/migrations/m260527_120000_add_dokumen_pembentukan_puu_rbac.php` | RBAC permissions + menu entry | ~80 |

### Modified files

| File | Change |
|------|--------|
| `backend/views/layouts/leftside.php` | Change PUU sidebar URLs from `/monografi/index` to `/dokumen-pembentukan-puu/index` and search model param from `MonografiSearch` to `DokumenPembentukanPuuSearch` |

### Unchanged

- `MonografiController.php` — untouched
- `MonografiSearch.php` — untouched
- `Monografi.php` model — untouched
- All `views/monografi/` views — untouched
- Sub-entity views (teu/, subyek/, lampiran/, eksemplar/) — shared, not copied
- `common/models/DocumentType.php` — already has `groupTypeNames()`
- `common/components/DocumentGroup.php` — unchanged

## 6. Testing plan

### Manual smoke tests

1. **Sidebar navigation:** Click each PUU sub-item → opens filtered grid with only that type.
2. **Index grid:** Shows only `legislation_formation` documents. Filter dropdown only shows PUU types. Grid columns say "Jenis Dokumen" and "Judul Dokumen".
3. **Create:** Jenis Peraturan dropdown only shows 9 PUU types. Created document appears in the PUU grid AND in Monografi grid (since both share `tipe_dokumen=2`).
4. **Update:** Edit existing PUU document → changes persist.
5. **View:** Detail page shows 5 tabs, all work. Sub-entity CRUD (add/remove pengarang, subyek, lampiran, eksemplar) works.
6. **Delete:** Deletes document (or shows error if FK constraint).
7. **Scope enforcement:** Visiting `/dokumen-pembentukan-puu/view?id=<regular-monografi-id>` returns 404.
8. **Unverify:** Clicking "Unverify" on a published document sets `is_publish = 0`.
9. **RBAC:** User without `/document-group/legislation-formation` permission does not see the sidebar items. User without `/dokumen-pembentukan-puu/index` gets 403 on the route.

## 7. Effort estimate

- Controller (index/view/create/update/delete/inactive + sub-CRUD): 4 hr
- Search model: 1 hr
- Views (index, view, detail, create, update, form-create, form-update): 3 hr
- RBAC migration + menu entry + sidebar URL update: 1 hr
- Testing and fixes: 1 hr

**Total: ~10 hours**