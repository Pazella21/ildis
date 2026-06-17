# Feed Export Interactive CLI — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add `feed/export-document` — an interactive, filterable CLI export for operators — while keeping `feed/generate-document` unchanged for cron.

**Architecture:** Extract filter validation, query building, and output-path logic into `common/components/FeedExportFilter.php` (testable from `common/tests`). Refactor `FeedController` to share document enrichment and atomic JSON write between both actions. New export writes only to `feed/export/`.

**Tech Stack:** PHP 7.4+/Yii2, Codeception unit tests (`vendor/bin/codecept run -c common`)

**Spec:** `docs/superpowers/specs/2026-06-12-feed-export-interactive-design.md`

---

## Files Structure

| Action | File | Responsibility |
|--------|------|----------------|
| Create | `common/components/FeedExportFilter.php` | Filter DTO, validation, query `applyToQuery()`, `resolveOutputPath()`, slug helpers |
| Create | `common/tests/unit/components/FeedExportFilterTest.php` | Unit tests for filter logic and path resolution |
| Modify | `console/controllers/FeedController.php` | Shared pipeline refactor; `actionExportDocument()` with interactive + CLI flags |

No changes to `docker/cron/crontab`, `install.sh`, or `feed/document.json` cron path.

---

### Task 1: FeedExportFilter — validation and path resolution

**Files:**
- Create: `common/components/FeedExportFilter.php`
- Create: `common/tests/unit/components/FeedExportFilterTest.php`

- [ ] **Step 1: Write the failing tests**

Create `common/tests/unit/components/FeedExportFilterTest.php`:

```php
<?php

namespace common\tests\unit\components;

use backend\models\DokumenJdih;
use Codeception\Test\Unit;
use common\components\FeedExportFilter;
use common\models\DocumentType;

class FeedExportFilterTest extends Unit
{
    public function testValidateRejectsInvalidTipe(): void
    {
        $filter = new FeedExportFilter(['tipe' => 9]);
        $filter->validate();
        $this->assertArrayHasKey('tipe', $filter->getErrors());
    }

    public function testValidateRequiresDateFieldWhenRangeSet(): void
    {
        $filter = new FeedExportFilter(['from' => '2024-01-01']);
        $filter->validate();
        $this->assertArrayHasKey('dateField', $filter->getErrors());
    }

    public function testValidateRejectsFromAfterTo(): void
    {
        $filter = new FeedExportFilter([
            'dateField' => 'tanggal_pengundangan',
            'from' => '2024-12-31',
            'to' => '2024-01-01',
        ]);
        $filter->validate();
        $this->assertArrayHasKey('to', $filter->getErrors());
    }

    public function testApplyToQueryAddsTipeFilter(): void
    {
        $query = DokumenJdih::find()->alias('d')->where(['d.is_publish' => 1]);
        $filter = new FeedExportFilter(['tipe' => DokumenJdih::TYPE_PERATURAN]);
        FeedExportFilter::applyToQuery($query, $filter);

        $sql = $query->createCommand()->getRawSql();
        $this->assertStringContainsString('`tipe_dokumen`', $sql);
        $this->assertStringContainsString((string) DokumenJdih::TYPE_PERATURAN, $sql);
    }

    public function testApplyToQueryAddsDateRangeForUpdatedAt(): void
    {
        $query = DokumenJdih::find()->alias('d')->where(['d.is_publish' => 1]);
        $filter = new FeedExportFilter([
            'dateField' => 'updated_at',
            'from' => '2024-01-01',
            'to' => '2024-06-30',
        ]);
        FeedExportFilter::applyToQuery($query, $filter);

        $sql = $query->createCommand()->getRawSql();
        $this->assertStringContainsString('`updated_at`', $sql);
        $this->assertStringContainsString('2024-01-01', $sql);
        $this->assertStringContainsString('2024-06-30 23:59:59', $sql);
    }

    public function testApplyToQueryExpandsTypeIdToDescendants(): void
    {
        $type = DocumentType::find()->where(['parent_id' => 1])->one();
        if ($type === null) {
            $this->markTestSkipped('No peraturan document_type seed data.');
        }

        $query = DokumenJdih::find()->alias('d')->where(['d.is_publish' => 1]);
        $filter = new FeedExportFilter(['typeId' => $type->id]);
        FeedExportFilter::applyToQuery($query, $filter);

        $sql = $query->createCommand()->getRawSql();
        $this->assertStringContainsString('`dokumen_type_id`', $sql);
        foreach ($type->descendantTypeIds() as $id) {
            $this->assertStringContainsString((string) $id, $sql);
        }
    }

    public function testResolveOutputPathBuildsSlug(): void
    {
        $filter = new FeedExportFilter([
            'tipe' => DokumenJdih::TYPE_PERATURAN,
            'dateField' => 'tanggal_pengundangan',
            'from' => '2024-01-01',
            'to' => '2024-12-31',
        ]);

        $path = $filter->resolveOutputPath();
        $this->assertStringEndsWith('.json', $path);
        $this->assertStringContainsString('peraturan', $path);
        $this->assertStringContainsString('2024-01-01_2024-12-31', $path);
        $this->assertStringStartsWith(\Yii::getAlias('@feed/export/'), $path);
    }

    public function testResolveOutputPathRejectsUnsafeCustomOutput(): void
    {
        $filter = new FeedExportFilter(['output' => '../document.json']);
        $this->expectException(\InvalidArgumentException::class);
        $filter->resolveOutputPath();
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/codecept run -c common unit/components/FeedExportFilterTest.php -v`

