<?php

use yii\helpers\Html;
use yii\helpers\Url;
use frontend\models\Eksemplar;
use frontend\models\DataLampiran;
use frontend\models\DataPengarang;
use frontend\models\DataSubyek;

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
$this->registerMetaTag(['property' => 'og:type', 'content' => 'book']);
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
                                <?= Html::encode($model->jenis_peraturan ?: 'Monografi') ?>
                            </span>
                        </div>
                        <h1 class="h3 font-weight-700 text-dark-blue mb-0">
                            <?= Html::encode($model->judul) ?>
                        </h1>
                    </div>

                    <!-- Metadata Grid -->
                    <div class="row g-4 mb-5">
                        <div class="col-sm-6">
                            <label class="text-muted small text-uppercase font-weight-700 mb-1 d-block tracking-wider">ISBN</label>
                            <div class="text-dark font-weight-600"><?= Html::encode($model->isbn ?: '-') ?></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small text-uppercase font-weight-700 mb-1 d-block tracking-wider">Tahun Terbit</label>
                            <div class="text-dark font-weight-600"><?= Html::encode($model->tahun_terbit ?: '-') ?></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small text-uppercase font-weight-700 mb-1 d-block tracking-wider">Penerbit</label>
                            <div class="text-dark font-weight-600"><?= Html::encode($model->penerbit ?: '-') ?></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small text-uppercase font-weight-700 mb-1 d-block tracking-wider">Tempat Terbit</label>
                            <div class="text-dark font-weight-600"><?= Html::encode($model->tempat_terbit ?: '-') ?></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small text-uppercase font-weight-700 mb-1 d-block tracking-wider">Klasifikasi</label>
                            <div class="text-dark font-weight-600"><?= Html::encode($model->klasifikasi ?: '-') ?></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small text-uppercase font-weight-700 mb-1 d-block tracking-wider">Nomor Panggil</label>
                            <div class="text-dark font-weight-600"><?= Html::encode($model->nomor_panggil ?: '-') ?></div>
                        </div>
                    </div>

                    <?php if (!empty($model->sumber)): ?>
                        <div class="mb-5">
                            <label class="text-muted small text-uppercase font-weight-700 mb-2 d-block tracking-wider">Anotasi</label>
                            <div class="p-4 bg-light bg-opacity-50 rounded-3 text-dark text-justify line-height-lg">
                                <?= Html::encode($model->sumber) ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <hr class="border-light-gray my-5">

                    <!-- Relation Sections -->
                    <div class="space-y-5">
                        <!-- Eksemplar -->
                        <div class="mb-5">
                            <h3 class="h5 font-weight-700 mb-4 d-flex align-items-center gap-2">
                                <i class="ti-layers text-primary mt-1"></i> Data Eksemplar
                            </h3>
                            <?php
                            $eksemplar = Eksemplar::find()->where(['id_dokumen' => $model->id])->all();
                            if (!empty($eksemplar)): ?>
                                <div class="table-responsive rounded-3 border">
                                    <table class="table table-hover mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="border-0 font-weight-700 small text-uppercase">Kode</th>
                                                <th class="border-0 font-weight-700 small text-uppercase">Lokasi Rak</th>
                                                <th class="border-0 font-weight-700 small text-uppercase">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($eksemplar as $data): ?>
                                                <tr>
                                                    <td class="font-weight-600"><?= Html::encode($data->kode_eksemplar) ?></td>
                                                    <td class="text-muted"><?= Html::encode($data->lokasi_rak) ?></td>
                                                    <td>
                                                        <span class="badge <?= $data->status_eksemplar == 'Dipinjam' ? 'bg-danger' : 'bg-success' ?> rounded-pill px-3 py-1">
                                                            <?= Html::encode($data->status_eksemplar) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="p-4 bg-light bg-opacity-50 rounded-3 text-muted text-center italic border border-dashed">
                                    Data eksemplar tidak tersedia
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
                                    <span class="text-muted italic small">Tidak ada pengarang</span>
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
                                    <span class="text-muted italic small">Tidak ada subjek</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="sticky-top" style="top: 100px;">
                    <!-- Cover Card -->
                    <?php if (!empty($model->gambar_sampul)): ?>
                        <div class="bg-white rounded-4 shadow-sm p-2 mb-4">
                            <?= Html::img('@web/common/dokumen/' . $model->gambar_sampul, [
                                'class' => 'img-fluid rounded-4 w-100',
                                'style' => 'object-fit: cover; max-height: 480px;'
                            ]) ?>
                        </div>
                    <?php endif; ?>

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
                                    Tidak ada berkas digital
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Additional Info Car -->
                    <div class="bg-white rounded-4 shadow-sm p-4 mb-4">
                        <h4 class="h6 font-weight-700 mb-4">Informasi Tambahan</h4>
                        <div class="space-y-4">
                            <div>
                                <label class="text-muted small text-uppercase font-weight-700 mb-1 d-block tracking-wider">Deskripsi Fisik</label>
                                <p class="text-dark small mb-0"><?= Html::encode($model->deskripsi_fisik ?: '-') ?></p>
                            </div>
                            <div>
                                <label class="text-muted small text-uppercase font-weight-700 mb-1 d-block tracking-wider">Bahasa</label>
                                <p class="text-dark small mb-0"><?= Html::encode($model->bahasa ?: '-') ?></p>
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
.space-y-5 > * + * { margin-top: 2.5rem !important; }
.text-dark-blue { color: #1e293b; }
.font-weight-600 { font-weight: 600; }
.font-weight-700 { font-weight: 700; }
.tracking-wide { letter-spacing: 0.025em; }
.tracking-wider { letter-spacing: 0.05em; }
.line-height-lg { line-height: 1.7; }
.italic { font-style: italic; }
.rounded-4 { border-radius: 1rem !important; }
</style>
