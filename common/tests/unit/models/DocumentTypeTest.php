<?php

namespace common\tests\unit\models;

use Codeception\Test\Unit;
use common\components\DocumentGroup;
use common\models\DocumentType;

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
