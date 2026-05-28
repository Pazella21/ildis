# Dokumen Pembentukan PUU Management Page — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create a dedicated backend CRUD page for managing Dokumen Pembentukan PUU documents, scoped to `legislation_formation` document types, with full label rebranding.

**Architecture:** New `DokumenPembentukanPuuController` mirrors `MonografiController` but permanently scopes queries to `legislation_formation` group types. New search model scopes the base query. New views directory clones from Monografi with rebranded labels and scoped dropdowns. Sub-entity views are shared (rendered from `monografi/` paths). RBAC migration adds route permissions.

**Tech Stack:** Yii2 (PHP 7.4+), Kartik GridView, AdminLTE, existing models (Monografi, DataPengarang, DataSubyek, DataLampiran, Eksemplar, LogPustakawan), common\models\DocumentType, common\components\DocumentGroup

---

### Task 1: Create `DokumenPembentukanPuuSearch` model

**Files:**
- Create: `backend/models/DokumenPembentukanPuuSearch.php`

This is the search model. It scopes all queries to `legislation_formation` document type names.

- [ ] **Step 1: Create the search model file**

```php
<?php

namespace backend\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\Monografi;
use common\components\DocumentGroup;
use common\models\DocumentType;

class DokumenPembentukanPuuSearch extends Monografi
{
    public $documentTypeId;

    private static function groupTypeNames()
    {
        static $names;
        if ($names === null) {
            $names = DocumentType::groupTypeNames(DocumentGroup::LEGISLATION_FORMATION);
        }
        return $names;
    }

    public function rules()
    {
        return [
            [['id', 'tipe_dokumen', 'daerah', 'hit_see', 'hit_download', 'is_publish', 'documentTypeId'], 'integer'],
            [['judul', 'teu', 'nomor_peraturan', 'sumber_perolehan', 'nomor_panggil', 'bentuk_peraturan', 'singkatan_jenis', 'cetakan', 'tempat_terbit', 'penerbit', 'tanggal_penetapan', 'deskripsi_fisik', 'sumber', 'isbn', 'bahasa', 'bidang_hukum', 'nomor_induk_buku', 'jenis_peraturan', 'singkatan_bentuk', 'tipe_koleksi_nomor_eksemplar', 'pola_nomor_eksemplar', 'jumlah_eksemplar', 'kala_terbit', 'tahun_terbit', 'tanggal_dibacakan', 'pernyataan_tanggung_jawab', 'edisi', 'gmd', 'judul_seri', 'klasifikasi', 'info_detil_spesifik', 'abstrak', 'gambar_sampul', 'label', 'sembunyikan_di_opac', 'promosikan_ke_beranda', 'status_terakhir', 'status', 'integrasi', '_created_by', '_updated_by', 'created_at', 'updated_at', 'inisiatif', 'pemrakarsa', 'tanggal_pengundangan', 'penandatanganan', 'lembaga_peradilan', 'pemohon', 'termohon', 'jenis_perkara', 'sub_klasifikasi', 'amar_status', 'berkekuatan_hukum_tetap', 'urusan_pemerintahan', 'catatan_status_peraturan'], 'safe'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $groupNames = self::groupTypeNames();

        $query = Monografi::find()
            ->where(['tipe_dokumen' => 2])
            ->andWhere(['jenis_peraturan' => $groupNames]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['is_publish' => SORT_ASC, 'tahun_terbit' => SORT_DESC]],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'tipe_dokumen' => $this->tipe_dokumen,
            'tanggal_penetapan' => $this->tanggal_penetapan,
            'tanggal_dibacakan' => $this->tanggal_dibacakan,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'tanggal_pengundangan' => $this->tanggal_pengundangan,
            'daerah' => $this->daerah,
            'hit_see' => $this->hit_see,
            'is_publish' => $this->is_publish,
            'hit_download' => $this->hit_download,
        ]);

        $query->andFilterWhere(['like', 'judul', $this->judul])
            ->andFilterWhere(['like', 'teu', $this->teu])
            ->andFilterWhere(['like', 'nomor_peraturan', $this->nomor_peraturan])
            ->andFilterWhere(['like', 'nomor_panggil', $this->nomor_panggil])
            ->andFilterWhere(['like', 'bentuk_peraturan', $this->bentuk_peraturan])
            ->andFilterWhere(['like', 'singkatan_jenis', $this->singkatan_jenis])
            ->andFilterWhere(['like', 'cetakan', $this->cetakan])
            ->andFilterWhere(['like', 'tempat_terbit', $this->tempat_terbit])
            ->andFilterWhere(['like', 'penerbit', $this->penerbit])
            ->andFilterWhere(['like', 'deskripsi_fisik', $this->deskripsi_fisik])
            ->andFilterWhere(['like', 'sumber', $this->sumber])
            ->andFilterWhere(['like', 'isbn', $this->isbn])
            ->andFilterWhere(['like', 'bahasa', $this->bahasa])
            ->andFilterWhere(['like', 'bidang_hukum', $this->bidang_hukum])
            ->andFilterWhere(['like', 'nomor_induk_buku', $this->nomor_induk_buku])
            ->andFilterWhere(['like', 'jenis_peraturan', $this->jenis_peraturan])
            ->andFilterWhere(['like', 'singkatan_bentuk', $this->singkatan_bentuk])
            ->andFilterWhere(['like', 'tipe_koleksi_nomor_eksemplar', $this->tipe_koleksi_nomor_eksemplar])
            ->andFilterWhere(['like', 'pola_nomor_eksemplar', $this->pola_nomor_eksemplar])
            ->andFilterWhere(['like', 'jumlah_eksemplar', $this->jumlah_eksemplar])
            ->andFilterWhere(['like', 'kala_terbit', $this->kala_terbit])
            ->andFilterWhere(['like', 'tahun_terbit', $this->tahun_terbit])
            ->andFilterWhere(['like', 'pernyataan_tanggung_jawab', $this->pernyataan_tanggung_jawab])
            ->andFilterWhere(['like', 'edisi', $this->edisi])
            ->andFilterWhere(['like', 'gmd', $this->gmd])
            ->andFilterWhere(['like', 'sumber_perolehan', $this->sumber_perolehan])
            ->andFilterWhere(['like', 'judul_seri', $this->judul_seri])
            ->andFilterWhere(['like', 'klasifikasi', $this->klasifikasi])
            ->andFilterWhere(['like', 'info_detil_spesifik', $this->info_detil_spesifik])
            ->andFilterWhere(['like', 'abstrak', $this->abstrak])
            ->andFilterWhere(['like', 'gambar_sampul', $this->gambar_sampul])
            ->andFilterWhere(['like', 'label', $this->label])
            ->andFilterWhere(['like', 'sembunyikan_di_opac', $this->sembunyikan_di_opac])
            ->andFilterWhere(['like', 'promosikan_ke_beranda', $this->promosikan_ke_beranda])
            ->andFilterWhere(['like', 'status_terakhir', $this->status_terakhir])
            ->andFilterWhere(['like', 'status', $this->status])
            ->andFilterWhere(['like', 'integrasi', $this->integrasi])
            ->andFilterWhere(['like', '_created_by', $this->_created_by])
            ->andFilterWhere(['like', '_updated_by', $this->_updated_by])
            ->andFilterWhere(['like', 'inisiatif', $this->inisiatif])
            ->andFilterWhere(['like', 'pemrakarsa', $this->pemrakarsa])
            ->andFilterWhere(['like', 'penandatanganan', $this->penandatanganan])
            ->andFilterWhere(['like', 'lembaga_peradilan', $this->lembaga_peradilan])
            ->andFilterWhere(['like', 'pemohon', $this->pemohon])
            ->andFilterWhere(['like', 'termohon', $this->termohon])
            ->andFilterWhere(['like', 'jenis_perkara', $this->jenis_perkara])
            ->andFilterWhere(['like', 'sub_klasifikasi', $this->sub_klasifikasi])
            ->andFilterWhere(['like', 'amar_status', $this->amar_status])
            ->andFilterWhere(['like', 'berkekuatan_hukum_tetap', $this->berkekuatan_hukum_tetap])
            ->andFilterWhere(['like', 'urusan_pemerintahan', $this->urusan_pemerintahan])
            ->andFilterWhere(['like', 'catatan_status_peraturan', $this->catatan_status_peraturan]);

        if ($this->documentTypeId) {
            $type = DocumentType::findOne($this->documentTypeId);
            if ($type) {
                $query->andWhere(['jenis_peraturan' => $type->name]);
            } else {
                $query->andWhere('0=1');
            }
        }

        return $dataProvider;
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add backend/models/DokumenPembentukanPuuSearch.php
git commit -m "feat(puu): add DokumenPembentukanPuuSearch model scoped to legislation_formation types"
```

