<?php

namespace frontend\controllers;

use common\components\DocumentGroup;
use frontend\models\DocumentType;
use Yii;
use frontend\models\Dokumen;
use frontend\models\DokumenSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\UploadedFile;

/**
 * DokumenController implements the CRUD actions for Dokumen model.
 */
class DokumenController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['create', 'update', 'delete'],
                'rules' => [
                    [
                        'actions' => ['create', 'update', 'delete'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all Dokumen models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new DokumenSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionIndex2($id)
    {
        $searchModel = new DokumenSearch(['bentuk_peraturan' => $id]);
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionPeraturan()
    {
        $searchModel = new DokumenSearch(['tipe_dokumen' => Dokumen::TYPE_PERATURAN]);
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index-peraturan', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionMonografi()
    {
        $searchModel = new DokumenSearch(['tipe_dokumen' => Dokumen::TYPE_MONOGRAFI]);
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index-monografi', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionArtikel()
    {
        $searchModel = new DokumenSearch(['tipe_dokumen' => Dokumen::TYPE_ARTIKEL]);
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index-artikel', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionPutusan()
    {
        $searchModel = new DokumenSearch(['tipe_dokumen' => Dokumen::TYPE_PUTUSAN]);
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index-putusan', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionLegislationFormation($slug = null)
    {
        $group = DocumentGroup::LEGISLATION_FORMATION;
        $type = $slug ? DocumentType::findBySlugInGroup($slug, $group) : null;

        if ($slug !== null && $slug !== '' && $type === null) {
            throw new NotFoundHttpException('Tipe dokumen tidak ditemukan.');
        }

        $searchModel = new DokumenSearch();
        $typeNames = $type
            ? $type->descendantTypeNames()
            : DocumentType::groupTypeNames($group);
        $dataProvider = $searchModel->searchByTypeNames(
            $typeNames,
            Yii::$app->request->queryParams
        );

        return $this->render('index-legislation-formation', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'currentType' => $type,
        ]);
    }
    
    public function actionUu()
    {
        $searchModel = new DokumenSearch(['jenis_peraturan' => 'UNDANG-UNDANG']);
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index-uu', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionBuku()
    {
        $searchModel = new DokumenSearch(['jenis_peraturan' => 'BUKU HUKUM']);
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index-buku', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionMajalah()
    {
        $searchModel = new DokumenSearch(['jenis_peraturan' => 'MAJALAH HUKUM']);
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index-majalah', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionMa()
    {
        $searchModel = new DokumenSearch(['jenis_peraturan' => 'PUTUSAN MAHKAMAH AGUNG']);
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index-putusan', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

     public function actionInggris()
    {
        $searchModel = new DokumenSearch(['singkatan_jenis' => 'PERATURAN BERBAHASA INGGRIS']);
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index-inggris', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
    
     public function actionBerlaku()
    {
        $searchModel = new DokumenSearch(['status' => 'Berlaku', 'tipe_dokumen' => Dokumen::TYPE_PERATURAN]);
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index-berlaku', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

     public function actionTberlaku()
    {
        $searchModel = new DokumenSearch(['status' => 'Tidak Berlaku', 'tipe_dokumen' => Dokumen::TYPE_PERATURAN]);
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index-tberlaku', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
    
    /**
     * Displays a single Dokumen model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id, $slug = null)
    {
        $model = $this->findModel($id);

        if ($slug !== null && $slug !== $model->getUrlSlug()) {
            return $this->redirect(['/dokumen/view', 'id' => $model->id, 'slug' => $model->getUrlSlug()], 301);
        }

        $title = $model->judul;
        $deskripsi = $model->judul;

        $jenisperaturan = DocumentType::find()->where(['singkatan' => $model->singkatan_jenis])->one();
        if (!empty($jenisperaturan)) {
            $keywords = [
                $jenisperaturan->singkatan . ' Nomor ' . $model->nomor_peraturan . ' Tahun ' . $model->tahun_terbit,
                $jenisperaturan->singkatan . ' No. ' . $model->nomor_peraturan . ' Tahun ' . $model->tahun_terbit,
                $jenisperaturan->singkatan . ' ' . $model->nomor_peraturan . ' ' . $model->tahun_terbit,
                $jenisperaturan->singkatan . ' ' . $model->nomor_peraturan . '/' . $model->tahun_terbit,
                $jenisperaturan->singkatan . '-' . $model->nomor_peraturan . '-' . $model->tahun_terbit,
                $jenisperaturan->singkatan . '-no-' . $model->nomor_peraturan . '-tahun-' . $model->tahun_terbit,
            ];
        }

        switch ($model->tipe_dokumen) {
            case Dokumen::TYPE_PERATURAN:
                return $this->render('view-peraturan', ['model' => $model, 'title' => $title, 'deskripsi' => $deskripsi, 'keywords' => $keywords]);
                break;

            case Dokumen::TYPE_MONOGRAFI:
                return $this->render('view-monografi', ['model' => $model, 'title' => $title, 'deskripsi' => $deskripsi]);
                break;

            case Dokumen::TYPE_ARTIKEL:
                return $this->render('view-artikel', ['model' => $model, 'title' => $title, 'deskripsi' => $deskripsi]);
                break;

            case Dokumen::TYPE_PUTUSAN:
                return $this->render('view-putusan', ['model' => $model, 'title' => $title, 'deskripsi' => $deskripsi]);
                break;
        }
    }

    /**
     * Finds the Dokumen model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Dokumen the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Dokumen::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }


    public function actionJenis($id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $rows = \frontend\models\DokumenHukum::find()->where(['parent_id' => $id])->all();
        $result = [];
        foreach ($rows as $branch) {
            $result[] = ['id' => $branch->id, 'name' => $branch->name];
        }
        return $result;
    }

    public function actionDownload($id)
    {
        return \common\components\SafeDownload::sendFile('@common/dokumen', $id);
    }
}
