<?php

/* @var $this yii\web\View */
/* @var $searchModel frontend\models\search\BeritaSearch */
?>

<div class="side-bar sticky-top berita-sidebar" style="top: 120px;">
    <div class="berita-sidebar__panel">
        <h2 class="berita-sidebar__title">
            <i class="bi bi-search" aria-hidden="true"></i> Cari berita
        </h2>
        <?= $this->render('_search', ['model' => $searchModel]); ?>
    </div>
</div>
