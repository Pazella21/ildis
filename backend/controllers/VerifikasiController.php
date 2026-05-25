<?php

namespace backend\controllers;

use Yii;
use backend\models\DokumenJdih;
use backend\models\Eksemplar;
use backend\models\Pengarang;
use backend\models\LogPustakawan;
use common\components\DateHelper;
use backend\models\JenisPeraturan;
use backend\models\DataPengarang;
use backend\models\DataSubyek;
use backend\models\DataStatus;
use backend\models\DataLampiran;
use backend\models\HasilUjiMateri;
use backend\models\DokumenTerkait;
use backend\models\PeraturanTerkait;
use backend\models\search\DokumenJdihSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;
use yii\data\ActiveDataProvider;
use yii\helpers\Json;
use yii\base\UserException;

/**
 * VerifikasiController implements the CRUD actions for Peraturan model.
 */
class VerifikasiController extends Controller
{
    /**
     * @inheritdoc
     */
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

    /**
     * Lists all Peraturan models.
     * @return mixed
     */
    public function actionIndex($tahun=null)
    {
        $searchModel = new DokumenJdihSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }


    public function actionPeraturan($tahun=null)
    {
        $searchModel = new DokumenJdihSearch(['tahun_terbit'=>$tahun]);
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->andWhere(['tipe_dokumen'=>DokumenJdih::TYPE_PERATURAN]);
        return $this->render('index-peraturan', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionUpdatePeraturan($id)
    {
        $model = $this->findModel($id);
        $old_abstrak = $model->abstrak;

        if ($model->load(Yii::$app->request->post())) {

            $jenisperaturan = JenisPeraturan::find()->where(['name' => $model->jenis_peraturan])->one();
            if (!empty($jenisperaturan)) {
                $model->jenis_peraturan = $jenisperaturan->name;
                $model->singkatan_jenis = $jenisperaturan->singkatan;
            }

            $abstrak = UploadedFile::getInstance($model, 'abstrak');
            if (!empty($abstrak)) {
                $model->abstrak =  strtolower(preg_replace('/[^a-zA-Z0-9-_\.]/', '', $abstrak->name));
                $path = Yii::getAlias('@common') . '/dokumen/' . $model->abstrak;
                $abstrak->saveAs($path);
            } else {
                $model->abstrak = $old_abstrak;
            }

            $jenisperaturan = JenisPeraturan::findOne($model->jenis_peraturan);
            if (!empty($jenisperaturan)) {
                $model->jenis_peraturan = $jenisperaturan->name;
                // $model->singkatan_jenis = 'UU';
                $model->bentuk_peraturan = $jenisperaturan->name;
            }

            if ($model->save()) {
                $log = new LogPustakawan();
                $log->dokumen_id = $id;
                $log->controller = 'Peraturan';
                $log->aksi = 'Ubah Peraturan';
                $log->keterangan = 'User ' . \Yii::$app->user->identity->username . ' melakukan ubah data peraturan pada ' . DateHelper::formatIndonesian(date('Y-m-d H:i:s'));
                $log->save();
                Yii::$app->session->setFlash('success', 'Data Peraturan berhasil diubah');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        } else {
            return $this->render('update-peraturan', [
                'model' => $model,
            ]);
        }
    }


    public function actionMonografi($tahun=null)
    {
        $searchModel = new DokumenJdihSearch(['tahun_terbit'=>$tahun]);
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->andWhere(['tipe_dokumen'=>DokumenJdih::TYPE_MONOGRAFI]);
        return $this->render('index-monografi', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionArtikel($tahun=null)
    {
        $searchModel = new DokumenJdihSearch(['tahun_terbit'=>$tahun]);
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->andWhere(['tipe_dokumen'=>DokumenJdih::TYPE_ARTIKEL]);
        return $this->render('index-artikel', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionPutusan($tahun=null)
    {
        $searchModel = new DokumenJdihSearch(['tahun_terbit'=>$tahun]);
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->andWhere(['tipe_dokumen'=>DokumenJdih::TYPE_PUTUSAN]);
        return $this->render('index-putusan', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
    /**
     * Displays a single Peraturan model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {

        $model = $this->findModel($id);

        $pengarangProvider = new ActiveDataProvider([
            'query' => DataPengarang::find()->where(['id_dokumen' => $id]),
            'pagination' => ['pageSize' => 10]
        ]);

        $subyek = new ActiveDataProvider([
            'query' => DataSubyek::find()->where(['id_dokumen' => $id]),
            'pagination' => ['pageSize' => 10]
        ]);

        $lampiran = new ActiveDataProvider([
            'query' => DataLampiran::find()->where(['id_dokumen' => $id]),
            'pagination' => ['pageSize' => 10]
        ]);

        $peraturan = new ActiveDataProvider([
            'query' => PeraturanTerkait::find()->where(['id_dokumen' => $id]),
            'pagination' => ['pageSize' => 10]
        ]);

        $dokumen = new ActiveDataProvider([
            'query' => DokumenTerkait::find()->where(['id_dokumen' => $id]),
            'pagination' => ['pageSize' => 10]
        ]);

        $ujimateri = new ActiveDataProvider([
            'query' => HasilUjiMateri::find()->where(['id_dokumen' => $id]),
            'pagination' => ['pageSize' => 10]
        ]);

        $status = new ActiveDataProvider([
            'query' => DataStatus::find()->where(['id_dokumen' => $id]),
            'pagination' => ['pageSize' => 10]
        ]);

        $log = new ActiveDataProvider([
            'query' => LogPustakawan::find()->where(['dokumen_id' => $id]),
            'pagination' => ['pageSize' => 10]
        ]);

        $eksemplar = new ActiveDataProvider([
            'query' => Eksemplar::find()->where(['id_dokumen' => $id]),
            'pagination' => ['pageSize' => 10]
        ]);

        switch ($model->tipe_dokumen) {
            case (string)DokumenJdih::TYPE_PERATURAN:
            return $this->render('view-peraturan', [
                'model' => $this->findModel($id),
                'teu' => $pengarangProvider,
                'subyek' => $subyek,
                'lampiran' => $lampiran,
                'peraturan' => $peraturan,
                'dokumen' => $dokumen,
                'status' => $status,
                'ujimateri' => $ujimateri,
                'log' => $log,

            ]);
            break;


            case (string)DokumenJdih::TYPE_MONOGRAFI:
            return $this->render('view-monografi', [
                'model' => $this->findModel($id),
                'teu' => $pengarangProvider,
                'subyek' => $subyek,
                'lampiran' => $lampiran,
                'peraturan' => $peraturan,
                'dokumen' => $dokumen,
                'status' => $status,
                'ujimateri' => $ujimateri,
                'log' => $log,
                'eksemplar' => $eksemplar,

            ]);


            break;

            case (string)DokumenJdih::TYPE_ARTIKEL:
            return $this->render('view-artikel', [
                'model' => $this->findModel($id),
                'teu' => $pengarangProvider,
                'subyek' => $subyek,
                'lampiran' => $lampiran,
                'peraturan' => $peraturan,
                'dokumen' => $dokumen,
                'status' => $status,
                'ujimateri' => $ujimateri,
                'log' => $log,

            ]);
            break;

            case (string)DokumenJdih::TYPE_PUTUSAN:
            return $this->render('view-putusan', [
                'model' => $this->findModel($id),
                'teu' => $pengarangProvider,
                'subyek' => $subyek,
                'lampiran' => $lampiran,
                'peraturan' => $peraturan,
                'dokumen' => $dokumen,
                'status' => $status,
                'ujimateri' => $ujimateri,
                'log' => $log,

            ]);
            break;
            default:

            return $this->render('view', [
                'model' => $this->findModel($id),
                'teu' => $pengarangProvider,
                'subyek' => $subyek,
                'lampiran' => $lampiran,
                'peraturan' => $peraturan,
                'dokumen' => $dokumen,
                'status' => $status,
                'ujimateri' => $ujimateri,
                'log' => $log,

            ]);
            break;
        }
    }

    public function actionInactive($id = null)
    {
        if ($id != null) {
            $model = $this->findModel($id);
            $model->is_publish = 0;
            if ($model->save()) {
                Yii::$app->session->setFlash('danger', 'Produk Hukum tidak  diverifikasi');
                return $this->redirect(['index']);
            } else {
                Yii::error('Failed to unpublish document: ' . json_encode($model->getErrors()));
            }
        } else {
            $model = $this->findModel(\Yii::$app->user->identity->id);
            $model->is_publish = 0;
            if ($model->save()) {
                Yii::$app->user->logout();
                Yii::$app->session->setFlash('success', 'Account inactive');
                return $this->goHome();
            }
        }
    }

    /**
     * Change is_publish user to Active
     * @param integer $id
     * @return mixed
     */
    public function actionActive($id, $tahun)
    {
        $model = $this->findModel($id);
        $model->is_publish = 1;
        $model->save();

        switch ($model->tipe_dokumen) {
            case (string)DokumenJdih::TYPE_PERATURAN:
                $view = 'peraturan';
                break;
            case (string)DokumenJdih::TYPE_MONOGRAFI:
                $view = 'monografi';
                break;
            case (string)DokumenJdih::TYPE_ARTIKEL:
                $view = 'artikel';
                break;
            case (string)DokumenJdih::TYPE_PUTUSAN:
                $view = 'putusan';
                break;
        }
        Yii::$app->session->setFlash('success', 'Produk Hukum telah diverifikasi');
        return $this->redirect([$view, 'id' => $model->id, 'tahun' =>$tahun]);
    }

    /**
     * Finds the DokumenJdih model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return DokumenJdih the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionPublishView($id)
    {
        /* @var $user User */
        $model = $this->findModel($id);
        if ($model->is_publish == 0) {
            $model->is_publish = 1;
            if ($model->save(false)) {
                Yii::$app->session->setFlash('success', 'Dokumen Hukum Berhasil dipublish');
                return $this->redirect(['view', 'id' => $id]);
            } else {
                $errors = $model->firstErrors;
                throw new UserException(reset($errors));
            }
        }
    }

    public function actionUnpublishView($id)
    {
        /* @var $user User */
        $model = $this->findModel($id);
        if ($model->is_publish == 1) {
            $model->is_publish = 0;
            if ($model->save(false)) {
                Yii::$app->session->setFlash('success', 'Dokumen Berhasil dinonaktifkan');
                return $this->redirect(['view', 'id' => $id]);
            } else {
                $errors = $model->firstErrors;
                throw new UserException(reset($errors));
            }
        }
    }
    protected function findModel($id)
    {
        if (($model = DokumenJdih::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
