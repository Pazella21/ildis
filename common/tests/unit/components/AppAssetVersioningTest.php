<?php

namespace common\tests\unit\components;

use Codeception\Test\Unit;
use frontend\assets\AppAsset;
use Yii;
use yii\web\Application;

class AppAssetVersioningTest extends Unit
{
    protected function _before(): void
    {
        parent::_before();
        if (!Yii::$app instanceof Application) {
            $config = \yii\helpers\ArrayHelper::merge(
                require Yii::getAlias('@frontend/config/main.php'),
                require Yii::getAlias('@frontend/config/main-local.php'),
                require Yii::getAlias('@frontend/config/test.php'),
            );
            new Application($config);
        }
    }

    public function testCustomAssetsHaveCacheBustingQuery(): void
    {
        $view = new \yii\web\View(['assetManager' => Yii::$app->assetManager]);
        $bundle = Yii::createObject(AppAsset::class);
        $bundle->publish(Yii::$app->assetManager);
        $bundle->registerAssetFiles($view);

        $extractUrl = static function ($file): string {
            if (is_string($file)) {
                return $file;
            }
            if (isset($file['url'])) {
                return $file['url'];
            }

            return $file[0] ?? '';
        };
        $cssUrls = array_map($extractUrl, $view->cssFiles);
        $jsTags = [];
        foreach ($view->jsFiles as $positionFiles) {
            foreach ($positionFiles as $tag) {
                $jsTags[] = $tag;
            }
        }
        $allUrls = implode(' ', $cssUrls) . ' ' . implode(' ', $jsTags);

        $this->assertStringContainsString('style.css?v=', $allUrls);
        $this->assertStringContainsString('main.js?v=', $allUrls);
        $this->assertStringContainsString('lazyload.css?v=', $allUrls);
        $this->assertStringNotContainsString('bootstrap.min.css?v=', $allUrls);
        $this->assertStringNotContainsString('aos.js?v=', $allUrls);
    }
}
