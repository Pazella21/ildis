<?php

/**
 * Shared LinkPager config for dokumen listing pages.
 */
return [
    'options' => ['class' => 'pagination jdih-pagination justify-content-center'],
    'linkOptions' => ['class' => 'page-link'],
    'linkContainerOptions' => ['class' => 'page-item'],
    'pageCssClass' => 'page-item',
    'prevPageCssClass' => 'page-item',
    'nextPageCssClass' => 'page-item',
    'activePageCssClass' => 'active',
    'disabledPageCssClass' => 'disabled',
    'prevPageLabel' => '<i class="bi bi-chevron-left" aria-hidden="true"></i><span class="visually-hidden">Halaman sebelumnya</span>',
    'nextPageLabel' => '<i class="bi bi-chevron-right" aria-hidden="true"></i><span class="visually-hidden">Halaman berikutnya</span>',
    'maxButtonCount' => 5,
    'firstPageLabel' => false,
    'lastPageLabel' => false,
];
