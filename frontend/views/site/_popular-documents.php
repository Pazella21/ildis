<?php

use yii\helpers\Html;
use yii\helpers\Url;
use common\components\DocumentPopularityService;
use frontend\models\Dokumen;

/* @var $popularDocuments Dokumen[] */
if (empty($popularDocuments)) {
    return;
}
?>
<section class="popular-documents-section py-5 bg-white">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="h3 font-weight-bold text-dark mb-2">Dokumen Terpopuler</h2>
            <p class="text-muted mb-0">Dokumen yang paling banyak dilihat dan diunduh</p>
        </div>
        <div class="row g-4">
            <?php foreach ($popularDocuments as $doc): ?>
                <?php
                $score = DocumentPopularityService::getPopularityScore($doc);
                $docUrl = Url::to(['/dokumen/view', 'id' => $doc->id, 'slug' => $doc->getUrlSlug()]);
                ?>
                <div class="col-md-6 col-lg-4">
                    <article class="card h-100 border-0 shadow-sm rounded-3">
                        <div class="card-body d-flex flex-column">
                            <span class="badge bg-primary-subtle text-primary align-self-start mb-2">
                                <?= Html::encode($doc->jenis_peraturan ?: 'Dokumen') ?>
                            </span>
                            <h3 class="h6 font-weight-bold mb-3 flex-grow-1">
                                <?= Html::a(Html::encode($doc->judul), $docUrl, ['class' => 'text-dark text-decoration-none stretched-link']) ?>
                            </h3>
                            <div class="d-flex flex-wrap gap-2 small text-muted position-relative" style="z-index: 2;">
                                <span><i class="bi bi-eye" aria-hidden="true"></i> <?= number_format((int) ($doc->hit_see ?? 0)) ?></span>
                                <span><i class="bi bi-download" aria-hidden="true"></i> <?= number_format((int) ($doc->hit_download ?? 0)) ?></span>
                                <span class="ms-auto fw-semibold">Skor <?= number_format($score) ?></span>
                            </div>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
