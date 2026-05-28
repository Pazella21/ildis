# Dokumen Pembentukan PUU — Design

**Date:** 2026-05-26
**Author:** Brainstorming session (Cursor agent + product owner)
**Status:** Draft, pending implementation plan

---

## 1. Goal

Add a new logical document group **"Dokumen Pembentukan PUU"** to the ILDIS application. The group contains eight Monografi sub-types (six already exist in seed data, one needs renaming, one needs to be created). Both the admin (backend) sidebar and the public (frontend) menu render this group dynamically from the database — adding or removing a child should require no code change.

The group is purely a presentation concept: documents continue to live in the existing polymorphic `document` table as Monografi (`tipe_dokumen = 2`), and existing CRUD, search, verification, and download flows are reused without modification.

## 2. Non-goals

- No new `tipe_dokumen` values.
- No new tables.
- No new columns on the `document` table.
- No new CRUD controller for the eight children — they reuse `MonografiController` via filtered URLs.
- No retirement of the half-built `PenyusunanController` / `PembahasanController` / `PerencanaanController` against the separate `rancangan` table. They remain untouched.
- No multilingual UI strings; Indonesian only.
- No new frontend design system; the listing view inherits Monografi styling.

## 3. Background

Discovered in `console/migrations/seed_data.sql`:

- The `document` table is polymorphic — Peraturan (`tipe_dokumen=1`), Monografi (2), Artikel (3), Putusan (4) all share it.
- The `document_type` table stores hierarchical sub-types keyed by `parent_id`.
- Six of the eight menu children from the design mockup **already exist** as Monografi sub-types (`parent_id = 2`): NASKAH AKADEMIK KEMENKUMHAM (76, renamed to `NASKAH AKADEMIK KEMENKUM`), NASKAH AKADEMIK (77), PENELITIAN HUKUM (78), PENGKAJIAN HUKUM (79), PENGKAJIAN KONSTITUSI (80), ANALISIS DAN EVALUASI (83), RANCANGAN PERATURAN PERUNDANG-UNDANGAN (84, with descendants 93–99).
- One existing row needs renaming: `Risalah Rapat` (147) → `RISALAH PEMBAHASAN`.
- One new row needs inserting: `PROGRAM PENYUSUNAN PUU`.

The codebase already has a parametric pattern for grouping document types (`parent_id` hierarchy), but no concept of a logical "group" that cuts across the existing hierarchy. This design introduces that concept with the smallest possible change.

## 4. Data model

### 4.1 Schema change

A single nullable string column on `document_type`:

```sql
ALTER TABLE `document_type`
  ADD COLUMN `document_group_label` VARCHAR(64) NULL DEFAULT NULL
    AFTER `singkatan`,
  ADD COLUMN `slug` VARCHAR(128) NULL DEFAULT NULL
    AFTER `document_group_label`,
  ADD INDEX `idx_document_group_label` (`document_group_label`),
  ADD UNIQUE INDEX `idx_document_type_slug` (`slug`);
```

`slug` is used for **public frontend URLs only** (e.g. `/dokumen-pembentukan-puu/penelitian-hukum`). Values are lowercase kebab-case, stable if an admin renames the display `name`, and unique across `document_type`. Backend admin menu links continue to use `documentTypeId` (numeric) in query strings — no need to expose slugs in the admin UI.

### 4.2 Tagged rows

After the migration runs, these eight `document_type` rows have `document_group_label = 'legislation_formation'`:

| id  | name                                       | source                            |
|-----|--------------------------------------------|-----------------------------------|
| 76  | `NASKAH AKADEMIK KEMENKUM`                 | existing, renamed + retagged      |
| 77  | `NASKAH AKADEMIK`                          | existing, retagged                |
| 78  | `PENELITIAN HUKUM`                         | existing, retagged                |
| 79  | `PENGKAJIAN HUKUM`                         | existing, retagged                |
| 80  | `PENGKAJIAN KONSTITUSI`                    | existing, retagged                |
| 83  | `ANALISIS DAN EVALUASI`                    | existing, retagged                |
| 84  | `RANCANGAN PERATURAN PERUNDANG-UNDANGAN`   | existing, retagged                |
| 147 | `RISALAH PEMBAHASAN`                       | existing row, renamed + retagged  |
| new | `PROGRAM PENYUSUNAN PUU`                   | new row, inserted by migration    |

