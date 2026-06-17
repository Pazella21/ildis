<?php

namespace common\urlRules;

use common\components\DocumentSlug;
use yii\web\UrlRule;

class DocumentViewUrlRule extends UrlRule
{
    public $pattern = 'dokumen/<id:\d+>-<slug:[\w-]+>';

    public $route = 'dokumen/view';

    public function createUrl($manager, $route, $params)
    {
        if ($route !== $this->route || !isset($params['id'])) {
            return false;
        }

        if (empty($params['slug'])) {
            $params['slug'] = DocumentSlug::resolve((int) $params['id'], $params['judul'] ?? null);
        }

        unset($params['judul']);

        return parent::createUrl($manager, $route, $params);
    }
}
