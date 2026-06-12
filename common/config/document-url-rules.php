<?php

/**
 * Shared pretty URL rules for public document routes.
 * Legacy query-string URLs (e.g. /dokumen/view?id=123) remain valid via Yii2 defaults.
 */
return [
    'dokumen/view/<id:\d+>' => 'dokumen/view',
];
