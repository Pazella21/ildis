<?php

use common\components\DocumentGroup;
use yii\widgets\ListView;

/** @var yii\web\View $this */
/** @var frontend\models\DokumenSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var common\models\DocumentType|null $currentType */

$pageTitle = $currentType
    ? ucwords(strtolower($currentType->name))
    : DocumentGroup::label(DocumentGroup::LEGISLATION_FORMATION);

$this->title = $pageTitle;
$this->registerMetaTag([
    'name' => 'description',
    'content' => 'Koleksi ' . $pageTitle . ' — Dokumen pembentukan peraturan perundang-undangan.',
]);
$this->registerMetaTag(['name' => 'robots', 'content' => 'index, follow']);
?>

<h1 class="sr-only"><?= htmlspecialchars($pageTitle) ?></h1>
<div class="dokumen-index-wrapper" style="background-color: #f8fafc; min-height: 100vh; padding-top: 80px;">
    <div class="container py-5">
        <div class="row">
            <?= $this->render('_search-sidebar', ['model' => $searchModel]) ?>

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
                        'pager' => require __DIR__ . '/_pager.php',
                    ]) ?>
                </div>
            </div>
        </div>
    </div>
</div>
