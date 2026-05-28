<?php

use yii\helpers\Html;
use backend\models\FrontendConfig;
use common\models\FooterSection;
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
$instansi = FrontendConfig::findOne(2);
$deskripsi = FrontendConfig::findOne(4);
$alamat = FrontendConfig::findOne(5);
$nomor = FrontendConfig::findOne(6);
$email = FrontendConfig::findOne(7);

$cleanInstansi = $instansi ? trim(strip_tags($instansi->isi_konfig)) : 'Badan Pembinaan Hukum Nasional - Kementerian Hukum R.I';
$cleanAlamat = $alamat ? trim(strip_tags($alamat->isi_konfig)) : 'Jl. Mayjend Sutoyo, Cililitan, Jakarta Timur';
$cleanNomor = $nomor ? trim(strip_tags($nomor->isi_konfig)) : 'Telp +62-21 8091909 (hunting) Faks +62-21 8011753';
$cleanEmail = $email ? trim(strip_tags($email->isi_konfig)) : 'humas@bphn.go.id · bphn.humaskerjasamantu@gmail.com';

$sections = FooterSection::getActiveSections();
$navSections = [];
$socialSections = [];
foreach ($sections as $section) {
    if ($section->type === FooterSection::TYPE_NAV) {
        $navSections[] = $section;
    } elseif ($section->type === FooterSection::TYPE_SOCIAL) {
        $socialSections[] = $section;
    }
}

$hasDynamicContent = !empty($navSections) || !empty($socialSections);
$socialSection = !empty($socialSections) ? $socialSections[0] : null;
$socialLinks = $socialSection ? $socialSection->activeLinks : [];

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

<?php if ($hasDynamicContent): ?>
    <?php foreach ($navSections as $section): ?>
      <?php if (!empty($section->activeLinks)): ?>
      <div class="col-lg-3 col-md-6 mb-4 mb-md-0 ps-lg-5">
        <h6 class="fw-bold mb-4 text-white" style="letter-spacing: 0.5px; font-size: 0.9rem;"><?= Html::encode($section->title) ?></h6>
        <ul class="list-unstyled mb-0" style="line-height: 2.2;">
          <?php foreach ($section->activeLinks as $link): ?>
            <li><?= Html::a(Html::encode($link->label), Html::encode($link->url), array_filter([
                'class' => 'footer-link',
                'target' => $link->open_in_new_tab ? '_blank' : null,
                'rel' => $link->open_in_new_tab ? 'noopener noreferrer' : null,
            ])) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>
    <?php endforeach; ?>
<?php else: ?>
      <!-- Fallback: hardcoded content -->
      <div class="col-lg-3 col-md-6 mb-4 mb-md-0 ps-lg-5">
        <h6 class="fw-bold mb-4 text-white" style="letter-spacing: 0.5px; font-size: 0.9rem;">LAYANAN</h6>
        <ul class="list-unstyled mb-0" style="line-height: 2.2;">
          <li><a href="#" class="footer-link">Pengaduan</a></li>
          <li><a href="#" class="footer-link">Penilaian</a></li>
        </ul>
      </div>

      <div class="col-lg-4 col-md-6 mb-4 mb-md-0">
        <h6 class="fw-bold mb-4 text-white" style="letter-spacing: 0.5px; font-size: 0.9rem;">TENTANG</h6>
        <ul class="list-unstyled mb-0" style="line-height: 2.2;">
          <li><a href="/" class="footer-link">Beranda</a></li>
          <li><a href="#" class="footer-link">FAQ</a></li>
          <li><a href="#" class="footer-link">Kontak Kami</a></li>
        </ul>
      </div>
<?php endif; ?>

    </div>

    <!-- Divider -->
    <hr style="border-color: rgba(255, 255, 255, 0.1); margin: 0 0 25px 0;">

    <!-- Visitor Analytics -->
    <div class="footer-analytics">
      <div class="container">
        <div class="analytics-strip">
          <span class="analytics-title"><i class="bi bi-people"></i> Pengunjung</span>
          <span class="analytics-stat">
            <span class="analytics-num"><?= $todayVisits ?></span>
            <span class="analytics-period">hari ini</span>
          </span>
          <span class="analytics-dot"></span>
          <span class="analytics-stat">
            <span class="analytics-num"><?= $weekVisits ?></span>
            <span class="analytics-period">minggu ini</span>
          </span>
          <span class="analytics-dot"></span>
          <span class="analytics-stat">
            <span class="analytics-num"><?= $monthVisits ?></span>
            <span class="analytics-period">bulan ini</span>
          </span>
          <span class="analytics-dot"></span>
          <span class="analytics-stat">
            <span class="analytics-num"><?= $yearVisits ?></span>
            <span class="analytics-period">tahun ini</span>
          </span>
        </div>
      </div>
    </div>

    <!-- Bottom Section -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center" style="font-size: 0.75rem;">
      <div class="d-flex flex-wrap justify-content-center justify-content-lg-start align-items-center gap-3 mb-3 mb-lg-0" style="color: #64748b;">
        <span class="text-white">&copy; <?= date('Y') ?> <?= Html::encode($cleanInstansi) ?> powered by <a href="https://ildis.bphn.go.id" target="_blank" style="color: #ffc107;">ILDIS</a></span>
      </div>

      <div class="d-flex align-items-center gap-4">
        <div class="d-flex align-items-center gap-2 text-white" style="cursor: pointer; font-size: 0.85rem;">
          <i class="bi bi-globe"></i>
          <span>Indonesia</span>
        </div>
        
        <div class="d-flex border-start ps-4 align-items-center gap-4" style="border-color: rgba(255, 255, 255, 0.1) !important; font-size: 1.15rem;">
