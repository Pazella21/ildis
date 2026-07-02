<?php

namespace backend\controllers;

use backend\models\SurveyKepuasanSearch;
use common\models\SurveyKepuasan;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

class SurveyKepuasanController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $searchModel = new SurveyKepuasanSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'aggregate' => SurveyKepuasan::getAggregateStats(),
        ]);
    }

    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        return $this->redirect(['index']);
    }

    protected function findModel($id): SurveyKepuasan
    {
        if (($model = SurveyKepuasan::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Data survey tidak ditemukan.');
    }
}