---

### Task 2: Create `DokumenPembentukanPuuController`

**Files:**
- Create: `backend/controllers/DokumenPembentukanPuuController.php`

The controller mirrors `MonografiController` with:
- Permanent scope filter in `findModel()` rejecting non-group documents
- Scoped `jenis_peraturan` resolution in `actionUpdate`
- Log messages say "Dokumen Pembentukan PUU" instead of "Monografi"
- Sub-CRUD actions for Pengarang, Subyek, Lampiran, Eksemplar only
- No Peraturan Terkait, Dokumen Terkait, Uji Materi, Status, Log tabs

- [ ] **Step 1: Create the controller file**

```php
<?php

namespace backend\controllers;

use Yii;
use backend\models\Monografi;
use backend\models\DokumenPembentukanPuuSearch;
use backend\models\Pengarang;
use backend\models\LogPustakawan;
use backend\models\JenisPeraturan;
use backend\models\DataPengarang;
use backend\models\DataSubyek;
use backend\models\DataLampiran;
use backend\models\Eksemplar;
use common\components\DateHelper;
use common\components\DocumentGroup;
use common\models\DocumentType;
use backend\web\components\FileHelper;
use common\components\SafeDownload;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;
use yii\helpers\Json;
use backend\models\EksemplarSearch;

class DokumenPembentukanPuuController extends Controller
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
        $searchModel = new DokumenPembentukanPuuSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView($id)
    {
        $pengarangProvider = new ActiveDataProvider([
            'query' => DataPengarang::find()->where(['id_dokumen' => $id]),
            'pagination' => ['pageSize' => 10],
        ]);

        $subyek = new ActiveDataProvider([
            'query' => DataSubyek::find()->where(['id_dokumen' => $id]),
            'pagination' => ['pageSize' => 10],
        ]);

        $lampiran = new ActiveDataProvider([
            'query' => DataLampiran::find()->where(['id_dokumen' => $id]),
            'pagination' => ['pageSize' => 10],
        ]);

        $eksemplar = new ActiveDataProvider([
            'query' => Eksemplar::find()->where(['id_dokumen' => $id]),
            'pagination' => ['pageSize' => 10],
        ]);

        return $this->render('view', [
            'model' => $this->findModel($id),
            'teu' => $pengarangProvider,
            'subyek' => $subyek,
            'lampiran' => $lampiran,
            'eksemplar' => $eksemplar,
        ]);
    }

    public function actionCreate()
    {
        $model = new Monografi();
        $log = new LogPustakawan();

        if ($model->load(Yii::$app->request->post())) {
            $abstrak = UploadedFile::getInstance($model, 'abstrak');
            if (!empty($abstrak)) {
                $model->abstrak = FileHelper::sanitizeFilename($abstrak->name);
                $path = Yii::getAlias('@common') . '/dokumen/' . $model->abstrak;
                $abstrak->saveAs($path);
            }

            $cover = UploadedFile::getInstance($model, 'gambar_sampul');
            if (!empty($cover)) {
                $model->gambar_sampul = FileHelper::sanitizeFilename($cover->name);
                $path = Yii::getAlias('@common') . '/dokumen/' . $model->gambar_sampul;
                $cover->saveAs($path);
            }
            $model->save();

            $log->dokumen_id = $model->id;
            $log->controller = 'DokumenPembentukanPuu';
            $log->aksi = 'Tambah Dokumen Pembentukan PUU';
            $log->keterangan = 'User ' . \Yii::$app->user->identity->username . ' melakukan tambah data Dokumen Pembentukan PUU pada ' . DateHelper::formatIndonesian(date('Y-m-d H:i:s'));
            $log->save();

            Yii::$app->session->setFlash('success', 'Data Dokumen Pembentukan PUU berhasil ditambahkan');
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $old_abstrak = $model->abstrak;
        $old_sampul = $model->gambar_sampul;

        if ($model->load(Yii::$app->request->post())) {

            $abstrak = UploadedFile::getInstance($model, 'abstrak');
            if (!empty($abstrak)) {
                $model->abstrak = FileHelper::sanitizeFilename($abstrak->name);
                $path = Yii::getAlias('@common') . '/dokumen/' . $model->abstrak;
                $abstrak->saveAs($path);
            } else {
                $model->abstrak = $old_abstrak;
            }

            $cover = UploadedFile::getInstance($model, 'gambar_sampul');
            if (!empty($cover)) {
                $model->gambar_sampul = FileHelper::sanitizeFilename($cover->name);
                $path = Yii::getAlias('@common') . '/dokumen/' . $model->gambar_sampul;
                $cover->saveAs($path);
            } else {
                $model->gambar_sampul = $old_sampul;
            }

            $jenisperaturan = JenisPeraturan::findOne($model->jenis_peraturan);
            if (!empty($jenisperaturan)) {
                $model->jenis_peraturan = $jenisperaturan->name;
                $model->bentuk_peraturan = $jenisperaturan->name;
            }

            if ($model->save()) {
                $log = new LogPustakawan();
                $log->dokumen_id = $id;
                $log->controller = 'DokumenPembentukanPuu';
                $log->aksi = 'Ubah Dokumen Pembentukan PUU';
                $log->keterangan = 'User ' . \Yii::$app->user->identity->username . ' melakukan ubah data Dokumen Pembentukan PUU pada ' . DateHelper::formatIndonesian(date('Y-m-d H:i:s'));
                $log->save();
                Yii::$app->session->setFlash('success', 'Data Dokumen Pembentukan PUU berhasil diubah');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    public function actionDelete($id)
    {
        try {
            $this->findModel($id)->delete();

            $log = new LogPustakawan();
            $log->dokumen_id = $id;
            $log->controller = 'DokumenPembentukanPuu';
            $log->aksi = 'Hapus Dokumen Pembentukan PUU';
            $log->keterangan = 'User ' . \Yii::$app->user->identity->username . ' melakukan hapus data Dokumen Pembentukan PUU pada ' . DateHelper::formatIndonesian(date('Y-m-d H:i:s'));
            $log->save();

            Yii::$app->session->setFlash('danger', 'Data Dokumen Pembentukan PUU berhasil dihapus');
            return $this->redirect(['index']);
        } catch (\yii\db\IntegrityException $e) {
            Yii::$app->session->setFlash('error', 'Data Dokumen Pembentukan PUU Tidak Dapat Dihapus Karena Dipakai Modul Lain');
            return $this->redirect(['index']);
        }
    }

    public function actionInactive($id)
    {
        $model = $this->findModel($id);
        $model->is_publish = 0;
        if ($model->save(false)) {
            Yii::$app->session->setFlash('danger', 'Verifikasi Dokumen Pembentukan PUU dibatalkan');
        } else {
            Yii::error(
                '[dokumen-pembentukan-puu/inactive] Failed to save model: ' . json_encode($model->getErrors()),
                __METHOD__
            );
            Yii::$app->session->setFlash('error', 'Gagal membatalkan verifikasi Dokumen Pembentukan PUU.');
        }
        return $this->redirect(['index']);
    }

    /*---------- BEGIN TEU -----------------*/

    public function actionTambahPengarang($id)
    {
        $this->findModel($id);
        $model = new DataPengarang();
        if ($model->load(Yii::$app->request->post())) {
            $model->id_dokumen = $id;
            if ($model->save()) {
                $log = new LogPustakawan();
                $log->dokumen_id = $id;
                $log->controller = 'DokumenPembentukanPuu';
                $log->aksi = 'Tambah Pengarang';
                $log->keterangan = 'User ' . \Yii::$app->user->identity->username . ' melakukan tambah data pengarang pada ' . DateHelper::formatIndonesian(date('Y-m-d H:i:s'));
                $log->save();

                Yii::$app->session->setFlash('success', 'Data Pengarang berhasil ditambah');
                return $this->redirect(['view', 'id' => $id]);
            }
        } else {
            return $this->render('../monografi/teu/create-teu', [
                'model' => $model,
                'id' => $id,
            ]);
        }
    }

    public function actionTambahPengarang2($id)
    {
        $this->findModel($id);
        $model = new Pengarang();
        $pengarangProvider = new DataPengarang();
        if ($model->load(Yii::$app->request->post()) && $pengarangProvider->load(Yii::$app->request->post())) {
            $model->status = 'Publish';
            if ($model->save()) {
                $pengarangProvider->id_dokumen = $id;
                $pengarangProvider->nama_pengarang = $model->id;
                $pengarangProvider->save();

                $log = new LogPustakawan();
                $log->dokumen_id = $id;
                $log->controller = 'DokumenPembentukanPuu';
                $log->aksi = 'Tambah Pengarang';
                $log->keterangan = 'User ' . \Yii::$app->user->identity->username . ' melakukan tambah data pengarang pada ' . DateHelper::formatIndonesian(date('Y-m-d H:i:s'));
                $log->save();

                Yii::$app->session->setFlash('success', 'Data Pengarang berhasil ditambah');
                return $this->redirect(['view', 'id' => $id]);
            }
        } else {
            return $this->render('../monografi/teu/create-teu2', [
                'model' => $model,
                'teu' => $pengarangProvider,
                'id' => $id,
            ]);
        }
    }

    public function actionUbahPengarang($id)
    {
        $model = DataPengarang::findOne($id);
        if ($model->load(Yii::$app->request->post())) {
            if ($model->save()) {
                $log = new LogPustakawan();
                $log->dokumen_id = $model->id_dokumen;
                $log->controller = 'DokumenPembentukanPuu';
                $log->aksi = 'Ubah Pengarang';
                $log->keterangan = 'User ' . \Yii::$app->user->identity->username . ' melakukan ubah data pengarang pada ' . DateHelper::formatIndonesian(date('Y-m-d H:i:s'));
                $log->save();
                Yii::$app->session->setFlash('warning', 'Data Pengarang berhasil diubah');
                return $this->redirect(['view', 'id' => $model->id_dokumen]);
            }
        } else {
            return $this->render('../monografi/teu/update-teu', [
                'model' => $model,
            ]);
        }
    }

    public function actionHapusPengarang($id)
    {
        $model = DataPengarang::findOne($id);
        $model->delete();
        $log = new LogPustakawan();
        $log->dokumen_id = $model->id_dokumen;
        $log->controller = 'DokumenPembentukanPuu';
        $log->aksi = 'Hapus Pengarang';
        $log->keterangan = 'User ' . \Yii::$app->user->identity->username . ' melakukan hapus data pengarang pada ' . DateHelper::formatIndonesian(date('Y-m-d H:i:s'));
        $log->save();
        Yii::$app->session->setFlash('danger', 'Data Pengarang berhasil dihapus');
        return $this->redirect(['view', 'id' => $model->id_dokumen]);
    }

    /*---------- END TEU -----------------*/

    /*---------- BEGIN SUBYEK -----------------*/

    public function actionTambahSubyek($id)
    {
        $this->findModel($id);
        $model = new DataSubyek();
        if ($model->load(Yii::$app->request->post())) {
            $model->id_dokumen = $id;
            if ($model->save()) {
                $log = new LogPustakawan();
                $log->dokumen_id = $id;
                $log->controller = 'DokumenPembentukanPuu';
                $log->aksi = 'Tambah Subjek';
                $log->keterangan = 'User ' . \Yii::$app->user->identity->username . ' melakukan tambah data subjek pada ' . DateHelper::formatIndonesian(date('Y-m-d H:i:s'));
                $log->save();

                Yii::$app->session->setFlash('success', 'Data Subyek berhasil ditambah');
                return $this->redirect(['view', 'id' => $id]);
            }
        } else {
            return $this->render('../monografi/subyek/create-subyek', [
                'model' => $model,
            ]);
        }
    }

    public function actionUbahSubyek($id)
    {
        $model = DataSubyek::findOne($id);
        if ($model->load(Yii::$app->request->post())) {
            if ($model->save()) {
                $log = new LogPustakawan();
                $log->dokumen_id = $model->id_dokumen;
                $log->controller = 'DokumenPembentukanPuu';
                $log->aksi = 'Ubah Subjek';
                $log->keterangan = 'User ' . \Yii::$app->user->identity->username . ' melakukan ubah data subjek pada ' . DateHelper::formatIndonesian(date('Y-m-d H:i:s'));
                $log->save();
                Yii::$app->session->setFlash('warning', 'Data Subyek berhasil diubah');
                return $this->redirect(['view', 'id' => $model->id_dokumen]);
            }
        } else {
            return $this->render('../monografi/subyek/update-subyek', [
                'model' => $model,
            ]);
        }
    }

    public function actionHapusSubyek($id)
    {
        $model = DataSubyek::findOne($id);
        $model->delete();
        $log = new LogPustakawan();
        $log->dokumen_id = $model->id_dokumen;
        $log->controller = 'DokumenPembentukanPuu';
        $log->aksi = 'Hapus Subjek';
        $log->keterangan = 'User ' . \Yii::$app->user->identity->username . ' melakukan hapus data subjek pada ' . DateHelper::formatIndonesian(date('Y-m-d H:i:s'));
        $log->save();
        Yii::$app->session->setFlash('danger', 'Data Subyek berhasil dihapus');
        return $this->redirect(['view', 'id' => $model->id_dokumen]);
    }

    /*---------- END SUBYEK -----------------*/

    /*---------- BEGIN LAMPIRAN -----------------*/

    public function actionTambahLampiran($id)
    {
        $this->findModel($id);
        $model = new DataLampiran();
        if ($model->load(Yii::$app->request->post())) {
            $model->id_dokumen = $id;
            $dokumen_lampiran = UploadedFile::getInstance($model, 'dokumen_lampiran');

            if (!empty($dokumen_lampiran)) {
                $model->dokumen_lampiran = FileHelper::sanitizeFilename($dokumen_lampiran->name);
                $path = Yii::getAlias('@common') . '/dokumen/' . $model->dokumen_lampiran;
                $dokumen_lampiran->saveAs($path);
            }
            $model->url_lampiran = Yii::getAlias('@imageurl') . '/common/dokumen/' . $model->dokumen_lampiran;

            if ($model->save()) {
                $log = new LogPustakawan();
                $log->dokumen_id = $model->id;
                $log->controller = 'DokumenPembentukanPuu';
                $log->aksi = 'Tambah Lampiran';
                $log->keterangan = 'User ' . \Yii::$app->user->identity->username . ' melakukan tambah data lampiran pada ' . DateHelper::formatIndonesian(date('Y-m-d H:i:s'));
                $log->save();
                Yii::$app->session->setFlash('success', 'Data Lampiran berhasil ditambah');
                return $this->redirect(['view', 'id' => $id]);
            }
        } else {
            return $this->render('../monografi/lampiran/create-lampiran', [
                'model' => $model,
            ]);
        }
    }

    public function actionUbahLampiran($id)
    {
        $model = DataLampiran::findOne($id);
        $old = $model->dokumen_lampiran;
        if ($model->load(Yii::$app->request->post())) {
            $dokumen_lampiran = UploadedFile::getInstance($model, 'dokumen_lampiran');

            if (!empty($dokumen_lampiran)) {
                $model->dokumen_lampiran = FileHelper::sanitizeFilename($dokumen_lampiran->name);
                $path = Yii::getAlias('@common') . '/dokumen/' . $model->dokumen_lampiran;
                $dokumen_lampiran->saveAs($path);
                $model->url_lampiran = Yii::getAlias('@imageurl') . '/common/dokumen/' . $model->dokumen_lampiran;
            } else {
                $model->dokumen_lampiran = $old;
            }
            if ($model->save()) {
                $log = new LogPustakawan();
                $log->dokumen_id = $model->id_dokumen;
                $log->controller = 'DokumenPembentukanPuu';
                $log->aksi = 'Ubah Lampiran';
                $log->keterangan = 'User ' . \Yii::$app->user->identity->username . ' melakukan ubah data lampiran pada ' . DateHelper::formatIndonesian(date('Y-m-d H:i:s'));
                $log->save();
                Yii::$app->session->setFlash('warning', 'Data Lampiran berhasil diubah');
                return $this->redirect(['view', 'id' => $model->id_dokumen]);
            }
        } else {
            return $this->render('../monografi/lampiran/update-lampiran', [
                'model' => $model,
            ]);
        }
    }

    public function actionHapusLampiran($id)
    {
        $model = DataLampiran::findOne($id);
        $model->delete();
        $log = new LogPustakawan();
        $log->dokumen_id = $model->id_dokumen;
        $log->controller = 'DokumenPembentukanPuu';
        $log->aksi = 'Hapus Lampiran';
        $log->keterangan = 'User ' . \Yii::$app->user->identity->username . ' melakukan hapus data lampiran pada ' . DateHelper::formatIndonesian(date('Y-m-d H:i:s'));
        $log->save();
        Yii::$app->session->setFlash('danger', 'Data Lampiran berhasil dihapus');
        return $this->redirect(['view', 'id' => $model->id_dokumen]);
    }

    /*---------- END LAMPIRAN -----------------*/

    /*---------- BEGIN EKSEMPLAR -----------------*/

    public function actionTambahEksemplar($id)
    {
        $monografi = $this->findModel($id);
        $model = new Eksemplar();

        if ($model->load(Yii::$app->request->post())) {
            $model->id_dokumen = $id;
            $model->no_panggil = $monografi->nomor_panggil;

            $barcode = UploadedFile::getInstance($model, 'barcode_image');
            if (!empty($barcode)) {
                $model->barcode_image = FileHelper::sanitizeFilename($barcode->name);
                $path = Yii::getAlias('@common') . '/dokumen/' . $model->barcode_image;
                $barcode->saveAs($path);
            }
            if ($model->save()) {
                $log = new LogPustakawan();
                $log->dokumen_id = $id;
                $log->controller = 'DokumenPembentukanPuu';
                $log->aksi = 'Tambah Eksemplar';
                $log->keterangan = 'User ' . \Yii::$app->user->identity->username . ' melakukan tambah data kode eksemplar pada ' . DateHelper::formatIndonesian(date('Y-m-d H:i:s'));
                $log->save();

                Yii::$app->session->setFlash('success', 'Data Eksemplar berhasil ditambah');
                return $this->redirect(['view', 'id' => $id]);
            } else {
                Yii::$app->session->setFlash('error', 'Data Kode Eksemplar sudah ada');
                return $this->render('../monografi/eksemplar/create-eksemplar', [
                    'model' => $model,
                    'id' => $id,
                ]);
            }
        } else {
            return $this->render('../monografi/eksemplar/create-eksemplar', [
                'model' => $model,
                'id' => $id,
            ]);
        }
    }

    public function actionUbahEksemplar($id)
    {
        $model = Eksemplar::findOne($id);
        $old_barcode = $model->barcode_image;
        if ($model->load(Yii::$app->request->post())) {
            $barcode = UploadedFile::getInstance($model, 'barcode_image');
            if (!empty($barcode)) {
                $model->barcode_image = FileHelper::sanitizeFilename($barcode->name);
                $path = Yii::getAlias('@common') . '/dokumen/' . $model->barcode_image;
                $barcode->saveAs($path);
            } else {
                $model->barcode_image = $old_barcode;
            }

            if ($model->save()) {
                $log = new LogPustakawan();
                $log->dokumen_id = $model->id_dokumen;
                $log->controller = 'DokumenPembentukanPuu';
                $log->aksi = 'Ubah Eksemplar';
                $log->keterangan = 'User ' . \Yii::$app->user->identity->username . ' melakukan ubah data eksemplar pada ' . DateHelper::formatIndonesian(date('Y-m-d H:i:s'));
                $log->save();
                Yii::$app->session->setFlash('warning', 'Data Eksemplar berhasil diubah');
                return $this->redirect(['view', 'id' => $model->id_dokumen]);
            }
        } else {
            return $this->render('../monografi/eksemplar/update-eksemplar', [
                'model' => $model,
            ]);
        }
    }

    public function actionHapusEksemplar($id)
    {
        $model = Eksemplar::findOne($id);
        $model->delete();
        $log = new LogPustakawan();
        $log->dokumen_id = $model->id_dokumen;
        $log->controller = 'DokumenPembentukanPuu';
        $log->aksi = 'Hapus Eksemplar';
        $log->keterangan = 'User ' . \Yii::$app->user->identity->username . ' melakukan hapus data eksemplar pada ' . DateHelper::formatIndonesian(date('Y-m-d H:i:s'));
        $log->save();
        Yii::$app->session->setFlash('danger', 'Data Eksemplar berhasil dihapus');
        return $this->redirect(['view', 'id' => $model->id_dokumen]);
    }

    /*---------- END EKSEMPLAR -----------------*/

    public function actionCetak()
    {
        $list = Yii::$app->request->post('list', '');
        if (!empty($list)) {
            $ids = array_map('intval', explode(',', $list));
            $searchModel = new EksemplarSearch();
            $dataProvider = $searchModel->get_eksemplarByIds($ids);
            $result = $dataProvider->getModels();

            if (empty($result)) {
                Yii::$app->session->setFlash('error', 'Tidak ada eksemplar pada judul buku yang telah dipilih.');
            }

            return $this->render('../monografi/eksemplar/barcode', [
                'result' => $result,
            ]);
        } else {
            Yii::$app->session->setFlash('error', 'Tidak ada data yang dipilih.');
        }
    }

    public function actionDownload($id)
    {
        return SafeDownload::sendFile('@common/dokumen', $id);
    }

    public function actionDownloadPeraturan($id)
    {
        $file = DataLampiran::find()->where(['id_dokumen' => $id])->one();
        if (!$file) {
            throw new NotFoundHttpException('Data lampiran tidak ditemukan.');
        }
        return SafeDownload::sendFile('@common/dokumen', $file->dokumen_lampiran);
    }

    public function actionDownloadAbstrak($id)
    {
        return SafeDownload::sendFile('@common/dokumen', $id);
    }

    public function actionGetPeraturan($zipId)
    {
        $location = JenisPeraturan::find()->where(['name' => $zipId])->one();
        echo Json::encode($location);
    }

    protected function findModel($id)
    {
        $model = Monografi::findOne($id);
        if (!$model || !in_array($model->jenis_peraturan, DocumentType::groupTypeNames(DocumentGroup::LEGISLATION_FORMATION))) {
            throw new NotFoundHttpException('Dokumen tidak ditemukan.');
        }
        return $model;
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add backend/controllers/DokumenPembentukanPuuController.php
git commit -m "feat(puu): add DokumenPembentukanPuuController with scoped CRUD and 4 sub-entity actions"
```

