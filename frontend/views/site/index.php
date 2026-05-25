<?php

use yii\helpers\Html;
use yii\helpers\Url;
use frontend\models\Dokumen;
use backend\models\FrontendConfig;

/* Set meta tags */

$this->title = 'JDIH - Jaringan Dokumentasi dan Informasi Hukum';
$this->description = 'Jaringan Dokumentasi dan Informasi Hukum';
$this->keywords = ['Jaringan', 'Dokumentasi', 'Informasi', 'Hukum'];

$instansi = FrontendConfig::findOne(2);
$instansiName = $instansi ? $instansi->isi_konfig : 'JDIH';

// Get totals using the existing helper method
$totalPeraturan = Dokumen::find()->total(1);
$totalMonografi = Dokumen::find()->total(2);
$totalArtikel   = Dokumen::find()->total(3);
$totalPutusan   = Dokumen::find()->total(4);

$totalBerlaku       = Dokumen::find()->where(['status' => 'Berlaku'])->count();
$totalTidakBerlaku  = Dokumen::find()->where(['status' => 'Tidak Berlaku'])->count();

?>

<style>
    /* Custom styling for the Google-like search experience */
    .search-landing-container {
        min-height: 70vh;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        position: relative;
        padding: 80px 2rem 2rem;
        /* Added 80px top padding for navbar */
    }

    .search-landing-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(26, 39, 82, 0.85);
        /* Dark navy overlay matching theme */
        z-index: 1;
    }

    .search-landing-container>* {
        position: relative;
        z-index: 2;
    }

    .search-logo-title {
        font-size: 2.5rem;
        font-weight: 700;
        color: #ffffff;
        margin-bottom: 2rem;
        letter-spacing: -0.5px;
        text-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
    }

    .search-input-wrapper {
        position: relative;
        width: 100%;
        max-width: 650px;
        margin: 0 auto;
    }

    .search-input {
        width: 100%;
        padding: 1rem 1.5rem 1rem 3rem;
        font-size: 1.1rem;
        border: 1px solid #dfe1e5;
        border-radius: 24px;
        box-shadow: 0 1px 6px rgba(32, 33, 36, 0.1);
        transition: all 0.2s ease;
        outline: none;
    }

    .search-input:focus,
    .search-input:hover {
        box-shadow: 0 1px 6px rgba(32, 33, 36, 0.28);
        border-color: rgba(223, 225, 229, 0);
    }

    .search-icon {
        position: absolute;
        left: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: #9aa0a6;
        font-size: 1.2rem;
    }

    .search-btn {
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        border-radius: 20px;
        padding: 0.5rem 1.5rem;
    }

    .quick-links {
        margin-top: 2.5rem;
        display: flex;
        gap: 12px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .quick-chip {
        padding: 8px 18px;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 20px;
        color: #ffffff;
        font-size: 0.95rem;
        text-decoration: none;
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .quick-chip:hover {
        background: rgba(255, 255, 255, 0.2);
        color: #ffffff;
        text-decoration: none;
        border-color: rgba(255, 193, 7, 0.6);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .quick-chip .badge-count {
        background: rgba(255, 193, 7, 0.9);
        color: #1a2752;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 700;
    }

    .news-strip {
        background: #f8f9fa;
        padding: 3rem 0;
        margin-top: 4rem;
        border-top: 1px solid #eee;
    }

    /* Koleksi Kami Cards */
    .hover-card {
        transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
        border: 1px solid rgba(0, 0, 0, 0.05) !important;
    }

    .hover-card:hover {
        transform: translateY(-8px) !important;
        box-shadow: 0 15px 30px rgba(26, 39, 82, 0.1) !important;
    }

    .koleksi-cards-section .icon-box i {
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }
</style>

<div class="site-index">

    <main>
        <div class="search-landing-container" style="background: url('<?= Url::to('@web/images/hero-bg.png') ?>') no-repeat center center; background-size: cover;">

            <h1 class="search-logo-title" data-aos="fade-up">
                <?= Html::encode($instansiName) ?>
            </h1>

            <form action="<?= Url::to(['dokumen/index']) ?>" method="GET" class="w-100" data-aos="fade-up" data-aos-delay="100">
                <div class="search-input-wrapper">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" name="DokumenSearch[judul]" class="search-input" placeholder="Cari dokumen hukum, peraturan, putusan..." value="" autocomplete="off" autofocus>
                    <button type="submit" class="btn btn-primary search-btn">Cari</button>
                </div>
            </form>

            <div class="quick-links" data-aos="fade-up" data-aos-delay="200">
                <a href="<?= Url::to(['dokumen/peraturan']) ?>" class="quick-chip">
                    <i class="bi bi-file-earmark-text"></i> Peraturan
                    <span class="badge-count"><?= number_format($totalPeraturan) ?></span>
                </a>
                <a href="<?= Url::to(['dokumen/monografi']) ?>" class="quick-chip">
                    <i class="bi bi-book"></i> Monografi
                    <span class="badge-count"><?= number_format($totalMonografi) ?></span>
                </a>
                <a href="<?= Url::to(['dokumen/artikel']) ?>" class="quick-chip">
                    <i class="bi bi-journal-text"></i> Artikel Hukum
                    <span class="badge-count"><?= number_format($totalArtikel) ?></span>
                </a>
                <a href="<?= Url::to(['dokumen/putusan']) ?>" class="quick-chip">
                    <i class="bi bi-bank"></i> Putusan
                    <span class="badge-count"><?= number_format($totalPutusan) ?></span>
                </a>
            </div>
        </div>

        <!-- Koleksi Kami Cards Section -->
        <section class="koleksi-cards-section py-5" style="background-color: #f8fafc;">
            <div class="container py-4">
                <div class="row text-center mb-5">
                    <div class="col-12">
                        <h2 class="fw-bold mb-3" style="color: #1a2752; font-size: 2rem; letter-spacing: -0.5px;">Koleksi Kami</h2>
                        <p style="color: #64748b; font-size: 1.05rem;">Telusuri berbagai jenis pustaka dan dokumentasi hukum</p>
                        <div class="mx-auto mt-3" style="height: 4px; width: 60px; background-color: #ffc107; border-radius: 2px;"></div>
                    </div>
                </div>

                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4 mb-5 justify-content-center">
                            <!-- Peraturan -->
                            <div class="col">
                                <a href="<?= Url::to(['dokumen/peraturan']) ?>" class="text-decoration-none">
                                    <div class="card h-100 border-0 rounded hover-card" style="background: #ffffff; box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
                                        <div class="card-body p-4 text-center d-flex flex-column h-100">
                                            <div class="icon-box mb-4 mx-auto rounded-circle d-flex align-items-center justify-content-center" style="width: 70px; height: 70px; background-color: rgba(26, 39, 82, 0.05);">
                                                <i class="bi bi-file-earmark-text" style="font-size: 2rem; color: #1a2752;"></i>
                                            </div>
                                            <h5 class="card-title font-weight-bold text-dark mb-3">Peraturan</h5>
                                            <p class="card-text small flex-grow-1" style="color: #64748b; line-height: 1.5;">Peraturan Perundang-undangan tingkat pusat hingga daerah.</p>
                                            <div class="mt-3 pt-3 border-top border-light">
                                                <h3 class="font-weight-bold mb-0" style="color: #1a2752; font-size: 1.75rem;"><?= number_format($totalPeraturan) ?></h3>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <!-- Monografi - Highlighted -->
                            <div class="col">
                                <a href="<?= Url::to(['dokumen/monografi']) ?>" class="text-decoration-none">
                                    <div class="card h-100 border-0 rounded hover-card" style="background: #1a2752; box-shadow: 0 10px 25px rgba(26, 39, 82, 0.15); transform: translateY(-3px);">
                                        <div class="card-body p-4 text-center d-flex flex-column h-100">
                                            <div class="icon-box mb-4 mx-auto rounded-circle d-flex align-items-center justify-content-center" style="width: 70px; height: 70px; background-color: rgba(255, 193, 7, 0.1);">
                                                <i class="bi bi-book" style="font-size: 2rem; color: #ffc107;"></i>
                                            </div>
                                            <h5 class="card-title font-weight-bold text-white mb-3">Monografi Hukum</h5>
                                            <p class="card-text small flex-grow-1" style="color: #cbd5e1; line-height: 1.5;">Buku, naskah akademik, dan hasil kajian hukum.</p>
                                            <div class="mt-3 pt-3 border-top" style="border-color: rgba(255,255,255,0.1) !important;">
                                                <h3 class="font-weight-bold text-white mb-0" style="font-size: 1.75rem;"><?= number_format($totalMonografi) ?></h3>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <!-- Artikel Hukum -->
                            <div class="col">
                                <a href="<?= Url::to(['dokumen/artikel']) ?>" class="text-decoration-none">
                                    <div class="card h-100 border-0 rounded hover-card" style="background: #ffffff; box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
                                        <div class="card-body p-4 text-center d-flex flex-column h-100">
                                            <div class="icon-box mb-4 mx-auto rounded-circle d-flex align-items-center justify-content-center" style="width: 70px; height: 70px; background-color: rgba(26, 39, 82, 0.05);">
                                                <i class="bi bi-journal-text" style="font-size: 2rem; color: #1a2752;"></i>
                                            </div>
                                            <h5 class="card-title font-weight-bold text-dark mb-3">Artikel Hukum</h5>
                                            <p class="card-text small flex-grow-1" style="color: #64748b; line-height: 1.5;">Artikel dan jurnal hukum cetak maupun elektronik.</p>
                                            <div class="mt-3 pt-3 border-top border-light">
                                                <h3 class="font-weight-bold mb-0" style="color: #1a2752; font-size: 1.75rem;"><?= number_format($totalArtikel) ?></h3>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <!-- Putusan -->
                            <div class="col">
                                <a href="<?= Url::to(['dokumen/putusan']) ?>" class="text-decoration-none">
                                    <div class="card h-100 border-0 rounded hover-card" style="background: #ffffff; box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
                                        <div class="card-body p-4 text-center d-flex flex-column h-100">
                                            <div class="icon-box mb-4 mx-auto rounded-circle d-flex align-items-center justify-content-center" style="width: 70px; height: 70px; background-color: rgba(26, 39, 82, 0.05);">
                                                <i class="bi bi-bank" style="font-size: 2rem; color: #1a2752;"></i>
                                            </div>
                                            <h5 class="card-title font-weight-bold text-dark mb-3">Putusan</h5>
                                            <p class="card-text small flex-grow-1" style="color: #64748b; line-height: 1.5;">Dokumen putusan pengadilan yang bernilai yurisprudensi.</p>
                                            <div class="mt-3 pt-3 border-top border-light">
                                                <h3 class="font-weight-bold mb-0" style="color: #1a2752; font-size: 1.75rem;"><?= number_format($totalPutusan) ?></h3>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <!-- Bottom Row (Status) -->
                        <div class="row justify-content-center g-4">
                            <!-- Peraturan Berlaku -->
                            <div class="col-md-5">
                                <a href="<?= Url::to(['dokumen/berlaku']) ?>" class="text-decoration-none">
                                    <div class="card border-0 rounded hover-card h-100" style="background: #ffffff; box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
                                        <div class="card-body p-4 d-flex align-items-center">
                                            <div class="icon-box rounded-circle d-flex align-items-center justify-content-center mr-4" style="width: 60px; height: 60px; background-color: rgba(16, 185, 129, 0.1); flex-shrink: 0;">
                                                <i class="bi bi-check2-circle" style="font-size: 1.8rem; color: #10b981;"></i>
                                            </div>
                                            <div class="flex-grow-1 text-left">
                                                <h6 class="font-weight-bold text-dark mb-1">Peraturan Berlaku</h6>
                                                <p class="mb-0 small" style="color: #64748b;">Kumpulan peraturan yang masih aktif.</p>
                                            </div>
                                            <div class="text-right pl-3 border-left ml-3">
                                                <h4 class="font-weight-bold mb-0" style="color: #10b981;"><?= number_format($totalBerlaku) ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <!-- Peraturan Tidak Berlaku -->
                            <div class="col-md-5">
                                <a href="<?= Url::to(['dokumen/tberlaku']) ?>" class="text-decoration-none">
                                    <div class="card border-0 rounded hover-card h-100" style="background: #ffffff; box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
                                        <div class="card-body p-4 d-flex align-items-center">
                                            <div class="icon-box rounded-circle d-flex align-items-center justify-content-center mr-4" style="width: 60px; height: 60px; background-color: rgba(239, 68, 68, 0.1); flex-shrink: 0;">
                                                <i class="bi bi-x-circle" style="font-size: 1.8rem; color: #ef4444;"></i>
                                            </div>
                                            <div class="flex-grow-1 text-left">
                                                <h6 class="font-weight-bold text-dark mb-1">Tidak Berlaku</h6>
                                                <p class="mb-0 small" style="color: #64748b;">Kumpulan peraturan yang telah dicabut.</p>
                                            </div>
                                            <div class="text-right pl-3 border-left ml-3">
                                                <h4 class="font-weight-bold mb-0" style="color: #ef4444;"><?= number_format($totalTidakBerlaku) ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- News Strip (Only on landing) -->
        <?php if (!empty($berita)): ?>
            <section class="news-strip">
                <div class="container">
                    <div class="row mb-4">
                        <div class="col-12 text-center">
                            <h4 class="font-weight-bold">Berita Terbaru</h4>
                        </div>
                    </div>
                    <div class="row">
                        <?php foreach ($berita as $data): ?>
                            <div class="col-lg-4 mb-4">
                                <div class="card h-100 border-0 shadow-sm rounded overflow-hidden">
                                    <?= Html::a(Html::img('@web/common/dokumen/' . $data->image, ['class' => 'card-img-top', 'style' => 'height: 200px; object-fit: cover;']), ['berita/view', 'id' => $data->id]); ?>
                                    <div class="card-body p-4">
                                        <h5 class="card-title font-weight-bold mb-3">
                                            <?= Html::a(Html::encode($data->judul), ['berita/view', 'id' => $data->id], ['class' => 'text-dark text-decoration-none']) ?>
                                        </h5>
                                        <p class="card-text text-muted">
                                            <?= implode(" ", array_slice(explode(" ", strip_tags($data->isi)), 0, 15)) . '...' ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-3">
                        <?= Html::a('Lihat Semua Berita <i class="bi bi-arrow-right"></i>', ['berita/index'], ['class' => 'btn btn-outline-primary rounded-pill px-4']); ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>

</div>