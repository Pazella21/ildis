<?php

namespace backend\controllers;

use Yii;
use backend\models\Rancangan;
use backend\models\MasukanMasyarakat;
use backend\models\search\PenyusunanSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;
use backend\web\components\FileHelper;
use common\components\SafeDownload;
use yii\data\ActiveDataProvider;
use backend\models\DokumenJdih;
/**
 * PenyusunanController implements the CRUD actions for Rancangan model.
 */
class PenyusunanController extends Controller
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
     * Lists all Rancangan models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new PenyusunanSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Rancangan model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $partisipasi = new ActiveDataProvider([
            'query' => MasukanMasyarakat::find()->where(['rancangan_id'=>$id]),
            'pagination' => [
                'pageSize' => 10,
            ],
        ]);  

        return $this->render('view', [
            'model' => $this->findModel($id),
            'partisipasi'=>$partisipasi,
        ]);
    }

    public function actionCreate()
    {
        $model = new Rancangan();

        if ($model->load(Yii::$app->request->post()))
        {

            $file_rancangan = UploadedFile::getInstance($model, 'file_rancangan');
            if(!empty($file_rancangan)){
                $model->file_rancangan = FileHelper::sanitizeFilename($file_rancangan->name);
                $path = Yii::getAlias('@common'). '/uploads/rancangan/' . $model->file_rancangan;
                $file_rancangan->saveAs($path);
            } 

            $file_naskah_akademik = UploadedFile::getInstance($model, 'file_naskah_akademik');
            if(!empty($file_naskah_akademik)){
                $model->file_naskah_akademik = FileHelper::sanitizeFilename($file_naskah_akademik->name);
                $path = Yii::getAlias('@common'). '/uploads/rancangan/' . $model->file_naskah_akademik;
                $file_naskah_akademik->saveAs($path);
            }       

            if ($model->save()) 
            {
                Yii::$app->session->setFlash('success', 'Data Rancangan berhasil ditambahkan');
                return $this->redirect(['view', 'id' => $model->id]);
            } else 
            {
                Yii::$app->session->setFlash('error', 'Data Rancangan Gagal ditambahkan, periksa kembali ');
                return $this->render('create', ['model' => $model]);
            }

        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }


    /**
     * Updates an existing Rancangan model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $old_file_rancangan =  $model->file_rancangan;
        $old_file_naskah_akademik =  $model->file_naskah_akademik;

        if ($model->load(Yii::$app->request->post())){

           $file_rancangan = UploadedFile::getInstance($model, 'file_rancangan');
            if(!empty($file_rancangan)){
                $model->file_rancangan = FileHelper::sanitizeFilename($file_rancangan->name);
                $path = Yii::getAlias('@common'). '/uploads/rancangan/' . $model->file_rancangan;
                $file_rancangan->saveAs($path);
            }else{
                $model->file_rancangan= $old_file_rancangan;

            } 

           $file_naskah_akademik = UploadedFile::getInstance($model, 'file_naskah_akademik');
            if(!empty($file_naskah_akademik)){
                $model->file_naskah_akademik = FileHelper::sanitizeFilename($file_naskah_akademik->name);
                $path = Yii::getAlias('@common'). '/uploads/rancangan/' . $model->file_naskah_akademik;
                $file_naskah_akademik->saveAs($path);
            } else{
                $model->file_naskah_akademik = $old_file_naskah_akademik;
            } 

        if($model->save()) {
            Yii::$app->session->setFlash('success', 'Data Rancangan berhasil diubah');
            return $this->redirect(['view', 'id' => $model->id]);
        } else 
            {
                Yii::$app->session->setFlash('error', 'Data Rancangan Gagal ditambahkan, periksa kembali ');
                return $this->render('create', ['model' => $model]);
            }

        }else{ 
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing Rancangan model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        try
        {
            $this->findModel($id)->delete();
            Yii::$app->session->setFlash('danger', 'Data Rancangan berhasil dihapus');
            return $this->redirect(['index']);
        }
        catch(\yii\db\IntegrityException  $e)
        {
            Yii::$app->session->setFlash('error', "Data Rancangan Tidak Dapat Dihapus Karena Dipakai Modul Lain");
            return $this->redirect(['index']);
        } 
        
        
    }

    public function actionDownload($id)
    {
        return SafeDownload::sendFile('@common/uploads/rancangan', $id);
    } 


    /**
     * Finds the Rancangan model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Rancangan the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Rancangan::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionParent($id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $institutionType = ($id == DokumenJdih::KEMENTERIAN_ID) ? 'Kementerian' : 'Lembaga';
        $institutions = \backend\models\peraturan\Institutions::find()->where(['jenis' => $institutionType])->all();
        $results = [];
        foreach ($institutions as $institution) {
            $results[] = ['id' => $institution->id, 'name' => $institution->nama];
        }
        return $results;
    }
}