---

### Task 3: Create view files

**Files:**
- Create: `backend/views/dokumen-pembentukan-puu/index.php`
- Create: `backend/views/dokumen-pembentukan-puu/view.php`
- Create: `backend/views/dokumen-pembentukan-puu/_detail.php`
- Create: `backend/views/dokumen-pembentukan-puu/create.php`
- Create: `backend/views/dokumen-pembentukan-puu/update.php`
- Create: `backend/views/dokumen-pembentukan-puu/_form-create.php`
- Create: `backend/views/dokumen-pembentukan-puu/_form-update.php`

All views are cloned from monografi equivalents with these changes:
- Breadcrumb labels: "Dokumen Pembentukan PUU" instead of "Monografi"
- Page titles: "Dokumen Pembentukan PUU" instead of "Monografi Hukum"
- `jenis_peraturan` filter dropdown: uses `DocumentType::findByGroup(DocumentGroup::LEGISLATION_FORMATION)` mapped by `name, name` instead of `JenisPeraturan::find()->where(['parent_id' => 2])`
- `jenis_peraturan` label: "Jenis Dokumen" instead of "Jenis Monografi"
- `judul` label: "Judul Dokumen" instead of "Judul Monografi"
- Panel headings: "Data Dokumen Pembentukan PUU" instead of "Data Monografi Hukum"
- Flash messages: "Dokumen Pembentukan PUU" instead of "Monografi"
- Action column URLs: use `dokumen-pembentukan-puu/*` controller routes
- Form `jenis_peraturan` dropdown: scoped to `DocumentType::findByGroup()` with prompt "Pilih Jenis Dokumen"
- Form box header: "Dokumen Pembentukan PUU" instead of "Monografi Hukum"
- View has 5 tabs instead of 8: Data Utama, T.E.U, Subjek, Data Lampiran, Eksemplar (no Peraturan Terkait, Dokumen Terkait, Uji Materi, Log)
- Barcode form action URL: `/dokumen-pembentukan-puu/cetak` instead of `/monografi/cetak`
- "Tambah Data" button URL: `['create']` (resolves to this controller)
- Verify/unverify button: links to `catatan-verifikasi/monografi` (shared — same verification flow)

