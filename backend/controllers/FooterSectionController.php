<?php

namespace backend\controllers;

use Yii;
use common\models\FooterSection;
use backend\models\FooterSectionSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

class FooterSectionController extends Controller
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
        $searchModel = new FooterSectionSearch();
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
        $model = new FooterSection();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Bagian footer berhasil ditambahkan');
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Bagian footer berhasil diubah');
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionDelete($id)
    {
        try {
            $this->findModel($id)->delete();
            Yii::$app->session->setFlash('success', 'Bagian footer berhasil dihapus');
        } catch (\yii\db\IntegrityException $e) {
            Yii::$app->session->setFlash('error', 'Bagian footer tidak dapat dihapus karena memiliki link terkait');
        }

        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        if (($model = FooterSection::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Halaman yang dicari tidak ada.');
    }
}