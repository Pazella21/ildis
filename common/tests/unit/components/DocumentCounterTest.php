<?php

namespace common\tests\unit\components;

use Codeception\Test\Unit;
use common\components\DocumentCounter;

class DocumentCounterTest extends Unit
{
    public function testGetCountsReturnsZeroForMissingDocument()
    {
        $counts = DocumentCounter::getCounts(999999999);
        $this->assertSame(0, $counts['views']);
        $this->assertSame(0, $counts['downloads']);
    }

    public function testFileBelongsToDocumentRejectsEmptyFilename()
    {
        $this->assertFalse(DocumentCounter::fileBelongsToDocument(1, ''));
    }
}