- [ ] **Step 3: Commit**

```bash
git add backend/views/dokumen-pembentukan-puu/
git commit -m "feat(puu): add view files for Dokumen Pembentukan PUU management page"
```

---

### Task 4: Update sidebar URLs

**Files:**
- Modify: `backend/views/layouts/leftside.php`

Change the PUU sidebar children URLs from `/monografi/index` to `/dokumen-pembentukan-puu/index` and the search param from `MonografiSearch[documentTypeId]` to `DokumenPembentukanPuuSearch[documentTypeId]`.

- [ ] **Step 1: Update leftside.php**

In `backend/views/layouts/leftside.php`, change the `$puuChildren` array mapping:

```php
$puuChildren = array_map(static function (DocumentType $t) {
    return [
        'label' => $t->name,
        'url' => [
            '/dokumen-pembentukan-puu/index',
            'DokumenPembentukanPuuSearch[documentTypeId]' => $t->id,
        ],
    ];
}, $puuTypes);
```

- [ ] **Step 2: Commit**

```bash
git add backend/views/layouts/leftside.php
git commit -m "feat(puu): update sidebar links to point to dedicated Dokumen Pembentukan PUU controller"
```

---

### Task 5: RBAC migration

**Files:**
- Create: `console/migrations/m260527_120000_add_dokumen_pembentukan_puu_rbac.php`