Expected: FAIL — class `common\components\FeedExportFilter` not found

- [ ] **Step 3: Implement FeedExportFilter**

Create `common/components/FeedExportFilter.php`:

```php
<?php

namespace common\components;

use backend\models\DokumenJdih;
use common\models\DocumentType;
use yii\base\Model;
use yii\db\ActiveQuery;

class FeedExportFilter extends Model
{
    public const ALLOWED_DATE_FIELDS = [
        'updated_at',
        'tanggal_pengundangan',
        'tanggal_penetapan',
    ];

    public const TIPE_SLUGS = [
        DokumenJdih::TYPE_PERATURAN => 'peraturan',
        DokumenJdih::TYPE_MONOGRAFI => 'monografi',
        DokumenJdih::TYPE_ARTIKEL => 'artikel',
        DokumenJdih::TYPE_PUTUSAN => 'putusan',
    ];

    public const DATE_FIELD_SLUGS = [
        'updated_at' => 'updated',
        'tanggal_pengundangan' => 'pengundangan',
        'tanggal_penetapan' => 'penetapan',
    ];

    public $tipe;
    public $typeId;
    public $dateField;
    public $from;
    public $to;
    public $output;

    public function rules(): array
    {
        return [
            [['tipe', 'typeId'], 'integer'],
            [['dateField', 'from', 'to', 'output'], 'string'],
        ];
    }

    public function validate($attributeNames = null, $clearErrors = true): bool
    {
        parent::validate($attributeNames, $clearErrors);
        $this->validateBusinessRules();
        return !$this->hasErrors();
    }

    private function validateBusinessRules(): void
    {
        if ($this->tipe !== null && $this->tipe !== '') {
            $allowed = array_keys(self::TIPE_SLUGS);
            if (!in_array((int) $this->tipe, $allowed, true)) {
                $this->addError('tipe', 'tipe must be 1–4.');
            }
        }

        if ($this->typeId !== null && $this->typeId !== '') {
            if (DocumentType::findOne((int) $this->typeId) === null) {
                $this->addError('typeId', 'document_type not found.');
            }
        }

        if ($this->dateField !== null && $this->dateField !== '') {
            if (!in_array($this->dateField, self::ALLOWED_DATE_FIELDS, true)) {
                $this->addError('dateField', 'Invalid date field.');
            }
        }

        if (($this->from || $this->to) && empty($this->dateField)) {
            $this->addError('dateField', 'dateField is required when from/to is set.');
        }

        if ($this->from && !$this->isValidDate($this->from)) {
            $this->addError('from', 'from must be Y-m-d.');
        }

        if ($this->to && !$this->isValidDate($this->to)) {
            $this->addError('to', 'to must be Y-m-d.');
        }

        if ($this->from && $this->to && $this->from > $this->to) {
            $this->addError('to', 'to must be on or after from.');
        }
    }

    private function isValidDate(string $value): bool
    {
        $dt = \DateTime::createFromFormat('Y-m-d', $value);
        return $dt && $dt->format('Y-m-d') === $value;
    }

    public static function applyToQuery(ActiveQuery $query, self $filter): void
    {
        if ($filter->tipe !== null && $filter->tipe !== '') {
            $query->andWhere(['d.tipe_dokumen' => (int) $filter->tipe]);
        }

        if ($filter->typeId !== null && $filter->typeId !== '') {
            $type = DocumentType::findOne((int) $filter->typeId);
            if ($type === null) {
                throw new \InvalidArgumentException('document_type not found.');
            }
            $query->andWhere(['d.dokumen_type_id' => $type->descendantTypeIds()]);
        }

        if ($filter->dateField && $filter->from) {
            $query->andWhere(['>=', 'd.' . $filter->dateField, $filter->from]);
        }

        if ($filter->dateField && $filter->to) {
            $toValue = $filter->dateField === 'updated_at'
                ? $filter->to . ' 23:59:59'
                : $filter->to;
            $query->andWhere(['<=', 'd.' . $filter->dateField, $toValue]);
        }
    }

    public function resolveOutputPath(): string
    {
        $exportDir = \Yii::getAlias('@feed/export');

        if ($this->output !== null && $this->output !== '') {
            $basename = basename($this->output);
            if ($basename !== $this->output || strpos($this->output, '..') !== false) {
                throw new \InvalidArgumentException('Unsafe output path.');
            }
            if (substr(strtolower($basename), -5) !== '.json') {
                $basename .= '.json';
            }
            return $exportDir . '/' . $basename;
        }

        $parts = [];
        if ($this->tipe !== null && $this->tipe !== '') {
            $parts[] = self::TIPE_SLUGS[(int) $this->tipe] ?? 'dokumen';
        } else {
            $parts[] = 'semua';
        }

        if ($this->typeId !== null && $this->typeId !== '') {
            $type = DocumentType::findOne((int) $this->typeId);
            if ($type !== null) {
                $parts[] = self::slugify($type->slug ?: $type->name);
            }
        }

        if ($this->dateField && ($this->from || $this->to)) {
            $parts[] = self::DATE_FIELD_SLUGS[$this->dateField] ?? $this->dateField;
        }

        if ($this->from && $this->to) {
            $parts[] = $this->from . '_' . $this->to;
        } elseif ($this->from) {
            $parts[] = 'from-' . $this->from;
        } elseif ($this->to) {
            $parts[] = 'to-' . $this->to;
        }

        return $exportDir . '/' . implode('-', $parts) . '.json';
    }

    public static function slugify(string $value): string
    {
        $slug = strtolower(trim($value));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        return trim($slug, '-') ?: 'dokumen';
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `vendor/bin/codecept run -c common unit/components/FeedExportFilterTest.php -v`

Expected: PASS (or SKIP on descendant test if no seed data)

- [ ] **Step 5: Commit**

```bash
git add common/components/FeedExportFilter.php common/tests/unit/components/FeedExportFilterTest.php
git commit -m "feat: add FeedExportFilter for feed export CLI filters"
```

---

### Task 2: Refactor FeedController shared pipeline

**Files:**
- Modify: `console/controllers/FeedController.php`

- [ ] **Step 1: Extract private methods without changing generate-document behavior**

Add these private methods and constants to `FeedController`. Replace inline logic in `actionGenerateDocument()` with calls to them. The public behavior of `generate-document` must remain identical.

Key extracted methods:

```php
private function getDocumentSelectColumns(): array { /* same select array as today */ }