The value `legislation_formation` is an English snake_case slug. Display label `Dokumen Pembentukan PUU` lives in a PHP enum (`common/components/DocumentGroup.php`).

Untagged Monografi sub-types (`BUKU HUKUM`, `KOMPENDIUM HUKUM`, `REFERENSI`, `LOKAKARYA`, etc.) are unaffected (`document_group_label` and `slug` remain `NULL`).

### 4.2.1 Slugs for tagged rows (frontend URLs)

| id  | name (DB) | `slug` |
|-----|-----------|--------|
| 76  | `NASKAH AKADEMIK KEMENKUM` | `naskah-akademik-kemenkum` |
| 77  | `NASKAH AKADEMIK` | `naskah-akademik` |
| 78  | `PENELITIAN HUKUM` | `penelitian-hukum` |
| 79  | `PENGKAJIAN HUKUM` | `pengkajian-hukum` |
| 80  | `PENGKAJIAN KONSTITUSI` | `pengkajian-konstitusi` |
| 83  | `ANALISIS DAN EVALUASI` | `analisis-dan-evaluasi` |
| 84  | `RANCANGAN PERATURAN PERUNDANG-UNDANGAN` | `rancangan-puu` |
| 147 | `RISALAH PEMBAHASAN` | `risalah-pembahasan` |
| new | `PROGRAM PENYUSUNAN PUU` | `program-penyusunan-puu` |

`rancangan-puu` is a short public slug for the long type name; it is **not** auto-derived from `name` so the URL stays readable. New rows inserted later should set `slug` explicitly (or via admin form); optional helper: `yii\helpers\Inflector::slug()` as a default when `slug` is empty on save.

### 4.3 Migration strategy

Two-layer rollout safe for both fresh and existing installations:

**Layer A — Yii migration** `console/migrations/mYYMMDD_HHMMSS_add_document_group_label_to_document_type.php`