This migration adds route permissions for the new controller and a menu entry under "Dokumen Hukum".

- [ ] **Step 1: Create the migration file**

```php
<?php

use yii\db\Migration;

class m260527_120000_add_dokumen_pembentukan_puu_rbac extends Migration
{
    public function safeUp()
    {
        $routes = [
            '/dokumen-pembentukan-puu/index',
            '/dokumen-pembentukan-puu/create',
            '/dokumen-pembentukan-puu/view',
            '/dokumen-pembentukan-puu/update',
            '/dokumen-pembentukan-puu/delete',
            '/dokumen-pembentukan-puu/inactive',
            '/dokumen-pembentukan-puu/tambah-pengarang',
            '/dokumen-pembentukan-puu/ubah-pengarang',
            '/dokumen-pembentukan-puu/hapus-pengarang',
            '/dokumen-pembentukan-puu/tambah-pengarang2',
            '/dokumen-pembentukan-puu/view-pengarang',
            '/dokumen-pembentukan-puu/tambah-subyek',
            '/dokumen-pembentukan-puu/ubah-subyek',
            '/dokumen-pembentukan-puu/hapus-subyek',
            '/dokumen-pembentukan-puu/tambah-lampiran',
            '/dokumen-pembentukan-puu/ubah-lampiran',
            '/dokumen-pembentukan-puu/hapus-lampiran',
            '/dokumen-pembentukan-puu/tambah-eksemplar',
            '/dokumen-pembentukan-puu/ubah-eksemplar',
            '/dokumen-pembentukan-puu/hapus-eksemplar',
            '/dokumen-pembentukan-puu/cetak',
            '/dokumen-pembentukan-puu/download',
            '/dokumen-pembentukan-puu/download-peraturan',
            '/dokumen-pembentukan-puu/download-abstrak',
            '/dokumen-pembentukan-puu/get-peraturan',
            '/dokumen-pembentukan-puu/*',
        ];

        $time = time();
        foreach ($routes as $i => $route) {
            $this->insert('{{%auth_item}}', [
                'name' => $route,
                'type' => 2,
                'description' => 'Dokumen Pembentukan PUU: ' . $route,
                'created_at' => $time,
                'updated_at' => $time,
            ]);
        }

        $pustakawanRoutes = [
            '/dokumen-pembentukan-puu/index',
            '/dokumen-pembentukan-puu/create',
            '/dokumen-pembentukan-puu/view',
            '/dokumen-pembentukan-puu/update',
            '/dokumen-pembentukan-puu/inactive',
            '/dokumen-pembentukan-puu/tambah-pengarang',
            '/dokumen-pembentukan-puu/ubah-pengarang',
            '/dokumen-pembentukan-puu/hapus-pengarang',
            '/dokumen-pembentukan-puu/tambah-pengarang2',
            '/dokumen-pembentukan-puu/tambah-subyek',
            '/dokumen-pembentukan-puu/ubah-subyek',
            '/dokumen-pembentukan-puu/hapus-subyek',
            '/dokumen-pembentukan-puu/tambah-lampiran',
            '/dokumen-pembentukan-puu/ubah-lampiran',
            '/dokumen-pembentukan-puu/hapus-lampiran',
            '/dokumen-pembentukan-puu/tambah-eksemplar',
            '/dokumen-pembentukan-puu/ubah-eksemplar',
            '/dokumen-pembentukan-puu/hapus-eksemplar',
            '/dokumen-pembentukan-puu/cetak',
            '/dokumen-pembentukan-puu/download',
            '/dokumen-pembentukan-puu/download-peraturan',
            '/dokumen-pembentukan-puu/download-abstrak',
            '/dokumen-pembentukan-puu/get-peraturan',
        ];

        foreach ($pustakawanRoutes as $route) {
            $this->insert('{{%auth_item_child}}', [
                'parent' => 'pustakawan',
                'child' => $route,
            ]);
        }

        $this->insert('{{%auth_item_child}}', [
            'parent' => 'superadmin',
            'child' => '/dokumen-pembentukan-puu/*',
        ]);

        $this->insert('{{%menu}}', [
            'name' => 'Dokumen Pembentukan PUU',
            'parent' => 16,
            'route' => '/dokumen-pembentukan-puu/index',
            'order' => 15,
            'data' => serialize(['fa fa-file-text-o']),
        ]);
    }

    public function safeDown()
    {
        $routes = [
            '/dokumen-pembentukan-puu/index',
            '/dokumen-pembentukan-puu/create',
            '/dokumen-pembentukan-puu/view',
            '/dokumen-pembentukan-puu/update',
            '/dokumen-pembentukan-puu/delete',
            '/dokumen-pembentukan-puu/inactive',
            '/dokumen-pembentukan-puu/tambah-pengarang',
            '/dokumen-pembentukan-puu/ubah-pengarang',
            '/dokumen-pembentukan-puu/hapus-pengarang',
            '/dokumen-pembentukan-puu/tambah-pengarang2',
            '/dokumen-pembentukan-puu/view-pengarang',
            '/dokumen-pembentukan-puu/tambah-subyek',
            '/dokumen-pembentukan-puu/ubah-subyek',
            '/dokumen-pembentukan-puu/hapus-subyek',
            '/dokumen-pembentukan-puu/tambah-lampiran',
            '/dokumen-pembentukan-puu/ubah-lampiran',
            '/dokumen-pembentukan-puu/hapus-lampiran',
            '/dokumen-pembentukan-puu/tambah-eksemplar',
            '/dokumen-pembentukan-puu/ubah-eksemplar',
            '/dokumen-pembentukan-puu/hapus-eksemplar',
            '/dokumen-pembentukan-puu/cetak',
            '/dokumen-pembentukan-puu/download',
            '/dokumen-pembentukan-puu/download-peraturan',
            '/dokumen-pembentukan-puu/download-abstrak',
            '/dokumen-pembentukan-puu/get-peraturan',
            '/dokumen-pembentukan-puu/*',
        ];

        foreach ($routes as $route) {
            $this->delete('{{%auth_item_child}}', ['child' => $route]);
        }

        foreach ($routes as $route) {
            $this->delete('{{%auth_item}}', ['name' => $route]);
        }

        $this->delete('{{%menu}}', [
            'name' => 'Dokumen Pembentukan PUU',
            'parent' => 16,
        ]);
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add console/migrations/m260527_120000_add_dokumen_pembentukan_puu_rbac.php
git commit -m "feat(puu): add RBAC migration for DokumenPembentukanPuu controller routes and menu entry"
```

