<?php

use yii\helpers\Html;
use yii\widgets\ListView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $searchModel frontend\models\search\BeritaSearch */

$this->title = 'Berita & Artikel Hukum';
$this->registerMetaTag(['name' => 'description', 'content' => 'Berita dan artikel hukum terbaru - informasi terkini seputar dunia hukum Indonesia di ILDIS.']);
$this->registerMetaTag(['name' => 'robots', 'content' => 'index, follow']);
?>

<div class="berita-index-wrapper" style="background-color: #f8fafc; min-height: 100vh; padding-top: 100px;">
    <div class="container py-5">
        <div class="row">
            <!-- Sidebar (Search) -->
            <div class="col-lg-3 mb-4">
                <div class="side-bar sticky-top" style="top: 120px;">
                    <div class="card border-0 rounded-4 shadow-sm overflow-hidden">
                        <div class="card-header border-0 py-3" style="background-color: #f1f5f9;">
                            <h5 class="card-title fw-bold mb-0 text-dark-blue small text-uppercase tracking-wider">
                                <i class="ti-search mr-2"></i> Cari Berita
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <?= $this->render('_search', ['model' => $searchModel]); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- News List -->
            <div class="col-lg-9">
                <div class="results-header mb-4 d-flex justify-content-between align-items-center">
                    <h1 class="h3 font-weight-700 text-dark-blue mb-0"><?= Html::encode($this->title) ?></h1>
                    <div class="small text-muted font-weight-500">
                        <?= number_format($dataProvider->getTotalCount()) ?> Berita ditemukan
                    </div>
                </div>

                <?= ListView::widget([
                    'dataProvider' => $dataProvider,
                    'summary' => false,
                    'itemOptions' => ['tag' => false],
                    'options' => ['class' => 'news-list'],
                    'itemView' => '_data',
                    'pager' => [
                        'options' => ['class' => 'pagination justify-content-center mt-5'],
                        'linkOptions' => ['class' => 'page-link border-0 shadow-sm rounded-3 mx-1'],
                        'pageCssClass' => 'page-item',
                        'activePageCssClass' => 'active',
                        'disabledPageCssClass' => 'disabled',
                        'prevPageLabel' => '<i class="ti-arrow-left"></i>',
                        'nextPageLabel' => '<i class="ti-arrow-right"></i>',
                    ],
                ]) ?>
            </div>
        </div>
    </div>
</div>

<style>
.text-dark-blue { color: #1e293b; }
.font-weight-700 { font-weight: 700; }
.font-weight-500 { font-weight: 500; }
.tracking-wider { letter-spacing: 0.05em; }
.pagination .page-item.active .page-link {
    background-color: #3b82f6;
    border-color: #3b82f6;
}
.pagination .page-link {
    color: #475569;
    padding: 10px 16px;
}
</style>