private function buildBaseQuery(): \yii\db\ActiveQuery
{
    return DokumenJdih::find()
        ->alias('d')
        ->select($this->getDocumentSelectColumns())
        ->where(['d.is_publish' => 1]);
}

/**
 * @return array<int, array<string, mixed>>
 */
private function fetchDocuments(\yii\db\ActiveQuery $query): array
{
    return $query->asArray()->all();
}

/**
 * @param array<int, array<string, mixed>> $dokumen
 * @return array<int, array<string, mixed>>
 */
private function enrichRows(array $dokumen): array
{
    // move existing lampiran + abstrak + url logic here unchanged
}

private function writeJsonFile(string $filePath, array $dokumen): int
{
    // move atomic write logic here; return byte count
}
```

Refactored `actionGenerateDocument()`:

```php
public function actionGenerateDocument()
{
    $filePath = \Yii::getAlias('@feed/document.json');

    try {
        $dokumen = $this->enrichRows(
            $this->fetchDocuments($this->buildBaseQuery())
        );

        if (empty($dokumen)) {
            echo "[feed] Peringatan: Tidak ada dokumen yang dipublikasikan. File tidak diperbarui.\n";
            return self::EXIT_CODE_ERROR;
        }

        $bytes = $this->writeJsonFile($filePath, $dokumen);
        echo "[feed] Berhasil: {$filePath} (" . count($dokumen) . " dokumen, {$bytes} bytes)\n";
        return self::EXIT_CODE_NORMAL;
    } catch (\Exception $e) {
        \Yii::error("[feed] Gagal generate document.json: " . $e->getMessage(), 'feed');
        echo "[feed] ERROR: " . $e->getMessage() . "\n";
        return self::EXIT_CODE_ERROR;
    }
}
```

- [ ] **Step 2: Smoke-test generate-document still works**

Run: `php yii feed/generate-document`

Expected: `[feed] Berhasil: .../feed/document.json (... dokumen, ... bytes)` or warning if no published docs

- [ ] **Step 3: Commit**

```bash
git add console/controllers/FeedController.php
git commit -m "refactor: extract shared feed document pipeline in FeedController"
```

---

### Task 3: Add export-document action with CLI options

**Files:**
- Modify: `console/controllers/FeedController.php`

- [ ] **Step 1: Add public properties, options(), and optionAliases()**

Mirror `UserController` pattern:

```php
use common\components\FeedExportFilter;
use backend\models\JenisPeraturan;
use yii\console\ExitCode;
use yii\helpers\Console;

