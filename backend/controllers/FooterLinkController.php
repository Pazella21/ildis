<?php

namespace backend\controllers;

use Yii;
use common\models\FooterLink;
use common\models\FooterSection;
use backend\models\FooterLinkSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

class FooterLinkController extends Controller
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
        $searchModel = new FooterLinkSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    public function actionCreate()
    {
        $model = new FooterLink();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Link footer berhasil ditambahkan');
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $model,
            'sections' => $this->getSectionsList(),
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Link footer berhasil diubah');
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
            'sections' => $this->getSectionsList(),
        ]);
    }

    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success', 'Link footer berhasil dihapus');
        return $this->redirect(['index']);
    }

    private function getSectionsList()
    {
        return \yii\helpers\ArrayHelper::map(
            FooterSection::find()->orderBy(['sort_order' => SORT_ASC])->all(),
            'id',
            'title'
        );
    }

    protected function findModel($id)
    {
        if (($model = FooterLink::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Halaman yang dicari tidak ada.');
    }
}