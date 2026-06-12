<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use frontend\models\PeraturanTerkait;
use frontend\models\DokumenTerkait;
use frontend\models\DataLampiran;
use frontend\models\DataStatus;
use frontend\models\HasilUjiMateri;
use frontend\models\DataPengarang;
use frontend\models\DataSubyek;
/* @var $this yii\web\View */
/* @var $model frontend\models\Dokumen */

$this->title = $model->judul ?? $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Dokumens', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$this->registerMetaTag(['name' => 'description', 'content' => mb_strimwidth(strip_tags($model->abstrak ?? $model->judul ?? ''), 0, 160, '...')]);
?>

<section class="page-title-section bg-img cover-background" data-overlay-dark="7" data-background="img/banner/bg1.jpg">
    <div class="container">
        <h1><?= Html::encode($model->judul ?? 'Detail Dokumen') ?></h1>
        <ul class="text-center">
            <li><?= Html::a('Home', ['/']); ?></li>
            <li>
                <span class="active"><?= Html::encode($model->judul ?? 'Detail') ?></span>
            </li>
        </ul>
    </div>
</section>
<!-- end page title section -->

<!-- start blog detail section -->
<section class="blogs">
    <div class="container">
        <div class="widget search">

            <div class="input-group mb-3">
                <input type="text" class="form-control" placeholder="cari dokumen hukum lain..." aria-label="Recipient's username" aria-describedby="button-addon2">
                <div class="input-group-append">
                    <button class="btn btn-primary" type="button" id="button-addon2"><span class="ti-search"></span></button>
                </div>
            </div>
        </div>
        <div class="row">
            <!--  start blog left-->

            <div class="col-lg-8 col-md-12 sm-margin-50px-bottom">
                <div class="posts">
                    <!--  start post-->

                    <div class="content">
                        <div class="blog-list-simple-text post-meta margin-20px-bottom">
                            <div class="post-title">
                                <h5><?= $model->judul; ?></h5>
                            </div>
                        </div>
                        <div class="row align-items-end">
                            <div class="col-lg-6 col-md-6 mb-3">
                                Tempat Terbit<br>
                                <span class="text-extra-dark-gray font-weight-600"><?= $model->tempat_terbit; ?></span>
                            </div>

                            <div class="col-lg-6 col-md-6 mb-3">
                                Tanggal Penetapan <br>
                                <span class="text-extra-dark-gray font-weight-600"><?= $model->tanggal_penetapan; ?></span>
                            </div>
                            <div class="col-lg-6 col-md-6 mb-3">
                                Tanggal Pengundangan<br>
                                <span class="text-extra-dark-gray font-weight-600"><?= $model->tanggal_pengundangan; ?></span>
                            </div>
                            <div class="col-lg-6 col-md-6 mb-3">
                                Sumber<br>
                                <span class="text-extra-dark-gray font-weight-600"><?= $model->sumber; ?></span>
                            </div>

                            <div class="col-lg-6 col-md-6 mb-3">
                                Urusan Pemerintahan<br>
                                <span class="text-extra-dark-gray font-weight-600"><?= $model->urusan_pemerintahan; ?></span>
                            </div>
                            <div class="col-lg-6 col-md-6 mb-3">
                                Bidang Hukum<br>
                                <span class="text-extra-dark-gray font-weight-600"><?= $model->bidang_hukum; ?></span>
                            </div>

                            <div class="col-lg-6 col-md-6 mb-3">
                                Bahasa<br>
                                <span class="text-extra-dark-gray font-weight-600"><?= $model->bahasa; ?></span>
                            </div>
                            <div class="col-lg-6 col-md-6 mb-3">
                                Pemrakarsa<br>
                                <span class="text-extra-dark-gray font-weight-600"><?= $model->pemrakarsa; ?></span>
                            </div>
                            <div class="col-lg-6 col-md-6 mb-3">
                                Penandatanganan<br>
                                <span class="text-extra-dark-gray font-weight-600"><?= $model->penandatanganan; ?></span>
                            </div>
                        </div>

                        <div class="row align-items-end">

                            <div class="col-lg-12 col-md-12 mt-3">
                                <span class="text-extra-dark-gray font-weight-600">Peraturan Terkait</span><br>
                                <?php
                                $peraturanterkait = PeraturanTerkait::find()->where(['id_dokumen' => $model->id])->all();

                                if (!empty($peraturanterkait)) {
                                    echo '<ul>';
                                    foreach ($peraturanterkait as  $data) {
                                        echo '<li class="list-group-item">' . $data['status_perter'];
                                        echo ' : ';
                                        //echo $data->getJudul($data['peraturan_terkait']);
                                        echo Html::a($data->getJudul($data['peraturan_terkait']), ['/dokumen/view', 'id' => $data->peraturan_terkait], ['class' => 'text-primary', 'title' => 'lihat detail']);
                                        echo '</li>';
                                        # code...
                                    }
                                } else {
                                    echo '<span class="text-extra-dark-gray font-weight-600">Data Tidak Tersedia</span>';
                                }
                                echo '</ul>';
                                ?>
                            </div>
                        </div>

                        <div class="row align-items-end">
                            <div class="col-lg-12 col-md-12 mt-4">
                                <span class="text-extra-dark-gray font-weight-600">Dokumen Terkait</span><br>
                                <?php
                                $dokumenterkait = DokumenTerkait::find()->where(['id_dokumen' => $model->id])->all();

                                if (!empty($dokumenterkait)) {
                                    foreach ($dokumenterkait as  $data) {
                                        //echo $data['document_terkait'];
                                        echo Html::a($data['document_terkait'], ['/dokumen/download', 'id' => $data->document_terkait], ['class' => 'btn btn-secondary btn-sm mb-2 btn-hover-primary', 'title' => 'download file']);

                                        echo '<br>';
                                        # code...
                                    }
                                } else {
                                    echo '<span class="text-extra-dark-gray font-weight-600">Data belum Tersedia</span>';
                                }
                                // echo '</ul>';

                                ?>
                            </div>
                        </div>

                        <div class="row align-items-end">
                            <div class="col-lg-12 col-md-12 mt-4">
                                <span class="text-extra-dark-gray font-weight-600">Hasil Uji Materi</span><br>
                                <?php
                                $ujimateri = HasilUjiMateri::find()->where(['id_dokumen' => $model->id])->all();

                                if (!empty($ujimateri)) {
                                    foreach ($ujimateri as  $data) {
                                        //echo $data['document_terkait'];
                                        echo Html::a($data['hasil_uji_materi'], ['/dokumen/download', 'id' => $data->hasil_uji_materi], ['class' => 'btn btn-secondary btn-sm mb-2 btn-hover-primary', 'title' => 'download file']);

                                        echo '<br>';
                                        # code...
                                    }
                                } else {
                                    echo '<span class="text-extra-dark-gray font-weight-600">Data belum Tersedia</span>';
                                }
                                // echo '</ul>';

                                ?>
                            </div>
                        </div>

                        <div class="row align-items-end">
                            <div class="col-lg-12 col-md-12 mt-4">