public $tipe;
public $typeId;
public $dateField;
public $from;
public $to;
public $output;
public $nonInteractive = false;
public $yes = false;

public function options($actionID)
{
    return array_merge(parent::options($actionID), [
        'tipe', 'typeId', 'dateField', 'from', 'to', 'output',
        'nonInteractive', 'yes',
    ]);
}

public function optionAliases()
{
    return array_merge(parent::optionAliases(), [
        't' => 'tipe',
        'o' => 'output',
        'n' => 'nonInteractive',
        'y' => 'yes',
    ]);
}
```

- [ ] **Step 2: Implement actionExportDocument()**

```php
public function actionExportDocument()
{
    $filter = $this->nonInteractive
        ? $this->buildFilterFromFlags()
        : $this->buildFilterInteractively();

    if ($filter === null) {
        return ExitCode::OK;
    }

    if (!$filter->validate()) {
        foreach ($filter->getErrors() as $field => $messages) {
            Console::error("{$field}: " . implode(', ', $messages));
        }
        return ExitCode::UNSPECIFIED_ERROR;
    }

    $query = $this->buildBaseQuery();
    FeedExportFilter::applyToQuery($query, $filter);

    $count = (int) $query->count();
    $outputPath = $filter->resolveOutputPath();

    if (!$this->yes && !$this->nonInteractive) {
        $this->printExportSummary($filter, $count, $outputPath);
        if (!$this->confirm('Lanjutkan export?')) {
            Console::output('Export dibatalkan.');
            return ExitCode::OK;
        }
    }

    if ($count === 0) {
        Console::error('[feed] Peringatan: Tidak ada dokumen yang cocok. File tidak ditulis.');
        return ExitCode::UNSPECIFIED_ERROR;
    }

    try {
        $dokumen = $this->enrichRows($this->fetchDocuments($query));
        $bytes = $this->writeJsonFile($outputPath, $dokumen);
        Console::output("[feed] Berhasil: {$outputPath} ({$count} dokumen, {$bytes} bytes)");
        return ExitCode::OK;
    } catch (\Exception $e) {
        \Yii::error('[feed] Gagal export: ' . $e->getMessage(), 'feed');
        Console::error('[feed] ERROR: ' . $e->getMessage());
        return ExitCode::UNSPECIFIED_ERROR;
    }
}
```

- [ ] **Step 3: Implement buildFilterFromFlags()**

```php
private function buildFilterFromFlags(): FeedExportFilter
{
    return new FeedExportFilter([
        'tipe' => $this->tipe,
        'typeId' => $this->typeId,
        'dateField' => $this->dateField,
        'from' => $this->from,
        'to' => $this->to,
        'output' => $this->output,
    ]);
}
```

- [ ] **Step 4: Implement buildFilterInteractively()**

```php
private function buildFilterInteractively(): FeedExportFilter
{
    $tipeOptions = [
        '' => 'Semua',
        (string) DokumenJdih::TYPE_PERATURAN => 'Peraturan',
        (string) DokumenJdih::TYPE_MONOGRAFI => 'Monografi',
        (string) DokumenJdih::TYPE_ARTIKEL => 'Artikel',
        (string) DokumenJdih::TYPE_PUTUSAN => 'Putusan',
    ];
    $tipe = $this->select('Pilih tipe dokumen:', $tipeOptions);

    $typeId = null;
    if ($tipe === (string) DokumenJdih::TYPE_PERATURAN) {
        $types = JenisPeraturan::find()
            ->where(['parent_id' => 1])
            ->orderBy(['name' => SORT_ASC])
            ->all();
        $typeOptions = ['' => 'Semua'] + \yii\helpers\ArrayHelper::map($types, 'id', 'name');
        $selected = $this->select('Pilih jenis peraturan:', $typeOptions);
        $typeId = $selected !== '' ? (int) $selected : null;
    }

    $dateFieldOptions = [
        '' => 'Tanpa filter tanggal',
        'updated_at' => 'Tanggal diperbarui (updated_at)',
        'tanggal_pengundangan' => 'Tanggal pengundangan',
        'tanggal_penetapan' => 'Tanggal penetapan',
    ];
    $dateField = $this->select('Pilih field tanggal:', $dateFieldOptions);

    $from = null;
    $to = null;
    if ($dateField !== '') {
        $from = $this->prompt('Dari tanggal (Y-m-d, kosongkan untuk abaikan):', ['default' => '']);
        $to = $this->prompt('Sampai tanggal (Y-m-d, kosongkan untuk abaikan):', ['default' => '']);
        $from = $from !== '' ? $from : null;
        $to = $to !== '' ? $to : null;
    }

    return new FeedExportFilter([
        'tipe' => $tipe !== '' ? (int) $tipe : null,
        'typeId' => $typeId,
        'dateField' => $dateField !== '' ? $dateField : null,
        'from' => $from,
        'to' => $to,
        'output' => $this->output,
    ]);
}
```

- [ ] **Step 5: Implement printExportSummary()**

```php
private function printExportSummary(FeedExportFilter $filter, int $count, string $outputPath): void
{
    Console::output('');
    Console::output('Ringkasan export:');
    Console::output('  Tipe       : ' . ($filter->tipe ?? 'Semua'));
    Console::output('  Type ID    : ' . ($filter->typeId ?? 'Semua'));
    Console::output('  Date field : ' . ($filter->dateField ?? '-'));
    Console::output('  From       : ' . ($filter->from ?? '-'));
    Console::output('  To         : ' . ($filter->to ?? '-'));
    Console::output('  Output     : ' . $outputPath);
    Console::output('  Dokumen    : ' . $count);
    Console::output('');
}
```

- [ ] **Step 6: Commit**

```bash
git add console/controllers/FeedController.php
git commit -m "feat: add interactive feed/export-document command"
```

---

### Task 4: Manual verification

**Files:** none (verification only)

- [ ] **Step 1: Non-interactive export with tipe filter**

Run: `php yii feed/export-document --nonInteractive --tipe=1 --yes`

Expected: JSON written under `feed/export/peraturan.json`; `document.json` mtime unchanged

- [ ] **Step 2: Non-interactive export with date range**

Run: `php yii feed/export-document --nonInteractive --tipe=1 --dateField=tanggal_pengundangan --from=2020-01-01 --to=2024-12-31 --yes`

Expected: filename contains `pengundangan` and date range slug

- [ ] **Step 3: Invalid flag rejected**

Run: `php yii feed/export-document --nonInteractive --dateField=invalid --from=2024-01-01`

Expected: error on `dateField`, exit non-zero, no file written

- [ ] **Step 4: Cron path regression**

Run: `php yii feed/generate-document`

Expected: still writes `feed/document.json` only

- [ ] **Step 5: Run unit tests**

Run: `vendor/bin/codecept run -c common unit/components/FeedExportFilterTest.php -v`

Expected: PASS

- [ ] **Step 6: Commit (if any fixups needed)**

Only if verification required small fixes.

---

## Spec Coverage Checklist

| Spec requirement | Task |
|------------------|------|
| New `export-document` command | Task 3 |
| `generate-document` unchanged for cron | Task 2 (refactor only), Task 4 step 4 |
| Filters: tipe, typeId hierarchy, date field + range | Task 1 + Task 3 |
| Interactive + `--nonInteractive` flags | Task 3 |
| Output under `feed/export/`, never `document.json` | Task 1 `resolveOutputPath()` + Task 3 |
| Atomic write shared | Task 2 `writeJsonFile()` |
| Zero results → no file written | Task 3 |
| Output path safety | Task 1 test + `resolveOutputPath()` |
| Unit tests | Task 1 |