---

### Task 6: Verify and smoke test

- [ ] **Step 1: Run syntax check on all new PHP files**

```bash
php -l backend/models/DokumenPembentukanPuuSearch.php
php -l backend/controllers/DokumenPembentukanPuuController.php
php -l backend/views/dokumen-pembentukan-puu/index.php
php -l backend/views/dokumen-pembentukan-puu/view.php
php -l backend/views/dokumen-pembentukan-puu/_detail.php
php -l backend/views/dokumen-pembentukan-puu/create.php
php -l backend/views/dokumen-pembentukan-puu/update.php
php -l backend/views/dokumen-pembentukan-puu/_form-create.php
php -l backend/views/dokumen-pembentukan-puu/_form-update.php
php -l console/migrations/m260527_120000_add_dokumen_pembentukan_puu_rbac.php
```

Expected: No syntax errors.

- [ ] **Step 2: Run the RBAC migration**

```bash
php yii migrate/up
```

Expected: Migration applies successfully, adds auth_item and auth_item_child rows.

- [ ] **Step 3: Verify sidebar renders**

Log in as superadmin, navigate to the backend. Verify that:
- "Dokumen Pembentukan PUU" appears under "Dokumen Hukum" in sidebar
- Each sub-item (Naskah Akademik, Penelitian Hukum, etc.) links to `/dokumen-pembentukan-puu/index?DokumenPembentukanPuuSearch[documentTypeId]=<id>`
- Clicking a sub-item opens the filtered grid showing only that document type

- [ ] **Step 4: Verify CRUD operations**

- Index: Grid shows only `legislation_formation` documents. Filter dropdown shows only PUU types.
- Create: Jenis Peraturan dropdown shows only PUU types. Creating a document saves it.
- View: Detail page shows 5 tabs. Sub-entity CRUD works.
- Update: Can edit document, dropdown scoped to PUU types.
- Delete: Deletes document (or shows FK error).
- Verify/unverify toggle works.
- Scope enforcement: Visiting `/dokumen-pembentukan-puu/view?id=<regular-monografi-id>` returns 404.