```php
public function safeUp()
{
    $this->addColumn('{{%document_type}}', 'document_group_label',
        $this->string(64)->null()->defaultValue(null)->after('singkatan'));
    $this->addColumn('{{%document_type}}', 'slug',
        $this->string(128)->null()->defaultValue(null)->after('document_group_label'));
    $this->createIndex('idx_document_group_label', '{{%document_type}}', 'document_group_label');
    $this->createIndex('idx_document_type_slug', '{{%document_type}}', 'slug', true);

    $tagged = [
        76 => [
            'name' => 'NASKAH AKADEMIK KEMENKUM',
            'singkatan' => 'NASKAH AKADEMIK KEMENKUM',
            'document_group_label' => 'legislation_formation',
            'slug' => 'naskah-akademik-kemenkum',
        ],
        77 => ['document_group_label' => 'legislation_formation', 'slug' => 'naskah-akademik'],
        78 => ['document_group_label' => 'legislation_formation', 'slug' => 'penelitian-hukum'],
        79 => ['document_group_label' => 'legislation_formation', 'slug' => 'pengkajian-hukum'],
        80 => ['document_group_label' => 'legislation_formation', 'slug' => 'pengkajian-konstitusi'],
        83 => ['document_group_label' => 'legislation_formation', 'slug' => 'analisis-dan-evaluasi'],
        84 => ['document_group_label' => 'legislation_formation', 'slug' => 'rancangan-puu'],
    ];
    foreach ($tagged as $id => $attrs) {
        $this->update('{{%document_type}}', $attrs, ['id' => $id]);
    }

    // Documents store sub-type as jenis_peraturan string (not FK); keep in sync with rename.
    $this->update('{{%document}}',
        ['jenis_peraturan' => 'NASKAH AKADEMIK KEMENKUM'],
        ['jenis_peraturan' => 'NASKAH AKADEMIK KEMENKUMHAM']
    );

    $this->db->createCommand()->update('{{%document_type}}',
        ['name' => 'RISALAH PEMBAHASAN',
         'singkatan' => 'RISALAH PEMBAHASAN',
         'document_group_label' => 'legislation_formation',
         'slug' => 'risalah-pembahasan'],
        ['id' => 147, 'name' => 'Risalah Rapat']
    )->execute();

    $exists = (new \yii\db\Query())->from('{{%document_type}}')
        ->where(['name' => 'PROGRAM PENYUSUNAN PUU'])->exists($this->db);
    if (!$exists) {
        $this->insert('{{%document_type}}', [
            'second_id' => '2:148',
            'parent_id' => 2,
            'name' => 'PROGRAM PENYUSUNAN PUU',
            'singkatan' => 'PROGRAM PENYUSUNAN PUU',
            'document_group_label' => 'legislation_formation',
            'slug' => 'program-penyusunan-puu',
            'integrasi' => 1, 'created_by' => 1, 'updated_by' => 1,
        ]);
    }
}

public function safeDown()
{
    $this->delete('{{%document_type}}', ['name' => 'PROGRAM PENYUSUNAN PUU']);
    $this->update('{{%document_type}}',
        ['name' => 'Risalah Rapat',
         'singkatan' => 'Risalah Rapat',
         'document_group_label' => null],
        ['id' => 147]);
    $this->update('{{%document_type}}',
        ['document_group_label' => null, 'slug' => null],
        ['id' => [76, 77, 78, 79, 80, 83, 84]]);
    $this->update('{{%document_type}}',
        ['slug' => null],
        ['id' => 147]);
    $this->dropIndex('idx_document_type_slug', '{{%document_type}}');
    $this->dropIndex('idx_document_group_label', '{{%document_type}}');
    $this->dropColumn('{{%document_type}}', 'slug');
    $this->dropColumn('{{%document_type}}', 'document_group_label');
}
```

Idempotency guards:

- `addColumn` is non-destructive — existing rows get `NULL`.
- `update` calls produce the same end state on re-run.
- `PROGRAM PENYUSUNAN PUU` insert is gated by an existence check.
- The Risalah rename is gated on `name = 'Risalah Rapat'`, so if an admin already edited the name we leave it alone.
- Row 76 rename (`KEMENKUMHAM` → `KEMENKUM`) runs unconditionally on id 76; matching `document.jenis_peraturan` rows are updated in the same migration.

**Layer B — `seed_data.sql` update.** Modify the existing `INSERT INTO document_type` block (around line 13723) to include the new column value for the seven affected rows and append the new PROGRAM PENYUSUNAN PUU row. This keeps fresh installs accurate when bootstrapping from the SQL dump before migrations run.

Install order assumed: `seed_data.sql` then `php yii migrate`. The migration's idempotent guards make this safe.

## 5. Backend (admin) sidebar

### 5.1 New constant module

`common/components/DocumentGroup.php`

```php
<?php
namespace common\components;

class DocumentGroup
{
    const LEGISLATION_FORMATION = 'legislation_formation';

    public static function labels(): array
    {
        return [
            self::LEGISLATION_FORMATION => 'Dokumen Pembentukan PUU',
        ];
    }

    public static function label(string $slug): string
    {
        return self::labels()[$slug] ?? $slug;
    }
}
```

### 5.2 Model helpers

Both the backend sidebar and the frontend menu need to query `document_type` rows by group label. To avoid the frontend importing from `backend\models\` (a Yii2 advanced-template layering violation), introduce a shared model in the `common` layer:

`common/models/DocumentType.php`

```php
<?php
namespace common\models;

use yii\db\ActiveRecord;

