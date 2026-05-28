# Dokumen Pembentukan PUU Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a data-driven **Dokumen Pembentukan PUU** document group (nine `document_type` rows tagged `legislation_formation`) with dynamic admin sidebar + public frontend menu, slug URLs, and filtered Monografi listings — without new tables or Monografi controller clones.

**Architecture:** Extend `document_type` with `document_group_label` + `slug`; shared helpers on `common\models\DocumentType`; backend filters via `MonografiSearch[documentTypeId]` → `jenis_peraturan` exact match; frontend lists via `DokumenSearch::searchByTypeNames()` and `actionLegislationFormation($slug)`.

**Tech Stack:** Yii 2 Advanced (PHP 7.4+), MySQL/MariaDB, Codeception, mdm\admin RBAC.

**Spec:** `docs/superpowers/specs/2026-05-26-dokumen-pembentukan-puu-design.md`

---

## File map

| Action | Path | Responsibility |
|--------|------|----------------|
| Create | `console/migrations/m260527_000000_add_document_group_label_to_document_type.php` | Schema + data + RBAC permission |
| Create | `common/components/DocumentGroup.php` | Group slug constants + Indonesian labels |
| Create | `common/models/DocumentType.php` | `findByGroup`, `findBySlugInGroup`, descendant name helpers |
| Modify | `frontend/models/DocumentType.php` | `extends \common\models\DocumentType` (keep existing import in `DokumenController`) |
| Modify | `backend/models/TipeDokumen.php` | Rules/labels for new columns |
| Modify | `backend/models/MonografiSearch.php` | Virtual `documentTypeId` filter |
| Modify | `backend/views/layouts/leftside.php` | Third sidebar menu block for PUU group |
| Modify | `frontend/models/DokumenSearch.php` | `searchByTypeNames()` |
| Modify | `frontend/controllers/DokumenController.php` | `actionLegislationFormation()` |
| Create | `frontend/views/dokumen/index-legislation-formation.php` | Public listing (copy from `index-monografi.php`) |
| Modify | `frontend/views/layouts/menu.php` | New dropdown |
| Modify | `frontend/config/main.php` | Pretty URL rules |
| Modify | `console/migrations/seed_data.sql` | Layer B: column values on `document_type` INSERT block (~line 13723) |
| Create | `common/tests/unit/models/DocumentTypeTest.php` | Unit tests for name/slug helpers |
| Create | `frontend/tests/functional/DokumenPembentukanPuuCest.php` | HTTP smoke for public routes |

---

### Task 1: Database migration (schema + data + RBAC)

**Files:**
- Create: `console/migrations/m260527_000000_add_document_group_label_to_document_type.php`

- [ ] **Step 1: Generate migration file**

Run:
```bash
php yii migrate/create add_document_group_label_to_document_type --migrationPath=@console/migrations
```

Rename output to `m260527_000000_add_document_group_label_to_document_type.php` if the timestamp differs.

- [ ] **Step 2: Implement `safeUp()` / `safeDown()`**

Paste the full migration from spec §4.3 (`docs/superpowers/specs/2026-05-26-dokumen-pembentukan-puu-design.md` lines 97–172). Ensure these are included:

- Columns: `document_group_label`, `slug` + indexes.
- Tagged ids `76, 77, 78, 79, 80, 83, 84` with slugs from spec §4.2.1.
- Row `76`: rename to `NASKAH AKADEMIK KEMENKUM`, slug `naskah-akademik-kemenkum`.
- `document.jenis_peraturan` update: `KEMENKUMHAM` → `KEMENKUM`.
- Row `147`: rename only when `name = 'Risalah Rapat'`.
- Insert `PROGRAM PENYUSUNAN PUU` if missing.

Add RBAC at end of `safeUp()`:

