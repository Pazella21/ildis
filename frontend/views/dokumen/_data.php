<?php

use yii\helpers\Html;
use frontend\models\DataLampiran;

$domain = yii\helpers\Url::base(true);

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
            echo Html::a('<i class="bi bi-file-earmark-pdf text-danger"></i> Dokumen', ['/common/dokumen/' . $lampiran->dokumen_lampiran], [
                'class' => 'btn-doc-action btn-doc-primary',
                'target' => '_blank',
                'title' => 'Unduh/Lihat Dokumen'
            ]);
        }

        if (!empty($model->abstrak)) {
            echo Html::a('<i class="bi bi-file-earmark-text text-primary"></i> Abstrak', ['/common/dokumen/' . $model->abstrak], [
                'class' => 'btn-doc-action btn-doc-outline',
                'target' => '_blank',
                'title' => 'Unduh/Lihat Abstrak'
            ]);
        }
        ?>
    </div>
</div>
