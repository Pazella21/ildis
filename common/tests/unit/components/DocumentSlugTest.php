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