```php
$time = time();
$this->insert('{{%auth_item}}', [
    'name' => '/document-group/legislation-formation',
    'type' => 2,
    'description' => 'View Dokumen Pembentukan PUU menu group',
    'rule_name' => null,
    'data' => null,
    'created_at' => $time,
    'updated_at' => $time,
]);

foreach (['pustakawan', 'superadmin'] as $roleName) {
    $exists = (new \yii\db\Query())
        ->from('{{%auth_item_child}}')
        ->where(['parent' => $roleName, 'child' => '/document-group/legislation-formation'])
        ->exists($this->db);
    if (!$exists) {
        $this->insert('{{%auth_item_child}}', [
            'parent' => $roleName,
            'child' => '/document-group/legislation-formation',
        ]);
    }
}
```

In `safeDown()`, before dropping columns:

```php
$this->delete('{{%auth_item_child}}', [
    'child' => '/document-group/legislation-formation',
]);
$this->delete('{{%auth_item}}', [
    'name' => '/document-group/legislation-formation',
]);
$this->update('{{%document}}',
    ['jenis_peraturan' => 'NASKAH AKADEMIK KEMENKUMHAM'],
    ['jenis_peraturan' => 'NASKAH AKADEMIK KEMENKUM']
);
$this->update('{{%document_type}}',
    ['name' => 'NASKAH AKADEMIK KEMENKUMHAM', 'singkatan' => 'NASKAH AKADEMIK KEMENKUMHAM'],
    ['id' => 76]
);
```

- [ ] **Step 3: Run migration**

```bash
php yii migrate --interactive=0
```

Expected: `Migration m260527_000000_add_document_group_label_to_document_type applied successfully.`

Verify:
```bash
php yii migrate/down 1 --interactive=0
php yii migrate --interactive=0
```

- [ ] **Step 4: Commit**

```bash
git add console/migrations/m260527_000000_add_document_group_label_to_document_type.php
git commit -m "feat(db): add document_group_label and slug for PUU types"
```

---

### Task 2: Shared `DocumentGroup` + `DocumentType` model

**Files:**
- Create: `common/components/DocumentGroup.php`
- Create: `common/models/DocumentType.php`
- Modify: `frontend/models/DocumentType.php`

- [ ] **Step 1: Create `common/components/DocumentGroup.php`**

```php
<?php

namespace common\components;

class DocumentGroup
{
    public const LEGISLATION_FORMATION = 'legislation_formation';

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

- [ ] **Step 2: Create `common/models/DocumentType.php`**

Use the full class from spec §5.2 (lines 221–278): `findByGroup`, `findBySlugInGroup`, `descendantTypeIds`, `descendantTypeNames`, `groupTypeNames`.

Add `rules()` for the new attributes (optional but useful for forms):

```php
public function rules()
{
    return [
        [['parent_id', 'integrasi', 'created_by', 'updated_by'], 'integer'],
        [['created_at', 'updated_at'], 'safe'],
        [['second_id', 'name', 'singkatan', 'status'], 'string', 'max' => 255],
        [['document_group_label'], 'string', 'max' => 64],
        [['slug'], 'string', 'max' => 128],
        [['slug'], 'match', 'pattern' => '/^[\w-]+$/'],
        [['slug'], 'unique'],
    ];
}
```

- [ ] **Step 3: Thin frontend model**

Replace `frontend/models/DocumentType.php` body with:

```php
<?php

namespace frontend\models;

class DocumentType extends \common\models\DocumentType
{
}
```

This preserves `use frontend\models\DocumentType` in `DokumenController.php`.

- [ ] **Step 4: Commit**

```bash
git add common/components/DocumentGroup.php common/models/DocumentType.php frontend/models/DocumentType.php
git commit -m "feat(common): DocumentGroup constants and DocumentType helpers"
```

---

### Task 3: Unit tests for `DocumentType` helpers

**Files:**
- Create: `common/tests/unit/models/DocumentTypeTest.php`

- [ ] **Step 1: Write test (requires migrated DB / fixtures)**

```php
<?php

namespace common\tests\unit\models;