class DocumentType extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'document_type';
    }

    public static function findByGroup(string $groupSlug): array
    {
        return static::find()
            ->where(['document_group_label' => $groupSlug])
            ->orderBy(['name' => SORT_ASC])
            ->all();
    }

    public static function findBySlugInGroup(string $slug, string $groupSlug): ?self
    {
        return static::findOne([
            'slug' => $slug,
            'document_group_label' => $groupSlug,
        ]);
    }

    public function descendantTypeIds(): array
    {
        $ids = [$this->id];
        $children = static::find()->where(['parent_id' => $this->id])->all();
        foreach ($children as $child) {
            $ids = array_merge($ids, $child->descendantTypeIds());
        }
        return $ids;
    }

    public function descendantTypeNames(): array
    {
        $names = [$this->name];
        $children = static::find()->where(['parent_id' => $this->id])->all();
        foreach ($children as $child) {
            $names = array_merge($names, $child->descendantTypeNames());
        }
        return $names;
    }

    public static function groupTypeNames(string $groupSlug): array
    {
        $names = [];
        foreach (self::findByGroup($groupSlug) as $root) {
            $names = array_merge($names, $root->descendantTypeNames());
        }
        return array_values(array_unique($names));
    }
}
```

Existing `backend\models\TipeDokumen` and `backend\models\JenisPeraturan` are unchanged — they continue to serve their current admin-form roles. The new `common\models\DocumentType` is used only for the menu-rendering and group-filtering paths described in §5.3 and §6.

Also add `document_group_label` and `slug` to `safeAttributes()` / validation rules on `backend\models\TipeDokumen` so admins can edit them via the existing form (`slug` unique, max 128, pattern `[\w-]+`).

### 5.3 How sub-type filtering works (important data shape)

Monografi documents do **not** reference their sub-type via a FK. The Monografi create form (`backend/views/monografi/_form-create.php` line 37) populates a `jenis_peraturan` dropdown whose options are `document_type.name` strings (filtered by `parent_id = 2`). The selected NAME is then stored as a plain string in the `document.jenis_peraturan` column.

(The `document.dokumen_type_id` FK column exists but is not populated by the current Monografi form — it is left for a future data-model migration that is out of scope here.)

Implication: to filter Monografi by a sub-type, we filter on `jenis_peraturan = '<exact name>'`, not on an id. The existing `MonografiSearch` already does `andFilterWhere(['like', 'jenis_peraturan', $this->jenis_peraturan])`, which is fuzzy. For an exact-match filter we add a new virtual attribute.

### 5.4 MonografiSearch extension

`backend/models/MonografiSearch.php`:

```php
public $documentTypeId; // virtual attribute, not a DB column

public function rules()
{
    return array_merge(parent::rules(), [
        [['documentTypeId'], 'integer'],
    ]);
}

