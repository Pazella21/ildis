<?php

use common\urlRules\DocumentViewUrlRule;

/**
 * Shared pretty URL rules for public document routes.
 *
 * Canonical:  /dokumen/123-judul-slug
 * Also valid: /dokumen/view/123, /dokumen/view?id=123
 */
return [
    ['class' => DocumentViewUrlRule::class],
    'dokumen/view/<id:\d+>' => 'dokumen/view',
];
