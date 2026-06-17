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
                <?= $this->render('_sidebar', ['searchModel' => $searchModel]) ?>
            </div>

            <!-- News List -->
            <div class="col-lg-9">
                <div class="berita-page-header mb-4 pb-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2">
                        <h1 class="berita-page-header__title mb-0"><?= Html::encode($this->title) ?></h1>
                        <span class="berita-page-header__count">
                            <?= number_format($dataProvider->getTotalCount()) ?> berita
                        </span>
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

<?= $this->render('_berita-shared-styles') ?>

<style>
.berita-page-header {
    border-bottom: 1px solid #e2e8f0;
}

.berita-page-header__title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1a2752;
    letter-spacing: -0.01em;
    line-height: 1.3;
}

.berita-page-header__count {
    font-size: 0.875rem;
    color: #64748b;
    white-space: nowrap;
}

.news-list-card {
    background: #ffffff;
    border: 1px solid #e8edf4 !important;
    border-radius: 0.75rem;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.news-item .news-list-card:hover {
    border-color: #cbd5e1 !important;
    box-shadow: 0 4px 16px rgba(26, 39, 82, 0.06) !important;
}

.news-image-wrapper {
    min-height: 180px;
    overflow: hidden;
}

.news-list-card__image {
    object-fit: cover;
}

.news-list-card__date {
    font-size: 0.8125rem;
    color: #64748b;
    margin-bottom: 0.5rem;
}

.news-list-card__title {
    font-size: 1.0625rem;
    font-weight: 600;
    line-height: 1.4;
    margin: 0 0 0.625rem;
}

.news-list-card__title a {
    color: #1a2752;
    text-decoration: none;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.news-list-card__title a:hover {
    color: #274685;
}

.news-list-card__excerpt {
    font-size: 0.9rem;
    line-height: 1.6;
    color: #64748b;
    margin-bottom: 0.75rem;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.news-read-more {
    font-size: 0.875rem;
    font-weight: 600;
    color: #1a2752;
    text-decoration: none;
}

.news-read-more:hover {
    color: #274685;
    text-decoration: underline;
    text-underline-offset: 2px;
}

.pagination .page-item.active .page-link {
    background-color: #1a2752;
    border-color: #1a2752;
}
.pagination .page-link {
    color: #475569;
    padding: 10px 16px;
}
.pagination .page-link:hover {
    color: #1a2752;
}
</style>
