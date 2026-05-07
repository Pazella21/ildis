<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use frontend\models\Dokumen;
use frontend\models\Berita;

class SitemapController extends Controller
{
    public function behaviors()
    {
        return [
            [
                'class' => 'yii\filters\ContentNegotiator',
                'formats' => [
                    'application/xml' => Response::FORMAT_XML,
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $urls = [];

        $urls[] = ['loc' => Yii::$app->urlManager->createAbsoluteUrl(['/']), 'changefreq' => 'daily', 'priority' => '1.0'];
        $urls[] = ['loc' => Yii::$app->urlManager->createAbsoluteUrl(['/site/kontak']), 'changefreq' => 'monthly', 'priority' => '0.3'];
        $urls[] = ['loc' => Yii::$app->urlManager->createAbsoluteUrl(['/site/sekilas-sejarah']), 'changefreq' => 'monthly', 'priority' => '0.3'];
        $urls[] = ['loc' => Yii::$app->urlManager->createAbsoluteUrl(['/site/visi']), 'changefreq' => 'monthly', 'priority' => '0.3'];
        $urls[] = ['loc' => Yii::$app->urlManager->createAbsoluteUrl(['/site/misi']), 'changefreq' => 'monthly', 'priority' => '0.3'];
        $urls[] = ['loc' => Yii::$app->urlManager->createAbsoluteUrl(['/site/sto']), 'changefreq' => 'monthly', 'priority' => '0.3'];
        $urls[] = ['loc' => Yii::$app->urlManager->createAbsoluteUrl(['/site/dasar-hukum']), 'changefreq' => 'monthly', 'priority' => '0.3'];
        $urls[] = ['loc' => Yii::$app->urlManager->createAbsoluteUrl(['/dokumen/peraturan']), 'changefreq' => 'daily', 'priority' => '0.9'];
        $urls[] = ['loc' => Yii::$app->urlManager->createAbsoluteUrl(['/dokumen/monografi']), 'changefreq' => 'daily', 'priority' => '0.9'];
        $urls[] = ['loc' => Yii::$app->urlManager->createAbsoluteUrl(['/dokumen/artikel']), 'changefreq' => 'daily', 'priority' => '0.9'];
        $urls[] = ['loc' => Yii::$app->urlManager->createAbsoluteUrl(['/dokumen/putusan']), 'changefreq' => 'daily', 'priority' => '0.9'];

        $dokumen = Dokumen::find()->select(['id', 'updated_at'])->where(['sembunyikan_di_opac' => null])->orWhere(['sembunyikan_di_opac' => ''])->orderBy(['id' => SORT_DESC])->asArray()->all();
        foreach ($dokumen as $doc) {
            $urls[] = [
                'loc' => Yii::$app->urlManager->createAbsoluteUrl(['/dokumen/view', 'id' => $doc['id']]),
                'changefreq' => 'weekly',
                'priority' => '0.7',
                'lastmod' => !empty($doc['updated_at']) ? date('c', strtotime($doc['updated_at'])) : null,
            ];
        }

        $berita = Berita::find()->select(['id', 'updated_at'])->where(['status' => 1])->orderBy(['id' => SORT_DESC])->asArray()->all();
        foreach ($berita as $item) {
            $urls[] = [
                'loc' => Yii::$app->urlManager->createAbsoluteUrl(['/berita/view', 'id' => $item['id']]),
                'changefreq' => 'weekly',
                'priority' => '0.6',
                'lastmod' => !empty($item['updated_at']) ? date('c', strtotime($item['updated_at'])) : null,
            ];
        }

        Yii::$app->response->format = Response::FORMAT_RAW;
        $response = Yii::$app->response;
        $response->headers->set('Content-Type', 'application/xml; charset=utf-8');

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $url) {
            echo '  <url>' . "\n";
            echo '    <loc>' . htmlspecialchars($url['loc'], ENT_XML1, 'UTF-8') . '</loc>' . "\n";
            if (!empty($url['lastmod'])) {
                echo '    <lastmod>' . $url['lastmod'] . '</lastmod>' . "\n";
            }
            echo '    <changefreq>' . $url['changefreq'] . '</changefreq>' . "\n";
            echo '    <priority>' . $url['priority'] . '</priority>' . "\n";
            echo '  </url>' . "\n";
        }
        echo '</urlset>';

        Yii::$app->end();
    }
}