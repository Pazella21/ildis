<?php

use yii\helpers\Html;
use backend\models\FrontendConfig;
use common\models\VisitorStats;

// Fetch visitor stats
$today = date('Y-m-d');
$thisWeek = date('Y-m-d', strtotime('monday this week'));
$thisMonth = date('Y-m-01');
$thisYear = date('Y-01-01');

$todayStat = VisitorStats::find()->where(['stat_type' => VisitorStats::TYPE_DAILY, 'stat_date' => $today, 'document_id' => null])->one();
$weekStat = VisitorStats::find()->where(['stat_type' => VisitorStats::TYPE_WEEKLY, 'stat_date' => $thisWeek, 'document_id' => null])->one();
$monthStat = VisitorStats::find()->where(['stat_type' => VisitorStats::TYPE_MONTHLY, 'stat_date' => $thisMonth, 'document_id' => null])->one();
$yearStat = VisitorStats::find()->where(['stat_type' => VisitorStats::TYPE_YEARLY, 'stat_date' => $thisYear, 'document_id' => null])->one();

$todayVisits = $todayStat ? (int)$todayStat->unique_visits : 0;
$weekVisits = $weekStat ? (int)$weekStat->unique_visits : 0;
$monthVisits = $monthStat ? (int)$monthStat->unique_visits : 0;
$yearVisits = $yearStat ? (int)$yearStat->unique_visits : 0;

$logo = FrontendConfig::findOne(3);
$fb = FrontendConfig::findOne(13);
$yt = FrontendConfig::findOne(14);
$ig = FrontendConfig::findOne(15);
$instansi = FrontendConfig::findOne(2);
$deskripsi = FrontendConfig::findOne(4);
$alamat = FrontendConfig::findOne(5);
$nomor = FrontendConfig::findOne(6);
$email = FrontendConfig::findOne(7);

// Clean up HTML from configs
$cleanInstansi = $instansi ? trim(strip_tags($instansi->isi_konfig)) : 'Badan Pembinaan Hukum Nasional - Kementerian Hukum R.I';
$cleanAlamat = $alamat ? trim(strip_tags($alamat->isi_konfig)) : 'Jl. Mayjend Sutoyo, Cililitan, Jakarta Timur';
$cleanNomor = $nomor ? trim(strip_tags($nomor->isi_konfig)) : 'Telp +62-21 8091909 (hunting) Faks +62-21 8011753';
$cleanEmail = $email ? trim(strip_tags($email->isi_konfig)) : 'humas@bphn.go.id · bphn.humaskerjasamantu@gmail.com';

?>

