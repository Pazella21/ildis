<?php

use yii\helpers\Html;

/* @var $documentStats array{views: int, downloads: int} */
$views = (int) ($documentStats['views'] ?? 0);
$downloads = (int) ($documentStats['downloads'] ?? 0);
?>
<div class="dokumen-view-stats d-flex flex-wrap gap-3 mb-4" aria-label="Statistik dokumen">
    <span class="badge bg-light text-dark border px-3 py-2 rounded-pill">
        <i class="bi bi-eye me-1" aria-hidden="true"></i>
        <span><?= Html::encode(number_format($views, 0, ',', '.')) ?> dilihat</span>
    </span>
    <span class="badge bg-light text-dark border px-3 py-2 rounded-pill">
        <i class="bi bi-download me-1" aria-hidden="true"></i>
        <span><?= Html::encode(number_format($downloads, 0, ',', '.')) ?> diunduh</span>
    </span>
</div>
