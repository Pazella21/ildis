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
$currentUrl = Url::to(['/dokumen/view', 'id' => $model->id, 'slug' => $model->getUrlSlug()], true);
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

<div class="dokumen-view-wrapper">
    <div class="container">
        <div class="row">
            <!-- Main Content Area -->
            <div class="col-lg-8">
                <div class="dokumen-view-card">
                    <!-- Heading Section -->
                    <div class="mb-5">
                        <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
                            <span class="dokumen-view-type-badge">
                                <?= Html::encode($model->jenis_peraturan) ?>
                            </span>
                            <?php if ($model->status): ?>
                                <span class="dokumen-view-type-badge <?= stripos($model->status, 'Berlaku') !== false && stripos($model->status, 'Tidak') === false ? 'dokumen-view-status-badge--active' : 'dokumen-view-status-badge--inactive' ?>">
                                    <?= Html::encode($model->status) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <h1 class="dokumen-view-title">
                            <?= Html::encode($model->judul) ?>
                        </h1>
                        <?= $this->render('_document-stats', ['documentStats' => $documentStats]) ?>
                    </div>

                    <!-- Metadata Grid -->
                    <div class="row g-4 mb-5">
                        <div class="col-sm-6">
                            <label class="dokumen-view-label">Nomor Peraturan</label>
                            <div class="dokumen-view-value"><?= Html::encode($model->nomor_peraturan ?: '-') ?></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="dokumen-view-label">Tahun Terbit</label>
                            <div class="dokumen-view-value"><?= Html::encode($model->tahun_terbit ?: '-') ?></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="dokumen-view-label">Tanggal Penetapan</label>
                            <div class="dokumen-view-value"><?= $model->tanggal_penetapan ? \common\components\DateHelper::formatIndonesian($model->tanggal_penetapan) : '-' ?></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="dokumen-view-label">Tanggal Pengundangan</label>
                            <div class="dokumen-view-value"><?= Html::encode($model->tanggal_pengundangan ?: '-') ?></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="dokumen-view-label">Tempat Terbit</label>
                            <div class="dokumen-view-value"><?= Html::encode($model->tempat_terbit ?: '-') ?></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="dokumen-view-label">Bahasa</label>
                            <div class="dokumen-view-value"><?= Html::encode($model->bahasa ?: '-') ?></div>
                        </div>
                    </div>

                    <hr class="dokumen-view-divider">

                    <!-- Relation Sections -->
                    <div class="space-y-5">
                        <!-- Peraturan Terkait -->
                        <div class="mb-5">
                            <h3 class="dokumen-view-section-title">
                                <i class="ti-link" aria-hidden="true"></i> Peraturan Terkait
                            </h3>
                            <?php
                            $peraturanterkait = PeraturanTerkait::find()->where(['id_dokumen' => $model->id])->all();
                            if (!empty($peraturanterkait)): ?>
                                <ul class="list-group list-group-flush border-top border-bottom">
                                    <?php foreach ($peraturanterkait as $data): ?>
                                        <li class="list-group-item py-3 px-0 d-flex gap-3 align-items-baseline" style="background: transparent;">
                                            <i class="ti-arrow-right text-muted small"></i>
                                            <?= Html::a($data->getJudul($data['peraturan_terkait']), ['/dokumen/view', 'id' => $data->peraturan_terkait], [
                                                'class' => 'dokumen-view-link',
                                                'title' => 'Lihat Detail'
                                            ]) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div class="dokumen-view-empty">
                                    Data tidak tersedia
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Keterangan Status -->
                        <div class="mb-5">
                            <h3 class="dokumen-view-section-title">
                                <i class="ti-info-alt" aria-hidden="true"></i> Keterangan Status
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
                                                <?= Html::a($data->getJudul($data['id_dokumen_target']), ['/dokumen/view', 'id' => $data['id_dokumen_target']], ['class' => 'dokumen-view-link']) ?>
                                                <span class="text-muted small"><?= Html::encode($catatan) ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="dokumen-view-empty">
                                    Data belum tersedia
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
                                    <span class="text-muted italic small">Tidak ada data</span>
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
                                        ['/dokumen/download', 'id' => $data->dokumen_lampiran, 'docId' => $model->id],
                                        [
                                            'class' => 'lampiran-link btn btn-outline-primary font-weight-600 rounded-3 py-2 px-3',
                                            'title' => Html::encode($fileName),
                                        ]
                                    ) ?>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <?php if (!empty($model->abstrak)): ?>
                                <?= Html::a(
                                    '<i class="ti-book" style="font-size: 1.1rem; margin-right: 8px;"></i> <span style="font-weight: 500; font-size: 15px;">Lihat Abstrak</span>',
                                    ['/dokumen/download', 'id' => $model->abstrak, 'docId' => $model->id],
                                    [
                                        'class' => 'btn w-100 d-flex align-items-center justify-content-center mb-2',
                                        'style' => 'background-color: #1e264c; color: #ffffff; border-radius: 12px; padding: 12px 20px; border: none; box-shadow: none; transition: background-color 0.2s ease;',
                                        'onmouseover' => 'this.style.backgroundColor="#161d3a"',
                                        'onmouseout' => 'this.style.backgroundColor="#1e264c"',
                                        'title' => 'Lihat Abstrak'
                                    ]
                                ) ?>
                            <?php endif; ?>

                            <?php if (empty($lampiran) && empty($model->abstrak)): ?>
                                <div class="dokumen-view-empty">
                                    Tidak ada berkas tersedia
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Source/Location Card -->
                    <div class="dokumen-view-sidebar-panel">
                        <h4 class="dokumen-view-sidebar-title">Informasi Tambahan</h4>
                        <div class="space-y-4">
                            <div>
                                <label class="dokumen-view-label">Sumber</label>
                                <p class="dokumen-view-value mb-0"><?= Html::encode($model->sumber ?: '-') ?></p>
                            </div>
                            <div>
                                <label class="dokumen-view-label">Pemrakarsa</label>
                                <p class="dokumen-view-value mb-0"><?= Html::encode($model->pemrakarsa ?: '-') ?></p>
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
