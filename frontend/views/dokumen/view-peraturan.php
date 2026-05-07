<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\Json;
use frontend\models\PeraturanTerkait;
use frontend\models\DokumenTerkait;
use frontend\models\DataLampiran;
use frontend\models\DataStatus;
use frontend\models\HasilUjiMateri;
use frontend\models\DataPengarang;
use frontend\models\DataSubyek;

/* @var $this yii\web\View */
/* @var $model frontend\models\Dokumen */

$this->title = $title . ' - JDIH';

// --- SEO Metatags & Open Graph ---
$baseUrl = Url::to(['/'], true);
$currentUrl = Url::current([], true);
$desc = !empty($model->abstrak) ? strip_tags($model->abstrak) : $deskripsi;
$desc = mb_strimwidth($desc, 0, 160, "...");
$fallbackImage = $baseUrl . 'assets/img/jdih-default.png';

$this->registerMetaTag(['name' => 'description', 'content' => $desc]);
$this->registerMetaTag(['name' => 'keywords', 'content' => implode(', ', $keywords)]);
$this->registerLinkTag(['rel' => 'canonical', 'href' => $currentUrl]);

// Open Graph
$this->registerMetaTag(['property' => 'og:site_name', 'content' => 'JDIH']);
$this->registerMetaTag(['property' => 'og:title', 'content' => $this->title]);
$this->registerMetaTag(['property' => 'og:description', 'content' => $desc]);
$this->registerMetaTag(['property' => 'og:type', 'content' => 'article']);
$this->registerMetaTag(['property' => 'og:url', 'content' => $currentUrl]);
$ogImage = !empty($model->gambar_sampul) ? $baseUrl . 'common/dokumen/' . $model->gambar_sampul : $fallbackImage;
$this->registerMetaTag(['property' => 'og:image', 'content' => $ogImage]);

// Twitter
$this->registerMetaTag(['name' => 'twitter:card', 'content' => 'summary_large_image']);
$this->registerMetaTag(['name' => 'twitter:title', 'content' => $this->title]);
$this->registerMetaTag(['name' => 'twitter:description', 'content' => $desc]);
$this->registerMetaTag(['name' => 'twitter:image', 'content' => $ogImage]);

$this->params['breadcrumbs'][] = ['label' => 'Dokumen', 'url' => ['index']];
$this->params['breadcrumbs'][] = Html::encode($this->title);
?>

