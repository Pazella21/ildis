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
