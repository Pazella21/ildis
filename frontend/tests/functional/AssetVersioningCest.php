<?php

namespace frontend\tests\functional;

use frontend\tests\FunctionalTester;

class AssetVersioningCest
{
    public function customAssetsHaveCacheBustingQuery(FunctionalTester $I): void
    {
        $I->amOnPage('/');
        $I->seeInSource('style.css?v=');
        $I->seeInSource('main.js?v=');
        $I->seeInSource('lazyload.css?v=');
        $I->dontSeeInSource('bootstrap.min.css?v=');
        $I->dontSeeInSource('aos.js?v=');
    }
}
