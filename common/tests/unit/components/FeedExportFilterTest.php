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
