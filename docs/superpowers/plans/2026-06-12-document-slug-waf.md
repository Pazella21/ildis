# Document Slug WAF Safety — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Stop F5 WAF blocks on long document URLs by enforcing 60-character slugs everywhere, using structured peraturan slugs (`pm-7-2026`) when metadata is available.

**Architecture:** Extend `DocumentSlug` as the single source of truth (`fromDocument()`, `normalize()`). Wire it into model `getUrlSlug()`, `DocumentSlugBehavior`, and `resolve()`. Add a `normalize-slugs` console backfill. No routing or view changes.

**Tech Stack:** PHP 7.4+/Yii2, Codeception unit tests (`vendor/bin/codecept run -c common`)

**Spec:** `docs/superpowers/specs/2026-06-12-document-slug-waf-design.md`

---

## Files Structure

| Action | File | Responsibility |
|--------|------|----------------|
| Modify | `common/components/DocumentSlug.php` | `fromDocument()`, `normalize()`, `MAX_LENGTH = 60`, fix `resolve()` |
| Create | `common/tests/unit/components/DocumentSlugTest.php` | Unit tests for slug generation and normalization |
| Modify | `common/behaviors/DocumentSlugBehavior.php` | Generate via `fromDocument()`, always `normalize()` on save |
| Modify | `frontend/models/Dokumen.php` | `getUrlSlug()` delegates to `DocumentSlug` |
| Modify | `console/controllers/DocumentController.php` | `actionNormalizeSlugs()`, update `actionBackfillSlugs()` |

No changes to `DocumentViewUrlRule`, `DokumenController`, or view templates.

---

### Task 1: DocumentSlug — tests and core logic

**Files:**
- Modify: `common/components/DocumentSlug.php`
- Create: `common/tests/unit/components/DocumentSlugTest.php`

- [ ] **Step 1: Write the failing tests**

Create `common/tests/unit/components/DocumentSlugTest.php`:

```php
<?php

namespace common\tests\unit\components;

use Codeception\Test\Unit;
use common\components\DocumentSlug;

class DocumentSlugTest extends Unit
{
    private const LONG_JUDUL = 'Peraturan Menteri Hukum Nomor 7 Tahun 2026 Tentang Tata Cara Pengharmonisasian Pembulatan Dan Pemantapan Konsepsi Rancangan Undang-Undang';

    public function testFromJudulTruncatesLongTitle(): void
    {
        $slug = DocumentSlug::fromJudul(self::LONG_JUDUL);

        $this->assertLessThanOrEqual(60, strlen($slug));
        $this->assertNotSame('', $slug);
    }

    public function testFromJudulEmptyReturnsDokumen(): void
    {
        $this->assertSame('dokumen', DocumentSlug::fromJudul(''));
    }

    public function testNormalizeTruncatesLongSlug(): void
    {
        $long = str_repeat('kata-', 50);
        $slug = DocumentSlug::normalize($long);

        $this->assertLessThanOrEqual(60, strlen($slug));
        $this->assertNotSame('-', substr($slug, -1));
    }

    public function testFromDocumentPeraturanStructuredSlug(): void
    {
        $slug = DocumentSlug::fromDocument(
            1,
            self::LONG_JUDUL,
            'PM',
            '7',
            '2026'
        );

        $this->assertSame('pm-7-2026', $slug);
        $this->assertLessThanOrEqual(60, strlen($slug));
    }

    public function testFromDocumentPeraturanMissingTahunFallsBackToJudul(): void
    {
        $slug = DocumentSlug::fromDocument(
            1,
            self::LONG_JUDUL,
            'PM',
            '7',
            null
        );

        $this->assertNotSame('pm-7-2026', $slug);
        $this->assertLessThanOrEqual(60, strlen($slug));
        $this->assertStringContainsString('peraturan', $slug);
    }

    public function testFromDocumentPutusanUsesTruncatedJudul(): void
    {
        $slug = DocumentSlug::fromDocument(4, self::LONG_JUDUL);

        $this->assertLessThanOrEqual(60, strlen($slug));
        $this->assertStringContainsString('peraturan', $slug);
    }

    public function testFromDocumentEmptyJudulReturnsDokumen(): void
    {
        $this->assertSame('dokumen', DocumentSlug::fromDocument(2, ''));
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/codecept run -c common unit components/DocumentSlugTest`

Expected: FAIL — `fromDocument` and `normalize` methods do not exist; truncation still uses 80-char limit.

- [ ] **Step 3: Implement DocumentSlug**

Replace `common/components/DocumentSlug.php` with:

