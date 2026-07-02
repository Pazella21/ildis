<?php

namespace frontend\controllers;

use common\components\StatistikService;
use yii\web\Controller;

class StatistikController extends Controller
{
    public function actionIndex()
    {
        return $this->render('index', [
            'stats' => StatistikService::getSummary(),
        ]);
    }
}
