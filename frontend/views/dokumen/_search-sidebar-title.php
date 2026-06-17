<?php

/* @var $this yii\web\View */
/* @var $activeFilterCount int */
/* @var $hasActiveFilters bool */
?>

<div class="dokumen-search-card__title-row">
    <span class="dokumen-search-card__icon" aria-hidden="true">
        <i class="bi bi-sliders"></i>
    </span>
    <span class="dokumen-search-card__title-text">
        <span class="dokumen-search-card__title">Pencarian</span>
        <?php if ($hasActiveFilters): ?>
            <span class="dokumen-search-card__badge"><?= (int) $activeFilterCount ?> aktif</span>
        <?php endif; ?>
    </span>
    <span class="dokumen-search-toggle__hint d-lg-none">Ketuk untuk tampilkan filter</span>
</div>
