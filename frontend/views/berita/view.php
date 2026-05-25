<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model frontend\models\Berita */
/* @var $model2 frontend\models\search\BeritaSearch */

$this->title = $model->judul;

// --- SEO Metatags & Open Graph ---
$baseUrl = Url::to(['/'], true);
$currentUrl = Url::current([], true);
$desc = !empty($model->isi) ? strip_tags($model->isi) : $model->judul;
$desc = mb_strimwidth($desc, 0, 160, "...");

$this->registerMetaTag(['name' => 'description', 'content' => $desc]);

// Open Graph
$this->registerMetaTag(['property' => 'og:title', 'content' => $this->title]);
$this->registerMetaTag(['property' => 'og:description', 'content' => $desc]);
$this->registerMetaTag(['property' => 'og:type', 'content' => 'article']);
$this->registerMetaTag(['property' => 'og:url', 'content' => $currentUrl]);
if (!empty($model->image)) {
    $this->registerMetaTag(['property' => 'og:image', 'content' => $baseUrl . 'common/dokumen/' . $model->image]);
}

// Twitter
$this->registerMetaTag(['name' => 'twitter:card', 'content' => 'summary_large_image']);
$this->registerMetaTag(['name' => 'twitter:title', 'content' => $this->title]);
$this->registerMetaTag(['name' => 'twitter:description', 'content' => $desc]);

$this->params['breadcrumbs'][] = ['label' => 'Berita', 'url' => ['index']];
$this->params['breadcrumbs'][] = Html::encode($this->title);
?>

<div class="berita-view-wrapper" style="background-color: #f8fafc; min-height: 100vh; padding: 100px 0 40px 0;">
    <div class="container py-5">
        <div class="row">
            <!-- Main Content Area -->
            <div class="col-lg-8">
                <article class="bg-white rounded-4 shadow-sm overflow-hidden mb-4">
                    <!-- News Header Image -->
                    <?php if ($model->image): ?>
                        <div class="news-hero-image" style="height: 400px; overflow: hidden;">
                            <?= Html::img('@web/common/dokumen/' . $model->image, [
                                'class' => 'w-100 h-100 object-fit-cover',
                                'style' => 'object-fit: cover;',
                                'alt' => $model->judul
                            ]) ?>
                        </div>
                    <?php endif; ?>

                    <div class="p-4 p-md-5">
                        <!-- News Meta -->
                        <div class="mb-4">
                            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill font-weight-600 small">
                                <i class="ti-calendar mr-1"></i> <?= \common\components\DateHelper::formatIndonesian($model->tanggal) ?>
                            </span>
                        </div>

                        <!-- News Title -->
                        <h1 class="h2 font-weight-700 text-dark-blue mb-4">
                            <?= Html::encode($model->judul) ?>
                        </h1>

                        <!-- News Content -->
                        <div class="news-content text-dark lh-lg" style="text-align: justify; font-size: 1.1rem;">
                            <?= $model->isi ?>
                        </div>

                        <!-- Action Bar -->
                        <div class="mt-5 pt-4 border-top border-light d-flex justify-content-between align-items-center">
                            <?= Html::a('<i class="ti-arrow-left mr-2"></i> Kembali ke Daftar Berita', ['index'], [
                                'class' => 'text-muted text-decoration-none font-weight-600 small text-uppercase tracking-wider'
                            ]) ?>
                            
                            <!-- Share Buttons (Placeholder) -->
                            <div class="share-links d-flex gap-3">
                                <span class="text-muted small font-weight-600 text-uppercase tracking-wider">Bagikan:</span>
                                <a href="#" class="text-muted hover-primary"><i class="ti-facebook"></i></a>
                                <a href="#" class="text-muted hover-primary"><i class="ti-twitter-alt"></i></a>
                                <a href="#" class="text-muted hover-primary"><i class="ti-linkedin"></i></a>
                            </div>
                        </div>
                    </div>
                </article>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="sticky-top" style="top: 120px;">
                    <!-- Search Widget -->
                    <div class="card border-0 rounded-4 shadow-sm overflow-hidden mb-4">
                        <div class="card-header border-0 py-3" style="background-color: #f1f5f9;">
                            <h5 class="card-title fw-bold mb-0 text-dark-blue small text-uppercase tracking-wider">
                                <i class="ti-search mr-2"></i> Cari Berita
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <?= $this->render('_search', ['model' => $model2]); ?>
                        </div>
                    </div>

                    <!-- Related News Placeholder or Additional Info -->
                    <div class="bg-primary bg-opacity-10 border border-primary border-opacity-20 rounded-4 p-4 text-center">
                        <i class="ti-info-alt text-primary h1 d-block mb-3"></i>
                        <h5 class="font-weight-700 text-dark-blue mb-2 small text-uppercase tracking-wider">Informasi Publik</h5>
                        <p class="text-muted small mb-0">Dapatkan informasi hukum terbaru dan terpercaya melalui portal JDIH ini.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.text-dark-blue { color: #1e293b; }
.font-weight-600 { font-weight: 600; }
.font-weight-700 { font-weight: 700; }
.tracking-wider { letter-spacing: 0.05em; }
.hover-primary:hover { color: #3b82f6 !important; }
.lh-lg { line-height: 1.8 !important; }
.rounded-4 { border-radius: 1rem !important; }
.news-content p { margin-bottom: 1.5rem; }
</style>