use common\models\DocumentType;
use common\components\DocumentGroup;
use Codeception\Test\Unit;

class DocumentTypeTest extends Unit
{
    public function testFindBySlugInGroupReturnsPenelitianHukum(): void
    {
        $type = DocumentType::findBySlugInGroup(
            'penelitian-hukum',
            DocumentGroup::LEGISLATION_FORMATION
        );
        $this->assertNotNull($type);
        $this->assertSame('PENELITIAN HUKUM', $type->name);
    }

    public function testFindBySlugInGroupRejectsUntaggedSlug(): void
    {
        $type = DocumentType::findBySlugInGroup(
            'buku-hukum',
            DocumentGroup::LEGISLATION_FORMATION
        );
        $this->assertNull($type);
    }

    public function testRancanganPuuIncludesDescendantNames(): void
    {
        $type = DocumentType::findBySlugInGroup(
            'rancangan-puu',
            DocumentGroup::LEGISLATION_FORMATION
        );
        $this->assertNotNull($type);
        $names = $type->descendantTypeNames();
        $this->assertContains('RANCANGAN PERATURAN PERUNDANG-UNDANGAN', $names);
        $this->assertContains('RANCANGAN PERATURAN DAERAH PROVINSI', $names);
    }
}
```

- [ ] **Step 2: Run tests**

```bash
vendor/bin/codecept run -c common unit models/DocumentTypeTest
```

Expected: PASS (3 tests). If DB not migrated, run Task 1 first.

- [ ] **Step 3: Commit**

```bash
git add common/tests/unit/models/DocumentTypeTest.php
git commit -m "test: DocumentType group and slug helpers"
```

---

### Task 4: Backend — `TipeDokumen` + `MonografiSearch`

**Files:**
- Modify: `backend/models/TipeDokumen.php`
- Modify: `backend/models/MonografiSearch.php`

- [ ] **Step 1: Extend `TipeDokumen` rules and labels**

In `rules()`, add to string max rules and append:

```php
[['document_group_label'], 'string', 'max' => 64],
[['slug'], 'string', 'max' => 128],
[['slug'], 'match', 'pattern' => '/^[\w-]+$/'],
[['slug'], 'unique'],
```

In `attributeLabels()`:

```php
'document_group_label' => 'Document Group',
'slug' => 'Slug',
```

(Optional: add fields to the TipeDokumen admin form view if one exists — out of scope unless already editing `document_type` in UI.)

- [ ] **Step 2: Add `documentTypeId` to `MonografiSearch`**

At top of class:

```php
/** @var int|null Virtual filter: document_type.id → exact jenis_peraturan match */
public $documentTypeId;
```

In `rules()`, merge:

```php
[['documentTypeId'], 'integer'],
```

At end of `search()` before `return $dataProvider;`:

```php
if ($this->documentTypeId) {
    $type = \common\models\DocumentType::findOne($this->documentTypeId);
    if ($type) {
        $query->andWhere(['jenis_peraturan' => $type->name]);
    } else {
        $query->andWhere('0=1');
    }
}
```

- [ ] **Step 3: Manual verify backend filter**

Log in as `pustakawan`, open:

`/monografi/index?MonografiSearch[documentTypeId]=78`

Expected: grid shows only Monografi with `jenis_peraturan = 'PENELITIAN HUKUM'`.

- [ ] **Step 4: Commit**

```bash
git add backend/models/TipeDokumen.php backend/models/MonografiSearch.php
git commit -m "feat(backend): filter Monografi by document_type id"
```

---

### Task 5: Backend sidebar — dynamic PUU menu

**Files:**
- Modify: `backend/views/layouts/leftside.php`

**Note:** Current layout renders `$menuItems` (header only) in the first `Menu::widget`, then RBAC `$items2` in the second. The PUU block must be a **third** `Menu::widget`, not appended to `$menuItems` (which only contains the header).

- [ ] **Step 1: Build menu block after `$items2` is loaded (~line 53)**

```php
use common\components\DocumentGroup;
use common\models\DocumentType;