<!-- ======= Footer ======= -->
<footer class="footer bphn-footer" style="background-color: #1a2752;" role="contentinfo">
  <div class="container py-5 mt-3 mb-1">
    <div class="row pt-2 pb-4">
      <!-- Info Address -->
      <div class="col-lg-5 col-md-12 mb-4 mb-lg-0 pe-lg-5">
        <h6 class="fw-bold mb-4" style="color: #ffffff; letter-spacing: 0.5px;">
          JARINGAN DOKUMENTASI DAN INFORMASI <span style="color: #ffc107;">HUKUM NASIONAL</span>
        </h6>
        <p class="mb-4" style="line-height: 1.6;">
          <?= Html::encode($cleanInstansi) ?>
        </p>
        <p class="mb-3" style="line-height: 1.6;">
          <?= Html::encode($cleanAlamat) ?>
        </p>
        <p class="mb-3" style="line-height: 1.6;">
          <?= Html::encode(str_replace('Faks', ' Faks', $cleanNomor)) ?>
        </p>
        <p class="mb-0" style="line-height: 1.6;">
          Email <?= Html::encode($cleanEmail) ?>
        </p>
      </div>

      <!-- Layanan -->
      <div class="col-lg-3 col-md-6 mb-4 mb-md-0 ps-lg-5">
        <h6 class="fw-bold mb-4 text-white" style="letter-spacing: 0.5px; font-size: 0.9rem;">LAYANAN</h6>
        <ul class="list-unstyled mb-0" style="line-height: 2.2;">
          <li><a href="#" class="footer-link">Pengaduan</a></li>
          <li><a href="#" class="footer-link">Penilaian</a></li>
        </ul>
      </div>

      <!-- Tentang -->
      <div class="col-lg-4 col-md-6 mb-4 mb-md-0">
        <h6 class="fw-bold mb-4 text-white" style="letter-spacing: 0.5px; font-size: 0.9rem;">TENTANG</h6>
        <ul class="list-unstyled mb-0" style="line-height: 2.2;">
          <li><a href="/" class="footer-link">Beranda</a></li>
          <li><a href="#" class="footer-link">FAQ</a></li>
          <li><a href="#" class="footer-link">Kontak Kami</a></li>
        </ul>
      </div>
    </div>

    <!-- Divider -->
    <hr style="border-color: rgba(255, 255, 255, 0.1); margin: 0 0 25px 0;">

    <!-- Bottom Section -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center" style="font-size: 0.75rem;">
      <div class="d-flex flex-wrap justify-content-center justify-content-lg-start align-items-center gap-3 mb-3 mb-lg-0" style="color: #64748b;">
        <span class="text-white">&copy; 2026 BPHN</span>
        <span style="color: #475569; font-weight: bold;">&middot;</span>
        <span style="color: #728aad; font-size: 0.75rem;">
          <i class="bi bi-people me-1"></i>
          <span title="Kunjungan hari ini">Hari: <strong style="color: #a5b4cc;"><?= $todayVisits ?></strong></span>
          <span style="color: #475569;">&middot;</span>
          <span title="Kunjungan minggu ini">Minggu: <strong style="color: #a5b4cc;"><?= $weekVisits ?></strong></span>
          <span style="color: #475569;">&middot;</span>
          <span title="Kunjungan bulan ini">Bulan: <strong style="color: #a5b4cc;"><?= $monthVisits ?></strong></span>
          <span style="color: #475569;">&middot;</span>
          <span title="Kunjungan tahun ini">Tahun: <strong style="color: #a5b4cc;"><?= $yearVisits ?></strong></span>
        </span>
        <span style="color: #475569; font-weight: bold;">&middot;</span>
        <a href="#" class="footer-link-muted">Prasyarat Penggunaan</a>
        <span style="color: #475569; font-weight: bold;">&middot;</span>
        <a href="#" class="footer-link-muted">Kebijakan Privasi</a>
        <span style="color: #475569; font-weight: bold;">&middot;</span>
        <a href="#" class="footer-link-muted">Status Sistem</a>
        <span style="color: #475569; font-weight: bold;">&middot;</span>
        <a href="#" class="footer-link-muted">Pengaturan Cookie</a>
      </div>

      <div class="d-flex align-items-center gap-4">
        <div class="d-flex align-items-center gap-2 text-white" style="cursor: pointer; font-size: 0.85rem;">
          <i class="bi bi-globe"></i>
          <span>Indonesia</span>
        </div>
        
        <div class="d-flex border-start ps-4 align-items-center gap-4" style="border-color: rgba(255, 255, 255, 0.1) !important; font-size: 1.15rem;">
          <a href="<?= isset($fb->isi_konfig) ? Html::encode(strip_tags($fb->isi_konfig)) : '#' ?>" class="footer-social"><i class="bi bi-facebook"></i></a>
          <a href="<?= isset($ig->isi_konfig) ? Html::encode(strip_tags($ig->isi_konfig)) : '#' ?>" class="footer-social"><i class="bi bi-instagram"></i></a>
          <a href="#" class="footer-social">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-twitter-x" viewBox="0 0 16 16">
              <path d="M12.6.75h2.454l-5.36 6.142L16 15.25h-4.937l-3.867-5.07-4.425 5.07H.316l5.733-6.57L0 .75h5.063l3.495 4.633L12.601.75Zm-.86 13.028h1.36L4.323 2.145H2.865l8.875 11.633Z"/>
            </svg>
          </a>
          <a href="<?= isset($yt->isi_konfig) ? Html::encode(strip_tags($yt->isi_konfig)) : '#' ?>" class="footer-social"><i class="bi bi-youtube"></i></a>
        </div>
      </div>
    </div>
  </div>
  
  <style>
    .bphn-footer {
      color: #a5b4cc !important;
      font-size: 0.85rem;
    }
    .bphn-footer p, .bphn-footer span, .bphn-footer li {
      color: #a5b4cc !important;
    }
    .bphn-footer .text-white, .bphn-footer .text-white span {
      color: #ffffff !important;
    }
    .footer-link {
      color: #a5b4cc !important;
      text-decoration: none;
      transition: color 0.3s ease;
    }
    .footer-link:hover {
      color: #ffffff !important;
    }
    .footer-link-muted {
      color: #728aad !important;
      text-decoration: none;
      transition: color 0.3s ease;
    }
    .footer-link-muted:hover {
      color: #ffffff !important;
    }
    .footer-social {
      color: #a5b4cc !important;
      text-decoration: none;
      transition: color 0.3s ease;
    }
    .footer-social:hover {
      color: #ffffff !important;
    }
    .footer-social svg {
      vertical-align: middle;
      transform: translateY(-2px);
    }
  </style>
</footer>