<?php
$ldJson = [
    '@context' => 'https://schema.org',
    '@type' => 'Legislation',
    'name' => $model->judul,
    'legislationIdentifier' => $model->nomor_peraturan,
    'legislationJurisdiction' => [
        '@type' => 'AdministrativeArea',
        'name' => 'Indonesia',
    ],
];
if (!empty($model->tanggal_penetapan)) {
    $ldJson['legislationDate'] = date('Y-m-d', strtotime($model->tanggal_penetapan));
}
if (!empty($model->abstrak)) {
    $ldJson['description'] = strip_tags($model->abstrak);
}
$this->registerJs('<script type="application/ld+json">' . Json::encode($ldJson) . '</script>', \yii\web\View::POS_HEAD);
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
                                <?= Html::encode($model->jenis_peraturan) ?>
                            </span>
                            <?php if ($model->status): ?>
                                <span class="badge <?= stripos($model->status, 'Berlaku') !== false && stripos($model->status, 'Tidak') === false ? 'bg-success' : 'bg-danger' ?> bg-opacity-10 <?= stripos($model->status, 'Berlaku') !== false && stripos($model->status, 'Tidak') === false ? 'text-success' : 'text-danger' ?> px-3 py-2 rounded-pill font-weight-600">
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
                        <div class="col-sm-6">
                            <label class="text-muted small text-uppercase font-weight-700 mb-1 d-block tracking-wider">Nomor Peraturan</label>
                            <div class="text-dark font-weight-600"><?= Html::encode($model->nomor_peraturan ?: '-') ?></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small text-uppercase font-weight-700 mb-1 d-block tracking-wider">Tahun Terbit</label>
                            <div class="text-dark font-weight-600"><?= Html::encode($model->tahun_terbit ?: '-') ?></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small text-uppercase font-weight-700 mb-1 d-block tracking-wider">Tanggal Penetapan</label>
                            <div class="text-dark font-weight-600"><?= $model->tanggal_penetapan ? $model->getTanggal($model->tanggal_penetapan) : '-' ?></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small text-uppercase font-weight-700 mb-1 d-block tracking-wider">Tanggal Pengundangan</label>
                            <div class="text-dark font-weight-600"><?= Html::encode($model->tanggal_pengundangan ?: '-') ?></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small text-uppercase font-weight-700 mb-1 d-block tracking-wider">Tempat Terbit</label>
                            <div class="text-dark font-weight-600"><?= Html::encode($model->tempat_terbit ?: '-') ?></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small text-uppercase font-weight-700 mb-1 d-block tracking-wider">Bahasa</label>
                            <div class="text-dark font-weight-600"><?= Html::encode($model->bahasa ?: '-') ?></div>
                        </div>
                    </div>

                    <hr class="border-light-gray my-5">

                    <!-- Relation Sections -->
                    <div class="space-y-5">
                        <!-- Peraturan Terkait -->
                        <div class="mb-5">
                            <h3 class="h5 font-weight-700 mb-4 d-flex align-items-center gap-2">
                                <i class="ti-link text-primary mt-1"></i> Peraturan Terkait
                            </h3>
                            <?php
                            $peraturanterkait = PeraturanTerkait::find()->where(['id_dokumen' => $model->id])->all();
                            if (!empty($peraturanterkait)): ?>
                                <ul class="list-group list-group-flush border-top border-bottom">
                                    <?php foreach ($peraturanterkait as $data): ?>
                                        <li class="list-group-item py-3 px-0 d-flex gap-3 align-items-baseline" style="background: transparent;">
                                            <i class="ti-arrow-right text-muted small"></i>
                                            <?= Html::a($data->getJudul($data['peraturan_terkait']), ['/dokumen/view', 'id' => $data->peraturan_terkait], [
                                                'class' => 'text-decoration-none text-primary hover-opacity',
                                                'title' => 'Lihat Detail'
                                            ]) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div class="p-4 bg-light bg-opacity-50 rounded-3 text-muted text-center italic border border-dashed">
                                    Data tidak tersedia
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Keterangan Status -->
                        <div class="mb-5">
                            <h3 class="h5 font-weight-700 mb-4 d-flex align-items-center gap-2">
                                <i class="ti-info-alt text-primary mt-1"></i> Keterangan Status
                            </h3>
                            <?php
                            $status = DataStatus::find()->where(['id_dokumen' => $model->id])->all();
                            if (!empty($status)): ?>
                                <div class="space-y-3">
                                    <?php foreach ($status as $data): 
                                        $catatan = !empty($data->catatan_status_peraturan) ? ' ( ' . $data->catatan_status_peraturan . ' ) ' : '';
                                    ?>
                                        <div class="p-3 bg-light rounded-3 d-flex align-items-start gap-3">
                                            <div class="badge bg-white shadow-sm text-dark px-2 py-1 rounded small font-weight-600"><?= Html::encode($data['status_peraturan']) ?></div>
                                            <div class="text-dark">
                                                <?= Html::a($data->getJudul($data['id_dokumen_target']), ['/dokumen/view', 'id' => $data['id_dokumen_target']], ['class' => 'text-primary font-weight-600']) ?>
                                                <span class="text-muted small"><?= Html::encode($catatan) ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="p-4 bg-light bg-opacity-50 rounded-3 text-muted text-center italic border border-dashed">
                                    Data belum tersedia
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Technical Information -->
                        <div class="row g-4">
                            <div class="col-md-6">
                                <h4 class="h6 font-weight-700 text-uppercase mb-3 tracking-wide">T.E.U Badan</h4>
                                <?php
                                $teu = DataPengarang::find()->where(['id_dokumen' => $model->id])->all();
                                if (!empty($teu)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-borderless align-middle mb-0">
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
                                            <span class="badge bg-light text-dark border px-3 py-2 font-weight-500 rounded-pill">
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
                                foreach ($lampiran as $data): ?>
                                    <?= Html::a('<i class="ti-file mr-2"></i> ' . Html::encode($data['dokumen_lampiran']), ['/common/dokumen/' . $data->dokumen_lampiran], [
                                        'class' => 'btn btn-outline-primary text-left font-weight-600 rounded-3 py-2 px-3',
                                        'target' => '_blank',
                                        'title' => 'Lihat Lampiran'
                                    ]) ?>
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

                    <!-- Source/Location Card -->
                    <div class="bg-white rounded-4 shadow-sm p-4 mb-4">
                        <h4 class="h6 font-weight-700 mb-4">Informasi Tambahan</h4>
                        <div class="space-y-4">
                            <div>
                                <label class="text-muted small text-uppercase font-weight-700 mb-1 d-block tracking-wider">Sumber</label>
                                <p class="text-dark small mb-0"><?= Html::encode($model->sumber ?: '-') ?></p>
                            </div>
                            <div>
                                <label class="text-muted small text-uppercase font-weight-700 mb-1 d-block tracking-wider">Pemrakarsa</label>
                                <p class="text-dark small mb-0"><?= Html::encode($model->pemrakarsa ?: '-') ?></p>
                            </div>
                            <div>
                                <label class="text-muted small text-uppercase font-weight-700 mb-1 d-block tracking-wider">Bidang Hukum</label>
                                <p class="text-dark small mb-0"><?= Html::encode($model->bidang_hukum ?: '-') ?></p>
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
.hover-opacity:hover { opacity: 0.8; }
.italic { font-style: italic; }
.rounded-4 { border-radius: 1rem !important; }
</style>