public function search($params)
{
    // ...existing body...
    if ($this->documentTypeId) {
        $type = \common\models\DocumentType::findOne($this->documentTypeId);
        if ($type) {
            $query->andWhere(['jenis_peraturan' => $type->name]);
        } else {
            $query->andWhere('0=1'); // unknown type => empty result
        }
    }
    return $dataProvider;
}
```

This keeps the existing `jenis_peraturan LIKE` behavior for free-text search while adding a precise filter for menu-driven navigation.

### 5.5 Sidebar wiring

`backend/views/layouts/leftside.php` — after the RBAC menu items are assembled, append a dynamic group block:

```php
$puuItems = \common\models\DocumentType::findByGroup(
    \common\components\DocumentGroup::LEGISLATION_FORMATION
);
if ($puuItems && Yii::$app->user->can('/document-group/legislation-formation')) {
    $menuItems[] = [
        'label' => \common\components\DocumentGroup::label(
                       \common\components\DocumentGroup::LEGISLATION_FORMATION),
        'icon'  => 'file-text-o',
        'items' => array_map(fn($t) => [
            'label' => $t->name,
            'url'   => ['/monografi/index',
                        'MonografiSearch[documentTypeId]' => $t->id],
        ], $puuItems),
    ];
}
```

### 5.6 RBAC

Insert one new permission row in the `auth_item` table: `/document-group/legislation-formation`. Grant it to the same roles that currently have `/monografi/index`.

### 5.7 What clicking a child does

`/monografi/index?MonografiSearch[documentTypeId]=78` opens the existing Monografi grid pre-filtered to that sub-type (via the virtual attribute defined in §5.4). Create / Update / View / Delete actions reuse `MonografiController` as-is.

## 6. Frontend (public) menu

### 6.1 Menu file

`frontend/views/layouts/menu.php` — insert a new top-level dropdown next to the existing `Jenis Dokumen` (line 45):

```php
[
    'label' => \common\components\DocumentGroup::label(
                  \common\components\DocumentGroup::LEGISLATION_FORMATION),
    'url'   => '#',
    'options' => ['class' => 'dropdown'],
    'items' => array_map(fn($t) => [
        'label' => ucwords(strtolower($t->name)),
        'url'   => ['dokumen/legislation-formation', 'slug' => $t->slug],
    ], \common\models\DocumentType::findByGroup(
           \common\components\DocumentGroup::LEGISLATION_FORMATION)),
],
```

`ucwords(strtolower(...))` is used because seed data is ALL-CAPS while the design uses Title Case.

### 6.2 New controller action

`frontend/controllers/DokumenController.php`

```php
public function actionLegislationFormation($slug = null)
{
    $group = \common\components\DocumentGroup::LEGISLATION_FORMATION;
    $type = $slug
        ? \common\models\DocumentType::findBySlugInGroup($slug, $group)
        : null;

    if ($slug && !$type) {
        throw new \yii\web\NotFoundHttpException(
            'Tipe dokumen tidak ditemukan.'
        );
    }

    $searchModel = new \frontend\models\DokumenSearch();
    $typeNames = $type
        ? $type->descendantTypeNames()
        : \common\models\DocumentType::groupTypeNames(
              \common\components\DocumentGroup::LEGISLATION_FORMATION
          );
    $dataProvider = $searchModel->searchByTypeNames(
        $typeNames,
        Yii::$app->request->queryParams
    );

    return $this->render('index-legislation-formation', [
        'searchModel' => $searchModel,
        'dataProvider' => $dataProvider,
        'currentType' => $type,
    ]);
}
```

Add corresponding helpers:

`common\models\DocumentType::descendantTypeNames()` — same shape as `descendantTypeIds()` (§5.2) but returns the `name` strings (used to match `document.jenis_peraturan`).

`common\models\DocumentType::groupTypeNames(string $groupSlug)` — returns the `name` strings of all rows tagged with the given group label, plus the names of their descendants (so the group landing page picks up Rancangan PUU children too).

`frontend\models\DokumenSearch::searchByTypeNames(array $names, array $params)`:

```php
public function searchByTypeNames(array $names, array $params)
{
    $query = self::find()
        ->where(['tipe_dokumen' => self::TYPE_MONOGRAFI])
        ->andWhere(['is_publish' => 1])
        ->andWhere(['jenis_peraturan' => $names]); // exact match against names

    $this->load($params);
    // ...apply free-text filters from $params as the existing search() does...
    return new ActiveDataProvider(['query' => $query]);
}
```

Access rules: this action is **public** (no `IsGuest` deny). Add it to the access filter alongside existing public actions.

### 6.3 Hierarchical types — Rancangan PUU

Row 84 (`RANCANGAN PERATURAN PERUNDANG-UNDANGAN`) has children 93–99 (Rancangan Perda Provinsi, Kabupaten, Kota, Peraturan Gubernur, Bupati, Walikota, Perda generic). When a user clicks "Rancangan PUU" the listing must include documents filed under the parent name **plus all descendant names**. `descendantTypeNames()` walks `parent_id` recursively and collects names instead of ids.

### 6.4 URL rules

`frontend/config/main.php`:

```php
'dokumen-pembentukan-puu' => 'dokumen/legislation-formation',
'dokumen-pembentukan-puu/<slug:[\w-]+>' => 'dokumen/legislation-formation',
```

Public URL examples (slug-based, no numeric ids in the path):

| Menu child                    | URL |
|-------------------------------|-----|
| Group landing (all 8)         | `/dokumen-pembentukan-puu` |
| Naskah Akademik                 | `/dokumen-pembentukan-puu/naskah-akademik` |
| Naskah Akademik Kemenkum         | `/dokumen-pembentukan-puu/naskah-akademik-kemenkum` |
| Rancangan PUU (+ descendants)   | `/dokumen-pembentukan-puu/rancangan-puu` |
| Penelitian Hukum                | `/dokumen-pembentukan-puu/penelitian-hukum` |
| Pengkajian Hukum                | `/dokumen-pembentukan-puu/pengkajian-hukum` |
| Pengkajian Konstitusi           | `/dokumen-pembentukan-puu/pengkajian-konstitusi` |
| Analisis Dan Evaluasi           | `/dokumen-pembentukan-puu/analisis-dan-evaluasi` |
| Program Penyusunan PUU          | `/dokumen-pembentukan-puu/program-penyusunan-puu` |
| Risalah Pembahasan              | `/dokumen-pembentukan-puu/risalah-pembahasan` |

`findBySlugInGroup()` ensures a slug that belongs to another group (or an untagged type) returns 404, not a cross-group leak.

### 6.5 New view

`frontend/views/dokumen/index-legislation-formation.php` mirrors `index-monografi.php`: same card grid, same pagination, same search bar. Section title comes from `$currentType->name` when a child is selected, else `Dokumen Pembentukan PUU`.

### 6.6 Detail view

Reuses the existing `dokumen/view` route. No new detail view needed because the documents are Monografi-shaped.

## 7. Code surface summary

### New files

| File | Purpose | Approx. size |
|------|---------|--------------|
| `console/migrations/mYYMMDD_HHMMSS_add_document_group_label_to_document_type.php` | Schema + idempotent data tagging | ~70 LOC |
| `common/components/DocumentGroup.php` | Constants & display labels | ~30 LOC |
| `common/models/DocumentType.php` | Shared AR model with `findByGroup()` + `descendantTypeIds()` (used by both backend menu and frontend menu/controller) | ~40 LOC |
| `frontend/views/dokumen/index-legislation-formation.php` | Public listing view | ~50 LOC |

### Modified files

| File | Change |
|------|--------|
| `console/migrations/seed_data.sql` | Update INSERT VALUES for the 7 tagged rows; append the new PROGRAM PENYUSUNAN PUU row |
| `backend/models/TipeDokumen.php` | Add `document_group_label` + `slug` to safe attributes + rules (unique slug) |
| `backend/models/MonografiSearch.php` | Add virtual attribute `documentTypeId` that resolves to an exact `jenis_peraturan = (name)` filter (see §5.4) |
| `frontend/models/DokumenSearch.php` | Add `searchByTypeNames(array $names, array $params)` (see §6.2) |
| `backend/views/layouts/leftside.php` | Append dynamic group block after RBAC menu |
| `frontend/views/layouts/menu.php` | Insert new dropdown next to "Jenis Dokumen" |
| `frontend/controllers/DokumenController.php` | Add `actionLegislationFormation()` + public access rule |
| `frontend/config/main.php` | Add two URL rules |

### Unchanged (intentionally)

- `document` table schema.
- `MonografiController` actions.
- `CatatanVerifikasiController` publish/unpublish flow.
- `PenyusunanController` / `PembahasanController` / `PerencanaanController` against the `rancangan` table.
- Existing RBAC menu rows for Monografi.
- `frontend/views/dokumen/view-monografi.php` (detail view reused as-is).

## 8. Testing plan

### Functional (Codeception)

1. **Backend menu visibility per role**
   - User with `/monografi/index` permission and `/document-group/legislation-formation` sees the group with 8 children.
   - User without `/document-group/legislation-formation` does not see the group even if they have Monografi access.
2. **Child click filters Monografi list**
   - Click "Penelitian Hukum" → URL contains `MonografiSearch[documentTypeId]=78` → grid shows only `tipe_dokumen=2 AND jenis_peraturan='PENELITIAN HUKUM'` rows.
3. **Frontend group landing**
   - GET `/dokumen-pembentukan-puu` returns 200 and renders listing of all 8 tagged sub-types (jenis_peraturan IN the group's name set).
4. **Frontend child page (slug)**
   - GET `/dokumen-pembentukan-puu/penelitian-hukum` returns 200 with `jenis_peraturan='PENELITIAN HUKUM'` docs only.
5. **Rancangan PUU descendants (slug)**
   - GET `/dokumen-pembentukan-puu/rancangan-puu` returns docs with `jenis_peraturan IN ('RANCANGAN PERATURAN PERUNDANG-UNDANGAN', 'RANCANGAN PERATURAN DAERAH', …)`.
6. **Untagged or unknown slug returns 404**
   - GET `/dokumen-pembentukan-puu/buku-hukum` (no such slug in group) returns 404.
   - GET `/dokumen-pembentukan-puu/78` (numeric id in path, not a slug) returns 404 unless a row is deliberately given slug `78` (we do not).
7. **DocumentTypeId validator rejects unknown IDs**
   - `MonografiSearch[documentTypeId]=999999` (non-existent row) returns an empty grid, not a 500.

### Migration

- `php yii migrate` against a DB that has seed_data.sql already applied: confirms idempotency.
- `php yii migrate/down 1`: confirms the column and inserted row are removed; the renamed row is restored to `Risalah Rapat`.

### Manual smoke

- Backend: create a new document under each of the 8 sub-types; verify it shows up in the filtered Monografi grid.
- Frontend: verify each menu link loads, search and pagination work, and clicking a document opens the detail view.

## 9. Open questions

1. **Naskah Akademik double row.** The menu will render both row 76 (`NASKAH AKADEMIK KEMENKUM`) and row 77 (`NASKAH AKADEMIK`) as separate sub-items. The mockup shows one entry `Naskah Akademik/Keterangan/Penjelasan/Urgensi/Konsepsi`. Default behavior: keep both. Alternative: tag only row 77 and rename it. Decide after seeing the rendered menu.

2. **Sort order.** Default is alphabetical (`ORDER BY name ASC`). Mockup order is workflow-based, not alphabetical. If alphabetical proves confusing, add a `sort_order` column on `document_type` or hardcode order in `DocumentGroup` config.

3. **Display label translation.** `ucwords(strtolower(...))` makes `NASKAH AKADEMIK` render as `Naskah Akademik`. Verify acronyms like `PUU` aren't accidentally lower-cased in transit. If they are, add an exception list.

4. **`document.dokumen_type_id` is unused today.** The `document` table has both `dokumen_type_id` (FK, never populated by the current Monografi form) and `jenis_peraturan` (string name, populated by the form). This design filters on `jenis_peraturan` because it is the actively-used column. A future cleanup may want to backfill `dokumen_type_id` and switch all filters to use the FK — that work is out of scope here.

## 10. Follow-ups (out of scope, but on the radar)

- Dedicated "Manage Document Groups" admin page with drag-drop reassignment between groups.
- Promote `document_group_label` from string to FK (`document_group` table) if a second group is added.
- Public breadcrumbs reflecting the group on the detail view.
- Per-child SEO meta tags on listing pages.
- Sort-order column if alphabetical proves awkward in production.
- Wire the existing `rancangan` table content into the same group via a view or join if stakeholders later want drafting-workflow records to surface alongside catalog records.

## 11. Effort estimate

- Migration + seed_data.sql update: 1 hr
- `DocumentGroup` component + `common\models\DocumentType` + `TipeDokumen` safeAttributes patch: 1 hr
- Backend sidebar wiring + `MonografiSearch` virtual attribute + RBAC permission: 1 hr
- Frontend menu + `actionLegislationFormation` + `DokumenSearch::searchByTypeNames` + view + URL rules: 2 hr
- Test coverage (Codeception + migration round-trip): 1 hr

**Total: ~6 hours of focused work** for a working end-to-end feature.
