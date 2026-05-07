<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ListView;

$this->title = 'Artikel Hukum';
$this->registerMetaTag(['name' => 'description', 'content' => 'Koleksi artikel hukum Indonesia - baca dan telusuri artikel hukum terbaru di ILDIS.']);
$this->registerMetaTag(['name' => 'robots', 'content' => 'index, follow']);
?>

<h1 class="sr-only">Artikel Hukum Indonesia</h1>
<div class="dokumen-index-wrapper" style="background-color: #f8fafc; min-height: 100vh; padding-top: 80px;">
    <!-- Main Content -->
    <div class="container py-5">
        <div class="row">
            <!-- Sidebar (Search Filters) -->
            <div class="col-lg-3 mb-4">
                <div class="side-bar sticky-top" style="top: 100px;">
                    <div class="card border-0 rounded-4 shadow-sm" style="overflow: hidden;">
                        <div class="card-header border-0 py-3" style="background-color: #f1f5f9;">
                            <h5 class="card-title fw-bold mb-0" style="color: #1a2752; font-size: 1.1rem;">
                                <i class="bi bi-search me-2"></i> Pencarian
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <?php echo $this->render('_search', ['model' => $searchModel]); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results List -->
            <div class="col-lg-9">
                <div class="results-container bg-white rounded-4 p-4 p-md-5" style="box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
                    <div class="results-summary mb-4 pb-3 border-bottom d-flex justify-content-between align-items-center">
                        <div class="small text-muted">
                            <?= $dataProvider->getTotalCount() > 0 ? "Menampilkan " . ($dataProvider->pagination->offset + 1) . " - " . min($dataProvider->pagination->offset + $dataProvider->pagination->limit, $dataProvider->getTotalCount()) . " dari " . number_format($dataProvider->getTotalCount()) . " dokumen" : "Tidak ada dokumen ditemukan" ?>
                        </div>
                    </div>

                    <?= ListView::widget([
                        'dataProvider' => $dataProvider,
                        'options' => ['class' => 'results-list'],
                        'itemOptions' => ['class' => 'item'],
                        'itemView' => '_data',
                        'summary' => false,
                        'pager' => [
                            'options' => ['class' => 'pagination justify-content-center mt-5'],
                            'linkOptions' => ['class' => 'page-link'],
                            'pageCssClass' => 'page-item',
                            'activePageCssClass' => 'active',
                            'disabledPageCssClass' => 'disabled',
                            'prevPageLabel' => '<i class="bi bi-chevron-left"></i>',
                            'nextPageLabel' => '<i class="bi bi-chevron-right"></i>',
                        ],
                    ]) ?>
                </div>
            </div>
        </div>
    </div>
</div>