<?php

use yii\helpers\Url;
// The original file already uses yii\helpers\Html, frontend\models\DataLampiran, frontend\models\DataPengarang, frontend\models\DataSubyek
// So no need to re-declare them here.

/* @var $this yii\web\View */
/* @var $model frontend\models\Dokumen */

$this->title = $model->judul;

// --- SEO Metatags & Open Graph ---
$baseUrl = Url::to(['/'], true);
$currentUrl = Url::to(['/dokumen/view', 'id' => $model->id], true);
$desc = !empty($model->abstrak) ? strip_tags($model->abstrak) : $model->judul;
$desc = mb_strimwidth($desc, 0, 160, "...");

$this->registerMetaTag(['name' => 'description', 'content' => $desc]);

// Open Graph
$this->registerMetaTag(['property' => 'og:title', 'content' => $this->title]);
$this->registerMetaTag(['property' => 'og:description', 'content' => $desc]);
$this->registerMetaTag(['property' => 'og:type', 'content' => 'article']);
$this->registerMetaTag(['property' => 'og:url', 'content' => $currentUrl]);
if (!empty($model->gambar_sampul)) {
    $this->registerMetaTag(['property' => 'og:image', 'content' => $baseUrl . 'common/dokumen/' . $model->gambar_sampul]);
}

