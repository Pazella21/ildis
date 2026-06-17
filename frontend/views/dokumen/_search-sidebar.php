<?php

/* @var $this yii\web\View */
/* @var $model frontend\models\DokumenSearch */

$collapseId = 'dokumen-search-collapse';

$activeFilterCount = 0;
foreach ($model->attributes as $value) {
    if ($value !== '' && $value !== null) {
        $activeFilterCount++;
    }
}
$hasActiveFilters = $activeFilterCount > 0;

$collapseClasses = 'collapse d-lg-block dokumen-search-card__body';
if ($hasActiveFilters) {
    $collapseClasses .= ' show';
}
?>

<div class="col-lg-3 mb-4">
    <div class="side-bar sticky-top dokumen-search-sidebar">
        <div class="card border-0 dokumen-search-card">
            <div class="card-header border-0 dokumen-search-card__header">
                <button
                    class="dokumen-search-toggle d-lg-none w-100 d-flex align-items-center justify-content-between btn btn-link text-decoration-none p-0 border-0 shadow-none"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#<?= $collapseId ?>"
                    aria-expanded="<?= $hasActiveFilters ? 'true' : 'false' ?>"
                    aria-controls="<?= $collapseId ?>"
                >
                    <?= $this->render('_search-sidebar-title', [
                        'activeFilterCount' => $activeFilterCount,
                        'hasActiveFilters' => $hasActiveFilters,
                    ]) ?>
                    <i class="bi bi-chevron-down dokumen-search-toggle__icon flex-shrink-0 ms-3" aria-hidden="true"></i>
                </button>
                <div class="d-none d-lg-block">
                    <?= $this->render('_search-sidebar-title', [
                        'activeFilterCount' => $activeFilterCount,
                        'hasActiveFilters' => $hasActiveFilters,
                    ]) ?>
                </div>
            </div>
            <div id="<?= $collapseId ?>" class="<?= $collapseClasses ?>">
                <div class="card-body dokumen-search-card__form">
                    <?= $this->render('_search', ['model' => $model]) ?>
                </div>
            </div>
        </div>
    </div>
</div>
