<?php

namespace backend\controllers;

use Yii;
use backend\models\CatatanVerifikasi;
use backend\models\DokumenJdih;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * CatatanVerifikasiController implements the CRUD actions for CatatanVerifikasi model.
 */
class CatatanVerifikasiController extends Controller
{
    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'verbs' => [
                    'class' => VerbFilter::className(),
                    'actions' => [
                        'delete' => ['POST'],
                    ],
                ],
            ]
        );
    }

    /**
     * Lists all CatatanVerifikasi models.
     * @return mixed
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => CatatanVerifikasi::find(),
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single CatatanVerifikasi model.
     * @param int $id ID
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new CatatanVerifikasi model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate($id)
    {
        $model = new CatatanVerifikasi();

        $model->dokumen_id = $id;

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->save()) {
                return $this->redirect(['verifikasi/view', 'id' => $id]);
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionTanggapan($id)
    {
        $model = new CatatanVerifikasi();

        $model->dokumen_id = $id;

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionPeraturan($id)
    {
        return $this->renderCatatanAndPublish($id, 'peraturan/index');
    }

    public function actionMonografi($id)
    {
        return $this->renderCatatanAndPublish($id, 'monografi/index');
    }

    public function actionArtikel($id)
    {
        return $this->renderCatatanAndPublish($id, 'artikel/index');
    }

    public function actionPutusan($id)
    {
        return $this->renderCatatanAndPublish($id, 'putusan/index');
    }

    /**
     * Renders the verification-note form. On valid POST it saves the note and
     * flips the related document to is_publish = 1 atomically, then redirects
     * back to the type-specific index.
     *
     * @param int $dokumenId target document id
     * @param string $redirectRoute Yii route to redirect to on success (e.g. 'peraturan/index')
     * @return \yii\web\Response|string
     */
    private function renderCatatanAndPublish($dokumenId, string $redirectRoute)
    {
        $model = new CatatanVerifikasi();
        $model->dokumen_id = $dokumenId;

        if ($this->request->isPost && $model->load($this->request->post())) {
            if ($this->saveCatatanAndPublish($model, $dokumenId, $redirectRoute)) {
                Yii::$app->session->setFlash(
                    'success',
                    'Catatan Verifikasi sudah dibuat dan dokumen telah diverifikasi.'
                );
                return $this->redirect([$redirectRoute]);
            }
        } elseif (!$this->request->isPost) {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Save a verification note and flip the related document to is_publish = 1 atomically.
     * Returns true on commit, false on rollback (flash error already set).
     *
     * @throws NotFoundHttpException when the related document does not exist
     */
    private function saveCatatanAndPublish(CatatanVerifikasi $model, int $dokumenId, string $logTag): bool
    {
        $dokumen = DokumenJdih::findOne($dokumenId);
        if ($dokumen === null) {
            throw new NotFoundHttpException('Dokumen tidak ditemukan.');
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (!$model->save()) {
                throw new \RuntimeException(
                    'Gagal menyimpan catatan verifikasi: ' . json_encode($model->getErrors())
                );
            }

            $dokumen->is_publish = 1;
            if (!$dokumen->save(false)) {
                throw new \RuntimeException(
                    'Gagal memverifikasi dokumen: ' . json_encode($dokumen->getErrors())
                );
            }

            $transaction->commit();
            return true;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error('[catatan-verifikasi/' . $logTag . '] ' . $e->getMessage(), __METHOD__);
            Yii::$app->session->setFlash('error', $e->getMessage());
            return false;
        }
    }

    /**
     * Updates an existing CatatanVerifikasi model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing CatatanVerifikasi model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the CatatanVerifikasi model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return CatatanVerifikasi the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = CatatanVerifikasi::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