```php
<?php

namespace common\components;

use Yii;
use yii\helpers\Inflector;

class DocumentSlug
{
  private const MAX_LENGTH = 60;
  private const TYPE_PERATURAN = 1;

  public static function fromDocument(
      int $tipeDokumen,
      string $judul,
      ?string $singkatanJenis = null,
      ?string $nomorPeraturan = null,
      ?string $tahunTerbit = null
  ): string {
      if ($tipeDokumen === self::TYPE_PERATURAN
          && self::hasValue($singkatanJenis)
          && self::hasValue($nomorPeraturan)
          && self::hasValue($tahunTerbit)
      ) {
          return self::normalize(
              Inflector::slug($singkatanJenis . '-' . $nomorPeraturan . '-' . $tahunTerbit)
          );
      }

      return self::fromJudul($judul);
  }

  public static function fromJudul(string $judul): string
  {
      $slug = Inflector::slug($judul);
      if ($slug === '') {
          $slug = 'dokumen';
      }

      return self::normalize($slug);
  }

  public static function normalize(string $slug): string
  {
      $slug = trim($slug);
      if ($slug === '') {
          return 'dokumen';
      }

      if (strlen($slug) > self::MAX_LENGTH) {
          $slug = rtrim(substr($slug, 0, self::MAX_LENGTH), '-');
      }

      return $slug !== '' ? $slug : 'dokumen';
  }

  /**
   * Resolve slug for URL generation (normalized DB slug, or derived from document fields).
   */
  public static function resolve(int $id, ?string $judul = null): string
  {
      static $cache = [];

      if (isset($cache[$id])) {
          return $cache[$id];
      }

      $row = Yii::$app->db->createCommand(
          'SELECT slug, judul, tipe_dokumen, singkatan_jenis, nomor_peraturan, tahun_terbit
           FROM {{%document}} WHERE id = :id LIMIT 1',
          [':id' => $id]
      )->queryOne();

      if ($row === false) {
          return $cache[$id] = self::fromJudul($judul ?? 'dokumen');
      }

      if (!empty($row['slug'])) {
          return $cache[$id] = self::normalize($row['slug']);
      }

      return $cache[$id] = self::fromDocument(
          (int) $row['tipe_dokumen'],
          $judul ?? ($row['judul'] ?? ''),
          $row['singkatan_jenis'] ?? null,
          $row['nomor_peraturan'] ?? null,
          $row['tahun_terbit'] ?? null
      );
  }

  private static function hasValue(?string $value): bool
  {
      return $value !== null && trim($value) !== '';
  }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `vendor/bin/codecept run -c common unit components/DocumentSlugTest`

Expected: PASS (7 tests)

- [ ] **Step 5: Commit**

```bash
git add common/components/DocumentSlug.php common/tests/unit/components/DocumentSlugTest.php
git commit -m "fix: enforce WAF-safe document slugs with structured peraturan format"
```

---

### Task 2: DocumentSlugBehavior — generate and normalize on save

**Files:**
- Modify: `common/behaviors/DocumentSlugBehavior.php`

- [ ] **Step 1: Update behavior**

Replace `ensureSlug()` in `common/behaviors/DocumentSlugBehavior.php`:

```php
public function ensureSlug(): void
{
    $owner = $this->owner;

    if (empty($owner->slug)) {
        if (empty($owner->judul)) {
            return;
        }

        $owner->slug = DocumentSlug::fromDocument(
            (int) $owner->tipe_dokumen,
            $owner->judul,
            $owner->singkatan_jenis ?? null,
            $owner->nomor_peraturan ?? null,
            $owner->tahun_terbit ?? null
        );
        return;
    }

    $owner->slug = DocumentSlug::normalize($owner->slug);
}
```

- [ ] **Step 2: Run unit tests (regression check)**

Run: `vendor/bin/codecept run -c common unit components/DocumentSlugTest`

Expected: PASS

- [ ] **Step 3: Commit**

```bash
git add common/behaviors/DocumentSlugBehavior.php
git commit -m "fix: generate and normalize document slug on save"
```

---

### Task 3: Dokumen::getUrlSlug() — never return raw DB slug

**Files:**
- Modify: `frontend/models/Dokumen.php` (`getUrlSlug()` around line 180)

- [ ] **Step 1: Update getUrlSlug()**

Add `use common\components\DocumentSlug;` at the top if not present.

Replace `getUrlSlug()`:

```php
public function getUrlSlug(): string
{
    if (!empty($this->slug)) {
        return DocumentSlug::normalize($this->slug);
    }

    return DocumentSlug::fromDocument(
        (int) $this->tipe_dokumen,
        $this->judul ?? '',
        $this->singkatan_jenis ?? null,
        $this->nomor_peraturan ?? null,
        $this->tahun_terbit ?? null
    );
}
```

- [ ] **Step 2: Run unit tests (regression check)**

Run: `vendor/bin/codecept run -c common unit components/DocumentSlugTest`

Expected: PASS

- [ ] **Step 3: Commit**

```bash
git add frontend/models/Dokumen.php
git commit -m "fix: normalize slug in Dokumen::getUrlSlug()"
```

---

### Task 4: Console commands — normalize-slugs and backfill update

**Files:**
- Modify: `console/controllers/DocumentController.php`

- [ ] **Step 1: Update actionBackfillSlugs and add actionNormalizeSlugs**

Replace the full `console/controllers/DocumentController.php` with:

```php
<?php

namespace console\controllers;

use common\components\DocumentSlug;
use yii\console\Controller;
use yii\db\Query;
use yii\helpers\Console;