// Twitter
$this->registerMetaTag(['name' => 'twitter:card', 'content' => 'summary_large_image']);
$this->registerMetaTag(['name' => 'twitter:title', 'content' => $this->title]);
$this->registerMetaTag(['name' => 'twitter:description', 'content' => $desc]);

$this->params['breadcrumbs'][] = ['label' => 'Dokumen', 'url' => ['index']];
$this->params['breadcrumbs'][] = Html::encode($this->title);
?>

<div class="dokumen-view-wrapper" style="background-color: #f8fafc; min-height: 100vh; padding: 100px 0 40px 0;">
    <div class="container">
        <div class="row">
            <!-- Main Content Area -->
            <div class="col-lg-8">
                <div class="bg-white rounded-4 shadow-sm p-4 p-md-5 mb-4">
                    <!-- Heading Section -->
                    <div class="mb-5">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill font-weight-600">
                                <?= Html::encode($model->jenis_peraturan ?: 'Dokumen') ?>
                            </span>
                            <?php if ($model->status): ?>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2 rounded-pill font-weight-600">
                                    <?= Html::encode($model->status) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <h1 class="h3 font-weight-700 text-dark-blue mb-0">
                            <?= Html::encode($model->judul) ?>
                        </h1>
                    </div>

                    <!-- Metadata Grid -->
                    <div class="row g-4 mb-5">
                        <?php if ($model->nomor_peraturan): ?>
                        <div class="col-sm-6">
                            <label class="text-muted small text-uppercase font-weight-700 mb-1 d-block tracking-wider">Nomor</label>
                            <div class="text-dark font-weight-600"><?= Html::encode($model->nomor_peraturan) ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="col-sm-6">
                            <label class="text-muted small text-uppercase font-weight-700 mb-1 d-block tracking-wider">Tahun Terbit</label>
                            <div class="text-dark font-weight-600"><?= Html::encode($model->tahun_terbit ?: '-') ?></div>
                        </div>

                        <?php if ($model->tanggal_penetapan): ?>
                        <div class="col-sm-6">
                            <label class="text-muted small text-uppercase font-weight-700 mb-1 d-block tracking-wider">Tanggal</label>
                            <div class="text-dark font-weight-600"><?= \common\components\DateHelper::formatIndonesian($model->tanggal_penetapan) ?></div>
                        </div>
                        <?php endif; ?>

                        <div class="col-sm-6">
                            <label class="text-muted small text-uppercase font-weight-700 mb-1 d-block tracking-wider">Tempat Terbit</label>
                            <div class="text-dark font-weight-600"><?= Html::encode($model->tempat_terbit ?: '-') ?></div>
                        </div>
                        
                        <div class="col-sm-6">
                            <label class="text-muted small text-uppercase font-weight-700 mb-1 d-block tracking-wider">Bahasa</label>
                            <div class="text-dark font-weight-600"><?= Html::encode($model->bahasa ?: '-') ?></div>
                        </div>

                        <div class="col-sm-6">
                            <label class="text-muted small text-uppercase font-weight-700 mb-1 d-block tracking-wider">Bidang Hukum</label>
                            <div class="text-dark font-weight-600"><?= Html::encode($model->bidang_hukum ?: '-') ?></div>
                        </div>
                    </div>

                    <hr class="border-light-gray my-5">

                    <!-- Relation Sections -->
                    <div class="space-y-5">
                        <!-- Technical Information -->
                        <div class="row g-4">
                            <div class="col-md-6">
                                <h4 class="h6 font-weight-700 text-uppercase mb-3 tracking-wide">T.E.U Badan</h4>
                                <?php
                                $teu = DataPengarang::find()->where(['id_dokumen' => $model->id])->all();
                                if (!empty($teu)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-borderless align-middle mb-0 teu-table">
                                            <tbody class="small">
                                                <?php foreach ($teu as $data): ?>
                                                    <tr class="border-bottom border-light">
                                                        <td class="ps-0 py-2 font-weight-600"><?= Html::encode($data->namaPengarang->name) ?></td>
                                                        <td class="py-2 text-muted"><?= Html::encode($data->tipePengarang->name) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted italic small">Tidak ada data</span>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <h4 class="h6 font-weight-700 text-uppercase mb-3 tracking-wide">Subjek</h4>
                                <?php
                                $subjek = DataSubyek::find()->where(['id_dokumen' => $model->id])->all();
                                if (!empty($subjek)): ?>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php foreach ($subjek as $data): ?>
                                            <span class="badge bg-light text-dark border px-3 py-2 font-weight-500 rounded-pill subjek-badge">
                                                <?= Html::encode($data->subyek) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted italic small">Tidak ada data</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="sticky-top" style="top: 100px;">
                    <!-- Attachments Card -->
                    <div class="bg-white rounded-4 shadow-sm p-4 mb-4">
                        <h4 class="h6 font-weight-700 mb-4 d-flex align-items-center gap-2">
                            <i class="ti-download text-primary"></i> Lampiran & Berkas
                        </h4>
                        <div class="d-grid gap-2">
                            <?php
                            $lampiran = DataLampiran::find()->where(['id_dokumen' => $model->id])->all();
                            if (!empty($lampiran)): 
                                foreach ($lampiran as $data):
                                    $fileName = $data['dokumen_lampiran'];
                                ?>
                                    <?= Html::a(
                                        '<i class="ti-file lampiran-link__icon" aria-hidden="true"></i><span class="lampiran-link__name">' . Html::encode($fileName) . '</span>',
                                        ['/common/dokumen/' . $data->dokumen_lampiran],
                                        [
                                            'class' => 'lampiran-link btn btn-outline-primary font-weight-600 rounded-3 py-2 px-3',
                                            'target' => '_blank',
                                            'title' => Html::encode($fileName),
                                        ]
                                    ) ?>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <?php if (!empty($model->abstrak)): ?>
                                <?= Html::a('<i class="ti-book mr-2"></i> Abstrak', ['/common/dokumen/' . $model->abstrak], [
                                    'class' => 'btn btn-primary font-weight-600 rounded-3 py-2',
                                    'target' => '_blank',
                                    'title' => 'Lihat Abstrak'
                                ]) ?>
                            <?php endif; ?>

                            <?php if (empty($lampiran) && empty($model->abstrak)): ?>
                                <div class="text-center py-4 text-muted small italic border border-dashed rounded-3">
                                    Tidak ada berkas tersedia
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Additional Info Card -->
                    <div class="bg-white rounded-4 shadow-sm p-4 mb-4">
                        <h4 class="h6 font-weight-700 mb-4">Informasi Tambahan</h4>
                        <div class="space-y-4">
                            <div>
                                <label class="text-muted small text-uppercase font-weight-700 mb-1 d-block tracking-wider">Sumber</label>
                                <p class="text-dark small mb-0"><?= Html::encode($model->sumber ?: '-') ?></p>
                            </div>
                            <div>
                                <label class="text-muted small text-uppercase font-weight-700 mb-1 d-block tracking-wider">Penerbit</label>
                                <p class="text-dark small mb-0"><?= Html::encode($model->penerbit ?: '-') ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.space-y-3 > * + * { margin-top: 0.75rem !important; }
.space-y-4 > * + * { margin-top: 1rem !important; }
.space-y-5 > * + * { margin-top: 2rem !important; }
.text-dark-blue { color: #1e293b; }
.font-weight-600 { font-weight: 600; }
.font-weight-700 { font-weight: 700; }
.tracking-wide { letter-spacing: 0.025em; }
.tracking-wider { letter-spacing: 0.05em; }
.italic { font-style: italic; }
.rounded-4 { border-radius: 1rem !important; }
</style>
                            </div>
                        </div>

                        <div class="row align-items-end">
                            <div class="col-lg-12 col-md-12 mt-4">
                                <span class="text-extra-dark-gray font-weight-600">T.E.U BADAN</span><br>
                                <table class="table">
                                    <thead>
                                        <tr class="active">
                                            <th>Nama Pengarang</th>
                                            <th>Tipe Pengarang</th>
                                            <th>Jenis Pengarang</th>
                                        </tr>
                                    </thead>
                                    <?php
                                    $teu = DataPengarang::find()->where(['id_dokumen' => $model->id])->all();
                                    if (!empty($teu)) {
                                        echo '<tbody>';
                                        foreach ($teu as  $data) {
                                            //echo $data['document_terkait'];
                                            echo '<tr><td>' . $data->namaPengarang->name . '</td>';
                                            echo '<td>' . $data->tipePengarang->name . '</td>';
                                            echo '<td>' . $data->jenisPengarang->name . '</td></tr>';
                                        }
                                        echo '</tbody>';
                                    }
                                    ?>
                                </table>
                            </div>
                        </div>

                        <div class="row align-items-end">
                            <div class="col-lg-12 col-md-12 mt-4">
                                <span class="text-extra-dark-gray font-weight-600">SUBJEK</span><br>
                                <table class="table">
                                    <thead>
                                        <tr class="active">
                                            <th>Nama Subjek</th>
                                            <th>Tipe Subjek</th>
                                            <th>Jenis Subjek</th>
                                        </tr>
                                    </thead>
                                    <?php
                                    $subjek = DataSubyek::find()->where(['id_dokumen' => $model->id])->all();
                                    if (!empty($subjek)) {
                                        echo '<tbody>';
                                        foreach ($subjek as  $data) {

                                            echo '<tr><td>' . $data->subyek . '</td>';
                                            echo '<td>' . $data->tipe_subyek . '</td>';
                                            echo '<td>' . $data->jenis_subyek . '</td></tr>';
                                        }
                                        echo '</tbody>';
                                    }
                                    ?>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--  end blog left-->

            <!--  start blog right-->
            <div class="col-lg-4 col-md-12 padding-30px-left sm-padding-15px-left">
                <div class="side-bar">



                    <div class="shadow">
                        <ul class="list-group mt-2">
                            <li class="list-group-item text-center">JENIS DOKUMEN</li>
                            <li class="list-group-item list-group-item-primary text-center"><strong><?= $model->bentuk_peraturan; ?></strong></li>
                        </ul>
                    </div>

                    <div class="shadow">
                        <ul class="list-group mt-2">
                            <li class="list-group-item text-center">STATUS</li>
                            <li class="list-group-item list-group-item-danger text-center"><strong><?= $model->status; ?></strong></li>
                        </ul>
                    </div>



                    <div class="widget">
                        <div class="widget-title margin-35px-bottom mt-4">
                            <h3>Lampiran</h3>
                        </div>
                        <ul class="widget-list">
                            <?php
                            $lampiran = DataLampiran::find()->where(['id_dokumen' => $model->id])->all();

                            if (!empty($lampiran)) {
                                foreach ($lampiran as  $data) {
                                    //echo '<li>'.$data['dokumen_lampiran'].'</li>';
                                    echo Html::a($data['dokumen_lampiran'], ['/dokumen/download', 'id' => $data->dokumen_lampiran], ['class' => 'btn btn-secondary btn-sm mb-2 btn-hover-primary', 'title' => 'download file']);

                                    # code...
                                }
                            }
                            echo Html::a($model->abstrak, ['/dokumen/download', 'id' => $model->abstrak], ['class' => 'btn btn-secondary btn-sm mb-2 btn-hover-primary', 'title' => 'download file']);
                            ?>

                        </ul>
                    </div>

                    <div class="widget">
                        <div class="widget-title margin-35px-bottom mt-4">
                            <h3>Keterangan Status</h3>
                        </div>
                        <ul class="widget-list">
                            <?php
                            $status = DataStatus::find()->where(['id_dokumen' => $model->id])->all();

                            if (!empty($status)) {

                                foreach ($status as  $data) {
                                    //echo '<li>'.$data['dokumen_lampiran'].'</li>';
                                    //echo '<li>'.Html::a($data['status_peraturan'], ['/dokumen/download','id'=>$data->id_dokumen], ['title' => 'download file']).'</li>';
                                    echo  '<span class="text-extra-dark-gray font-weight-600">' . $data['status_peraturan'] . '</span> ' . Html::a($data->getJudul($data['id_dokumen']), ['/dokumen/view', 'id' => $data->id_dokumen], ['class' => 'text-primary', 'title' => 'lihat detail']) . '<br><br>';

                                    # code...
                                }
                                echo '</ul>';
                            }
                            ?>
                        </ul>
                    </div>

                </div>
            </div>
            <!--  end blog right-->

        </div>
    </div>
</section>