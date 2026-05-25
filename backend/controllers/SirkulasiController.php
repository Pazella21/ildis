<?php

namespace backend\controllers;

use Yii;
use backend\models\Member;
use backend\models\Member2Search;
use backend\models\Circulation;
use backend\models\CirculationSearch;
use backend\models\CirculationAllSearch;
use backend\models\Eksemplar;
use backend\models\EksemplarSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;
use backend\models\MemberType;

class SirkulasiController extends \yii\web\Controller
{

    public function actionIndex()
    {
        $searchModel = new CirculationAllSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index-all', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionBukuBelumKembali()
    {
        $searchModel = new CirculationAllSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->andWhere(['status' => 1]);
        return $this->render('index-buku-dipinjam', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionPeminjaman()
    {
        $searchModel = new Member2Search();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionPengembalian()
    {
        $searchModel = new CirculationSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index-pengembalian', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }



    public function actionView($id)
    {
        $model = Member::findOne($id);
        $model2 = Circulation::find()->where(['member_id' => $id, 'status' => 1])->all();

        $searchModel = new EksemplarSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->andWhere(['status_eksemplar' => 'Tersedia']);

        return $this->render('view', [
            'model' => $model,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'model2' => $model2,
            'id' => $id,

        ]);
    }

    public function actionPinjam($eksemplarId, $memberId)
    {

        $eksemplar = Eksemplar::findOne($eksemplarId);
        $model = Member::findOne($memberId);
        $member = MemberType::findOne($model->member_type_id);
        $day = '+' . $member->loan_periode . ' day';
        $model3 = Circulation::find()->where(['member_id' => $memberId, 'status' => 1])->count();

        if ($model3 >= $member->loan_limit) {
            Yii::$app->session->setFlash('danger', 'Batas peminjaman buku hanya sebanyak 3 buku');
            return $this->redirect(['view', 'id' => $memberId]);
        }

        $pinjam = new Circulation();
        $pinjam->member_id = $memberId;
        $pinjam->member = $model->username;
        $pinjam->item_code = $eksemplar->kode_eksemplar;
        $pinjam->item_id = $eksemplar->id;
        $pinjam->title = $eksemplar->monografi->judul;
        $pinjam->status = 1;
        $pinjam->status_peminjaman = 'Dipinjam';
        $pinjam->tanggal_pinjam = date('Y-m-d');
        $pinjam->tanggal_kembali = date('Y-m-d', strtotime($day));
        $pinjam->document_id = $eksemplar->id_dokumen;

        if (!$pinjam->save()) {
            Yii::error('Failed to save circulation: ' . json_encode($pinjam->errors));
        }

        $eksemplar->status_eksemplar = 'Dipinjam';
        $eksemplar->save(false); // TODO: review - skips validation

        Yii::$app->session->setFlash('success', 'Buku berhasil dipinjam');
        return $this->redirect(['view', 'id' => $memberId]);
    }

    public function actionHapusSirkulasi($id)
    {

        $model = Circulation::findOne($id);
        $model->delete();
        $eksemplar = Eksemplar::findOne($model->item_id);
        $eksemplar->status_eksemplar = 'Tersedia';
        $eksemplar->save(false); // TODO: review - skips validation
        Yii::$app->session->setFlash('danger', 'Data Sirkulasi berhasil dihapus');
        return $this->redirect(['view', 'id' => $model->member_id]);
    }

    public function actionKembali($circulationId, $memberId)
    {
        $model = Circulation::findOne($circulationId);

        $member = Member::findOne($memberId);
        $member_type = MemberType::findOne($member->member_type_id);

        $model->status = 0;
        $model->status_peminjaman = 'Telah Kembali';
        $timeStart = strtotime($model->tanggal_pinjam);
        $timeEnd = strtotime(date('Y-m-d'));

        $terlambat = (int)(($timeEnd - $timeStart) / 86400);

        if ($terlambat > 0) {
            $model->denda = $terlambat * $member_type->fine_each_day;
        } else {
            $model->denda = 0;
        }

        $model->save(false); // TODO: review - skips validation

        $eksemplar = Eksemplar::findOne($model->item_id);
        $eksemplar->status_eksemplar = 'Tersedia';
        $eksemplar->save(false); // TODO: review - skips validation

        Yii::$app->session->setFlash('success', 'Pengembalian Buku berhasil');
        return $this->redirect('pengembalian');
    }

    public function actionPerpanjang($id)
    {
        $model = Circulation::findOne($id);
        $member = Member::findOne($model->member_id);
        $memberType = MemberType::findOne($member->member_type_id);

        $model->status = 0;
        $model->status_peminjaman = 'Telah Kembali';
        $timeStart = strtotime($model->tanggal_pinjam);
        $timeEnd = strtotime(date('Y-m-d'));

        $numBulan = (int)(($timeEnd - $timeStart) / 86400);

        if ($numBulan > 3) {
            $numBulan = $numBulan - 3;
            $model->denda = $numBulan * $memberType->fine_each_day;
        } else {
            $model->denda = 0;
        }

        $model->save(false); // TODO: review - skips validation

        $pinjam = new Circulation();
        $pinjam->member_id = $model->member_id;
        $pinjam->member = $model->member;
        $pinjam->item_code = $model->item_code;
        $pinjam->item_id = $model->item_id;
        $pinjam->title = $model->title;
        $pinjam->status = 1;
        $pinjam->status_peminjaman = 'Dipinjam';
        $pinjam->tanggal_pinjam = date('Y-m-d');
        $pinjam->tanggal_kembali = date('Y-m-d', strtotime('+' . $memberType->loan_periode . ' day'));
        $pinjam->document_id = $model->document_id;

        if (!$pinjam->save()) {
            Yii::error('Failed to save circulation extension: ' . json_encode($pinjam->errors));
        }

        Yii::$app->session->setFlash('success', 'Perpanjangan Peminjaman Buku berhasil');
        return $this->redirect('pengembalian');
    }
}
