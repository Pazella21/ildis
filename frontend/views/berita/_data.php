<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $model frontend\models\Berita */
?>

<div class="news-item mb-4">
    <div class="card border-0 rounded-4 shadow-sm h-100 overflow-hidden" style="transition: transform 0.2s ease, box-shadow 0.2s ease; border: 1px solid #f1f5f9 !important;">
        <div class="row g-0 h-100">
            <!-- News Image -->
            <div class="col-md-4">
                <div class="news-image-wrapper h-100 position-relative" style="min-height: 200px; overflow: hidden;">
                    <?= Html::a(
                        Html::img('@web/common/dokumen/' . $model->image, [
                            'class' => 'w-100 h-100 object-fit-cover position-absolute',
                            'style' => 'object-fit: cover; transition: transform 0.5s ease;',
                            'alt' => $model->judul
                        ]), 
                        ['view', 'id' => $model->id],
                        ['class' => 'd-block h-100']
                    ) ?>
                </div>
            </div>
            
            <!-- News Content -->
            <div class="col-md-8">
                <div class="card-body p-4 d-flex flex-column h-100">
                    <div class="mb-2">
                        <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-1 rounded-pill small font-weight-600">
                            <i class="ti-calendar mr-1"></i> <?= \common\components\DateHelper::formatIndonesian($model->tanggal) ?>
                        </span>
                    </div>
                    
                    <h5 class="card-title mb-3">
                        <?= Html::a(Html::encode($model->judul), ['view', 'id' => $model->id], [
                            'class' => 'text-dark-blue font-weight-700 text-decoration-none hover-primary',
                            'style' => 'display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.4;'
                        ]) ?>
                    </h5>
                    
                    <p class="card-text text-muted small mb-4 flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                        <?= strip_tags($model->isi) ?>
                    </p>
                    
                    <div class="mt-auto pt-3 border-top border-light">
                        <?= Html::a('Baca Selengkapnya <i class="ti-arrow-right ml-1"></i>', ['view', 'id' => $model->id], [
                            'class' => 'btn btn-link p-0 text-primary font-weight-600 text-decoration-none small text-uppercase tracking-wider'
                        ]) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.news-item .card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.05) !important;
}
.news-item .card:hover .news-image-wrapper img {
    transform: scale(1.05);
}
.text-dark-blue { color: #1e293b; }
.font-weight-600 { font-weight: 600; }
.font-weight-700 { font-weight: 700; }
.hover-primary:hover { color: #3b82f6 !important; }
.tracking-wider { letter-spacing: 0.05em; }
</style>