$puuSidebarItems = [];
$puuTypes = DocumentType::findByGroup(DocumentGroup::LEGISLATION_FORMATION);
if ($puuTypes && Yii::$app->user->can('/document-group/legislation-formation')) {
    $puuSidebarItems = [
        [
            'label' => DocumentGroup::label(DocumentGroup::LEGISLATION_FORMATION),
            'icon' => 'file-text-o',
            'items' => array_map(static function (DocumentType $t) {
                return [
                    'label' => $t->name,
                    'url' => [
                        '/monografi/index',
                        'MonografiSearch[documentTypeId]' => $t->id,
                    ],
                ];
            }, $puuTypes),
        ],
    ];
}
```

- [ ] **Step 2: Render third menu widget after the RBAC widget (~line 70)**

```php
<?php if (!empty($puuSidebarItems)) : ?>
    <?= Menu::widget([
        'options' => ['class' => 'sidebar-menu'],
        'items' => $puuSidebarItems,
    ]) ?>
<?php endif; ?>
```

- [ ] **Step 3: Verify**

Log in as role with `/document-group/legislation-formation`. Sidebar shows **Dokumen Pembentukan PUU** with nine children (including both Naskah rows). Click **Penelitian Hukum** → filtered Monografi index.

- [ ] **Step 4: Commit**

```bash
git add backend/views/layouts/leftside.php
git commit -m "feat(backend): dynamic Dokumen Pembentukan PUU sidebar menu"
```

---

### Task 6: Frontend — search, controller, view, URLs, menu

**Files:**
- Modify: `frontend/models/DokumenSearch.php`
- Modify: `frontend/controllers/DokumenController.php`
- Create: `frontend/views/dokumen/index-legislation-formation.php`
- Modify: `frontend/config/main.php`
- Modify: `frontend/views/layouts/menu.php`

- [ ] **Step 1: Add `searchByTypeNames()` to `DokumenSearch`**

```php
/**
 * @param string[] $names Exact jenis_peraturan values
 */
