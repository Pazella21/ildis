<?php

use yii\helpers\Html;
use yii\helpers\Url;
use common\components\LazyImage;
use frontend\models\Eksemplar;
use frontend\models\DataLampiran;
use frontend\models\DataPengarang;
use frontend\models\DataSubyek;

/* @var $this yii\web\View */
/* @var $model frontend\models\Dokumen */

$this->title = $model->judul;

// --- SEO Metatags & Open Graph ---
$baseUrl = Url::to(['/'], true);
$currentUrl = Url::to(['/dokumen/view', 'id' => $model->id, 'slug' => $model->getUrlSlug()], true);
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

<div class="dokumen-view-wrapper">
    <div class="container">
        <div class="row">
            <!-- Main Content Area -->
            <div class="col-lg-8">
                <div class="dokumen-view-card">
                    <!-- Heading Section -->
                    <div class="mb-5">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="dokumen-view-type-badge">
                                <?= Html::encode($model->jenis_peraturan ?: 'Monografi') ?>
                            </span>
                        </div>
                        <h1 class="dokumen-view-title">
                            <?= Html::encode($model->judul) ?>
                        </h1>
                    </div>

                    <!-- Metadata Grid -->
                    <div class="row g-4 mb-5">
                        <div class="col-sm-6">
                            <label class="dokumen-view-label">ISBN</label>
                            <div class="dokumen-view-value"><?= Html::encode($model->isbn ?: '-') ?></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="dokumen-view-label">Tahun Terbit</label>
                            <div class="dokumen-view-value"><?= Html::encode($model->tahun_terbit ?: '-') ?></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="dokumen-view-label">Penerbit</label>
                            <div class="dokumen-view-value"><?= Html::encode($model->penerbit ?: '-') ?></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="dokumen-view-label">Tempat Terbit</label>
                            <div class="dokumen-view-value"><?= Html::encode($model->tempat_terbit ?: '-') ?></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="dokumen-view-label">Klasifikasi</label>
                            <div class="dokumen-view-value"><?= Html::encode($model->klasifikasi ?: '-') ?></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="dokumen-view-label">Nomor Panggil</label>
                            <div class="dokumen-view-value"><?= Html::encode($model->nomor_panggil ?: '-') ?></div>
                        </div>
                    </div>

                    <?php if (!empty($model->sumber)): ?>
                        <div class="mb-5">
                            <label class="dokumen-view-label">Anotasi</label>
                            <div class="dokumen-view-annotation text-justify">
                                <?= Html::encode($model->sumber) ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <hr class="dokumen-view-divider">

                    <!-- Relation Sections -->
                    <div class="space-y-5">
                        <!-- Eksemplar -->
                        <div class="mb-5">
                            <h3 class="dokumen-view-section-title">
                                <i class="ti-layers" aria-hidden="true"></i> Data Eksemplar
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
                                <div class="dokumen-view-empty">
                                    Data eksemplar tidak tersedia
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Technical Information -->
                        <div class="row g-4">
                            <div class="col-md-6">
                                <h4 class="dokumen-view-subsection-title">T.E.U Badan</h4>
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
                                <h4 class="dokumen-view-subsection-title">Subjek</h4>
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
                        <div class="dokumen-view-sidebar-panel p-2 mb-4">
                            <?= LazyImage::img('@web/common/dokumen/' . $model->gambar_sampul, [
                                'class' => 'img-fluid rounded-4 w-100',
                                'style' => 'object-fit: cover; max-height: 480px;',
                                'alt' => $model->judul,
                            ]) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Attachments Card -->
                    <div class="dokumen-view-sidebar-panel">
                        <h4 class="dokumen-view-sidebar-title">
                            <i class="ti-download" aria-hidden="true"></i> Lampiran & Berkas
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
                                <?= Html::a(
                                    '<i class="ti-book" style="font-size: 1.1rem; margin-right: 8px;"></i> <span style="font-weight: 500; font-size: 15px;">Lihat Abstrak</span>',
                                    ['/common/dokumen/' . $model->abstrak],
                                    [
                                        'class' => 'btn w-100 d-flex align-items-center justify-content-center mb-2',
                                        'style' => 'background-color: #1e264c; color: #ffffff; border-radius: 12px; padding: 12px 20px; border: none; box-shadow: none; transition: background-color 0.2s ease;',
                                        'onmouseover' => 'this.style.backgroundColor="#161d3a"',
                                        'onmouseout' => 'this.style.backgroundColor="#1e264c"',
                                        'target' => '_blank',
                                        'title' => 'Lihat Abstrak'
                                    ]
                                ) ?>
                            <?php endif; ?>

                            <?php if (empty($lampiran) && empty($model->abstrak)): ?>
                                <div class="dokumen-view-empty">
                                    Tidak ada berkas digital
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Additional Info Car -->
                    <div class="dokumen-view-sidebar-panel">
                        <h4 class="dokumen-view-sidebar-title">Informasi Tambahan</h4>
                        <div class="space-y-4">
                            <div>
                                <label class="dokumen-view-label">Deskripsi Fisik</label>
                                <p class="dokumen-view-value mb-0"><?= Html::encode($model->deskripsi_fisik ?: '-') ?></p>
                            </div>
                            <div>
                                <label class="dokumen-view-label">Bahasa</label>
                                <p class="dokumen-view-value mb-0"><?= Html::encode($model->bahasa ?: '-') ?></p>
                            </div>
                            <div>
                                <label class="dokumen-view-label">Bidang Hukum</label>
                                <p class="dokumen-view-value mb-0"><?= Html::encode($model->bidang_hukum ?: '-') ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
