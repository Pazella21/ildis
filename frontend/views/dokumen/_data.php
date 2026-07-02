<?php

use yii\helpers\Html;
use frontend\models\DataLampiran;

$domain = yii\helpers\Url::base(true);
$views = (int) ($model->hit_see ?? 0);
$downloads = (int) ($model->hit_download ?? 0);

?>

<div class="search-result-item">
    <div class="search-result-meta">
        <span class="meta-badge">
            <?= Html::a($model->bentuk_peraturan ?: 'Dokumen', ['/dokumen/index2', 'id' => $model->bentuk_peraturan], ['class' => 'text-decoration-none', 'style' => 'color: inherit;']); ?>
        </span>
        <span class="search-result-meta__sep" aria-hidden="true">&bull;</span>
        <span class="search-result-meta__year"><?= $model->tahun_terbit ?: '-'; ?></span>
    </div>

    <h3 class="search-result-title">
        <?= Html::a(Html::encode($model->judul), ['/dokumen/view', 'id' => $model->id], ['title' => 'Lihat detail dokumen']); ?>
    </h3>

    <div class="search-result-actions">
        <?php
        $lampiran = DataLampiran::find()->where(['id_dokumen' => $model->id])->one();

        if (!empty($lampiran)) {
            echo Html::a('<i class="bi bi-file-earmark-pdf text-danger"></i> Dokumen', ['/dokumen/download', 'id' => $lampiran->dokumen_lampiran, 'docId' => $model->id], [
                'class' => 'btn-doc-action btn-doc-primary',
                'title' => 'Unduh/Lihat Dokumen'
            ]);
        }

        if (!empty($model->abstrak)) {
            echo Html::a('<i class="bi bi-file-earmark-text text-primary"></i> Abstrak', ['/dokumen/download', 'id' => $model->abstrak, 'docId' => $model->id], [
                'class' => 'btn-doc-action btn-doc-outline',
                'title' => 'Unduh/Lihat Abstrak'
            ]);
        }
        ?>
        <span class="doc-list-stats" aria-label="Statistik dokumen">
            <span class="doc-list-stats__item" title="Jumlah dilihat">
                <i class="bi bi-eye" aria-hidden="true"></i>
                <span><?= Html::encode(number_format($views, 0, ',', '.')) ?> dilihat</span>
            </span>
            <span class="doc-list-stats__item" title="Jumlah diunduh">
                <i class="bi bi-download" aria-hidden="true"></i>
                <span><?= Html::encode(number_format($downloads, 0, ',', '.')) ?> diunduh</span>
            </span>
        </span>
    </div>
</div>