<?php if (!empty($socialLinks)): ?>
    <?php foreach ($socialLinks as $link): ?>
          <?php
          $linkOptions = ['class' => 'footer-social'];
          if ($link->open_in_new_tab) {
              $linkOptions['target'] = '_blank';
              $linkOptions['rel'] = 'noopener noreferrer';
          }
          ?>
          <?php if ($link->icon_class === 'bi bi-twitter-x'): ?>
          <a href="<?= Html::encode($link->url) ?>" <?= Html::renderTagAttributes($linkOptions) ?>>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-twitter-x" viewBox="0 0 16 16">
              <path d="M12.6.75h2.454l-5.36 6.142L16 15.25h-4.937l-3.867-5.07-4.425 5.07H.316l5.733-6.57L0 .75h5.063l3.495 4.633L12.601.75Zm-.86 13.028h1.36L4.323 2.145H2.865l8.875 11.633Z"/>
            </svg>
          </a>
          <?php elseif ($link->icon_class): ?>
          <a href="<?= Html::encode($link->url) ?>" <?= Html::renderTagAttributes($linkOptions) ?>><i class="<?= Html::encode($link->icon_class) ?>"></i></a>
          <?php else: ?>
          <a href="<?= Html::encode($link->url) ?>" <?= Html::renderTagAttributes($linkOptions) ?>><i class="bi bi-link-45deg"></i></a>
          <?php endif; ?>
    <?php endforeach; ?>
<?php elseif ($hasDynamicContent): ?>
<?php else: ?>
          <a href="#" class="footer-social"><i class="bi bi-facebook"></i></a>
          <a href="#" class="footer-social"><i class="bi bi-instagram"></i></a>
          <a href="#" class="footer-social">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-twitter-x" viewBox="0 0 16 16">
              <path d="M12.6.75h2.454l-5.36 6.142L16 15.25h-4.937l-3.867-5.07-4.425 5.07H.316l5.733-6.57L0 .75h5.063l3.495 4.633L12.601.75Zm-.86 13.028h1.36L4.323 2.145H2.865l8.875 11.633Z"/>
            </svg>
          </a>
          <a href="#" class="footer-social"><i class="bi bi-youtube"></i></a>
<?php endif; ?>
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

    /* Visitor Analytics */
    .footer-analytics {
      border-top: 1px solid rgba(255, 255, 255, 0.06);
      padding: 12px 0;
      margin-bottom: 16px;
    }

    .analytics-strip {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0;
      flex-wrap: wrap;
    }

    .analytics-title {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      font-size: 0.65rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 1.2px;
      color: #5e7192;
      margin-right: 14px;
      white-space: nowrap;
    }

    .analytics-title i {
      font-size: 0.75rem;
      color: #ffc107;
      opacity: 0.7;
    }

    .analytics-stat {
      display: inline-flex;
      align-items: baseline;
      gap: 3px;
      white-space: nowrap;
    }

    .analytics-num {
      font-size: 0.85rem;
      font-weight: 700;
      color: #fff;
      font-variant-numeric: tabular-nums;
      letter-spacing: -0.02em;
    }

    .analytics-period {
      font-size: 0.65rem;
      color: #5e7192;
      letter-spacing: 0.3px;
    }

    .analytics-dot {
      display: inline-block;
      width: 3px;
      height: 3px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.15);
      margin: 0 10px;
      vertical-align: middle;
    }

    @media (max-width: 768px) {
      .analytics-title {
        width: 100%;
        justify-content: center;
        margin-right: 0;
        margin-bottom: 6px;
      }

      .analytics-strip {
        gap: 0;
        justify-content: center;
      }

      .analytics-dot {
        margin: 0 6px;
      }
    }
  </style>
</footer>