class DocumentController extends Controller
{
    /**
     * Backfill empty document.slug values from document metadata.
     */
    public function actionBackfillSlugs(): int
    {
        $rows = (new Query())
            ->from('{{%document}}')
            ->select([
                'id',
                'judul',
                'slug',
                'tipe_dokumen',
                'singkatan_jenis',
                'nomor_peraturan',
                'tahun_terbit',
            ])
            ->where(['or', ['slug' => null], ['slug' => '']])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        if (empty($rows)) {
            $this->stdout("No documents need slug backfill.\n", Console::FG_GREEN);
            return self::EXIT_CODE_NORMAL;
        }

        $updated = $this->updateSlugs($rows, false);

        $this->stdout("Backfilled slug for {$updated} document(s).\n", Console::FG_GREEN);

        return self::EXIT_CODE_NORMAL;
    }

    /**
     * Regenerate slugs that are missing or exceed MAX_LENGTH (WAF safety backfill).
     */
    public function actionNormalizeSlugs(): int
    {
        $rows = (new Query())
            ->from('{{%document}}')
            ->select([
                'id',
                'judul',
                'slug',
                'tipe_dokumen',
                'singkatan_jenis',
                'nomor_peraturan',
                'tahun_terbit',
            ])
            ->where([
                'or',
                ['slug' => null],
                ['slug' => ''],
                ['>', new \yii\db\Expression('CHAR_LENGTH([[slug]])'), 60],
            ])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        if (empty($rows)) {
            $this->stdout("No documents need slug normalization.\n", Console::FG_GREEN);
            return self::EXIT_CODE_NORMAL;
        }

        $updated = $this->updateSlugs($rows, true);

        $this->stdout("Normalized slug for {$updated} document(s).\n", Console::FG_GREEN);

        return self::EXIT_CODE_NORMAL;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function updateSlugs(array $rows, bool $logSamples): int
    {
        $updated = 0;
        $samples = 0;

        foreach ($rows as $row) {
            $oldSlug = $row['slug'] ?? '';
            $newSlug = DocumentSlug::fromDocument(
                (int) $row['tipe_dokumen'],
                $row['judul'] ?? '',
                $row['singkatan_jenis'] ?? null,
                $row['nomor_peraturan'] ?? null,
                $row['tahun_terbit'] ?? null
            );

            if ($newSlug === $oldSlug) {
                continue;
            }

            \Yii::$app->db->createCommand()->update(
                '{{%document}}',
                ['slug' => $newSlug],
                ['id' => $row['id']]
            )->execute();

            if ($logSamples && $samples < 5) {
                $this->stdout(
                    "  id {$row['id']}: \"{$oldSlug}\" → \"{$newSlug}\"\n",
                    Console::FG_YELLOW
                );
                $samples++;
            }

            $updated++;
        }

        return $updated;
    }
}
```

- [ ] **Step 2: Smoke-test console commands**

Run: `php yii document/normalize-slugs`

Expected: Either "No documents need slug normalization." or a count with up to 5 before/after samples. No PHP errors.

Run: `php yii document/backfill-slugs`

Expected: Either "No documents need slug backfill." or a success count. No PHP errors.

- [ ] **Step 3: Commit**

```bash
git add console/controllers/DocumentController.php
git commit -m "feat: add document/normalize-slugs console command"
```

---

### Task 5: Final verification

- [ ] **Step 1: Run all common unit tests**

Run: `vendor/bin/codecept run -c common unit`

Expected: PASS

- [ ] **Step 2: Manual URL check (local/staging)**

1. Find a document with a long judul (or insert a test row).
2. Run `php yii document/normalize-slugs`.
3. Open `/dokumen/{id}-{slug}` in browser.
4. Confirm slug segment is ≤ 60 characters.
5. For a peraturan with `singkatan_jenis`, `nomor_peraturan`, `tahun_terbit` — confirm slug matches `{singkatan}-{nomor}-{tahun}` pattern (e.g. `pm-7-2026`).

- [ ] **Step 3: Production deploy checklist**

1. Deploy code.
2. Run `php yii document/normalize-slugs` on production.
3. Re-test a URL that was previously WAF-blocked.
4. Request sitemap recrawl (informational; no code change).

---

## Spec Coverage Self-Review

| Spec requirement | Task |
|------------------|------|
| `fromDocument()` with peraturan structured slug | Task 1 |
| `normalize()` max 60 chars | Task 1 |
| `fromJudul()` delegates to normalize | Task 1 |
| `resolve()` normalizes DB slug, uses fromDocument fallback | Task 1 |
| `getUrlSlug()` never returns raw DB slug | Task 3 |
| Behavior: fromDocument when empty, normalize always | Task 2 |
| `normalize-slugs` console command | Task 4 |
| `backfill-slugs` uses fromDocument | Task 4 |
| Unit tests per spec | Task 1 |
| Routing unchanged | N/A (no task needed) |
| Manual verification steps | Task 5 |

No placeholders. All method signatures consistent across tasks.