public function searchByTypeNames(array $names, array $params): \yii\data\ActiveDataProvider
{
    $query = DokumenDataSubyek::find()
        ->andWhere(['tipe_dokumen' => Dokumen::TYPE_MONOGRAFI])
        ->andWhere(['is_publish' => 1]);

    if ($names !== []) {
        $query->andWhere(['jenis_peraturan' => $names]);
    } else {
        $query->andWhere('0=1');
    }

    $dataProvider = new \yii\data\ActiveDataProvider([
        'query' => $query,
        'pagination' => ['pageSize' => 10],
        'sort' => ['defaultOrder' => ['tahun_terbit' => SORT_DESC, 'dokumen_type_id' => SORT_ASC]],
    ]);

    $this->load($params);

    if (!$this->validate()) {
        return $dataProvider;
    }

    $query->andFilterWhere(['like', 'judul', $this->judul])
        ->andFilterWhere(['like', 'teu', $this->teu])
        ->andFilterWhere(['like', 'subyek', $this->subyek])
        ->andFilterWhere(['like', 'nama_pengarang', $this->nama_pengarang])
        ->andFilterWhere(['like', 'tahun_terbit', $this->tahun_terbit]);

    return $dataProvider;
}
```

Add `use frontend\models\Dokumen;` if not already present via parent.

- [ ] **Step 2: Add `actionLegislationFormation()` to `DokumenController`**

```php
public function actionLegislationFormation($slug = null)
{
    $group = \common\components\DocumentGroup::LEGISLATION_FORMATION;
    $type = $slug
        ? DocumentType::findBySlugInGroup($slug, $group)
        : null;

    if ($slug !== null && $slug !== '' && $type === null) {
        throw new NotFoundHttpException('Tipe dokumen tidak ditemukan.');
    }

    $searchModel = new DokumenSearch();
    $typeNames = $type
        ? $type->descendantTypeNames()
        : DocumentType::groupTypeNames($group);

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

No `AccessControl` change needed — only `create`, `update`, `delete` are restricted; this action stays public like `actionMonografi`.

- [ ] **Step 3: Create view `index-legislation-formation.php`**

Copy `frontend/views/dokumen/index-monografi.php` to `index-legislation-formation.php`, then change:

```php
$pageTitle = $currentType
    ? ucwords(strtolower($currentType->name))
    : \common\components\DocumentGroup::label(
        \common\components\DocumentGroup::LEGISLATION_FORMATION
    );
$this->title = $pageTitle;
$this->registerMetaTag([
    'name' => 'description',
    'content' => 'Koleksi ' . $pageTitle . ' — Dokumen pembentukan peraturan perundang-undangan.',
]);
```

Replace `<h1 class="sr-only">…</h1>` text with `$pageTitle`.

- [ ] **Step 4: URL rules in `frontend/config/main.php`**

Inside `'rules' => [`, **before** the catch-all `/` rule:

```php
'dokumen-pembentukan-puu' => 'dokumen/legislation-formation',
'dokumen-pembentukan-puu/<slug:[\w-]+>' => 'dokumen/legislation-formation',
```

- [ ] **Step 5: Public menu in `frontend/views/layouts/menu.php`**

After the `Jenis Dokumen` block (after line 66), insert:

```php
[
    'label' => \common\components\DocumentGroup::label(
        \common\components\DocumentGroup::LEGISLATION_FORMATION
    ),
    'url' => ['/dokumen/legislation-formation'],
    'options' => ['class' => 'dropdown'],
    'activateItems' => true,
    'activeCssClass' => 'active',
    'template' => '<span class="submenu-button"></span><a href={url}>{label}</a>',
    'items' => array_map(static function (\common\models\DocumentType $t) {
        return [
            'label' => ucwords(strtolower($t->name)),
            'url' => ['dokumen/legislation-formation', 'slug' => $t->slug],
        ];
    }, \common\models\DocumentType::findByGroup(
        \common\components\DocumentGroup::LEGISLATION_FORMATION
    )),
],
```

Add group landing link: first item optional — or set parent `url` to `['/dokumen-pembentukan-puu']` via UrlManager alias (pretty URL `/dokumen-pembentukan-puu`).

Prefer parent dropdown `url` => `Url::to(['/dokumen-pembentukan-puu'])` for the group landing.

- [ ] **Step 6: Commit**

```bash
git add frontend/models/DokumenSearch.php frontend/controllers/DokumenController.php \
  frontend/views/dokumen/index-legislation-formation.php frontend/config/main.php \
  frontend/views/layouts/menu.php
git commit -m "feat(frontend): Dokumen Pembentukan PUU listing and menu"
```

---

### Task 7: Functional tests (public routes)

**Files:**
- Create: `frontend/tests/functional/DokumenPembentukanPuuCest.php`

- [ ] **Step 1: Write Cest**

```php
<?php

class DokumenPembentukanPuuCest
{
    public function groupLandingReturnsOk(\FunctionalTester $I): void
    {
        $I->amOnPage('/dokumen-pembentukan-puu');
        $I->seeResponseCodeIs(200);
        $I->see('Dokumen Pembentukan PUU');
    }

    public function penelitianHukumSlugReturnsOk(\FunctionalTester $I): void
    {
        $I->amOnPage('/dokumen-pembentukan-puu/penelitian-hukum');
        $I->seeResponseCodeIs(200);
    }

    public function unknownSlugReturns404(\FunctionalTester $I): void
    {
        $I->amOnPage('/dokumen-pembentukan-puu/buku-hukum');
        $I->seeResponseCodeIs(404);
    }

    public function numericSlugNotUsedAsId(\FunctionalTester $I): void
    {
        $I->amOnPage('/dokumen-pembentukan-puu/78');
        $I->seeResponseCodeIs(404);
    }
}
```

- [ ] **Step 2: Run**

```bash
vendor/bin/codecept run -c frontend functional DokumenPembentukanPuuCest
```

Expected: PASS (or skip if frontend functional suite needs web server — document in PR if env blocks).

- [ ] **Step 3: Commit**

```bash
git add frontend/tests/functional/DokumenPembentukanPuuCest.php
git commit -m "test(frontend): Dokumen Pembentukan PUU routes"
```

---

### Task 8: Update `seed_data.sql` (fresh installs)

**Files:**
- Modify: `console/migrations/seed_data.sql` (~line 13723 `INSERT INTO document_type`)

- [ ] **Step 1: Add columns to INSERT column list**

Change:
```sql
INSERT INTO `document_type` (`id`, `second_id`, `parent_id`, `name`, `singkatan`, ...
```
to include `` `document_group_label`, `slug` `` after `singkatan`.

- [ ] **Step 2: Set values on tagged rows**

| id | document_group_label | slug |
|----|----------------------|------|
| 76 | legislation_formation | naskah-akademik-kemenkum |
| 77 | legislation_formation | naskah-akademik |
| 78–80, 83–84 | legislation_formation | (per spec §4.2.1) |
| 147 | legislation_formation | risalah-pembahasan |

Update row 76 `name`/`singkatan` to `NASKAH AKADEMIK KEMENKUM`. Update row 147 name to `RISALAH PEMBAHASAN`.

- [ ] **Step 3: Append PROGRAM PENYUSUNAN PUU row** (new id after 150 or next free id in dump).

- [ ] **Step 4: Commit**

```bash
git add console/migrations/seed_data.sql
git commit -m "chore(seed): document_type group labels and slugs for PUU"
```

---

### Task 9: Final verification

- [ ] **Run unit + functional tests**

```bash
vendor/bin/codecept run -c common unit
vendor/bin/codecept run -c frontend functional DokumenPembentukanPuuCest
```

- [ ] **Manual smoke checklist (from spec §8)**

- [ ] Backend: PUU menu visible for `pustakawan`; hidden for user without permission.
- [ ] Backend: `MonografiSearch[documentTypeId]=78` filters correctly.
- [ ] Frontend: `/dokumen-pembentukan-puu`, `/dokumen-pembentukan-puu/rancangan-puu`, `/dokumen-pembentukan-puu/penelitian-hukum` return 200.
- [ ] Frontend: `/dokumen-pembentukan-puu/buku-hukum` → 404.
- [ ] Document detail from listing still opens existing `dokumen/view`.

---

## Spec coverage checklist (self-review)

| Spec requirement | Task |
|------------------|------|
| `document_group_label` + `slug` columns | Task 1 |
| Tag/rename/insert nine types + KEMENKUM rename + `document.jenis_peraturan` sync | Task 1 |
| `DocumentGroup` constants | Task 2 |
| `common\models\DocumentType` helpers | Task 2, 3 |
| `MonografiSearch[documentTypeId]` | Task 4 |
| Dynamic backend sidebar + RBAC permission | Task 1, 5 |
| Frontend menu + slug URLs | Task 6 |
| `searchByTypeNames` + descendants for Rancangan PUU | Task 6, 3 |
| `index-legislation-formation` view | Task 6 |
| seed_data.sql Layer B | Task 8 |
| Tests from spec §8 | Task 3, 7 |

**Out of scope (per spec):** new Monografi controller, `rancangan` integration, `dokumen_type_id` backfill, sort_order column, Manage Groups admin UI.

---

## Open questions (carry forward, do not block)

1. Nine menu items include two Naskah rows — merge later if stakeholders want one entry.
2. Alphabetical sort vs workflow order — default `ORDER BY name ASC`.
3. `ucwords(strtolower())` for menu labels — watch acronyms (PUU).
