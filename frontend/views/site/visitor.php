<?php

use yii\helpers\Html;

$this->title = 'Statistik Pengunjung ILDIS';
$this->registerMetaTag(['name' => 'description', 'content' => 'Statistik pengunjung ILDIS - Indonesian Law Documentation Information System.']);
$this->registerMetaTag(['name' => 'robots', 'content' => 'index, follow']);

$s = $stats;

function formatNumber($num)
{
    if ($num >= 1000000) {
        return number_format($num / 1000000, 1) . 'M';
    }
    if ($num >= 1000) {
        return number_format($num / 1000, 1) . 'K';
    }
    return number_format($num, 0, ',', '.');
}

function percentChange($current, $previous)
{
    if ($previous == 0) {
        return $current > 0 ? 100 : 0;
    }
    return round((($current - $previous) / $previous) * 100, 1);
}

function changeIndicator($change)
{
    if ($change > 0) {
        return '<span class="visitor-change visitor-change-up"><svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M6 2.5L9.5 7H2.5L6 2.5Z" fill="currentColor"/></svg>+' . $change . '%</span>';
    }
    if ($change < 0) {
        return '<span class="visitor-change visitor-change-down"><svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M6 9.5L2.5 5H9.5L6 9.5Z" fill="currentColor"/></svg>' . $change . '%</span>';
    }
    return '<span class="visitor-change visitor-change-neutral">0%</span>';
}

$cards = [
    [
        'label' => 'Hari Ini',
        'icon' => 'bi-calendar-day',
        'unique' => $s['today']['unique'],
        'total' => $s['today']['total'],
        'change' => percentChange($s['today']['unique'], $s['yesterday']['unique']),
        'prevLabel' => 'Kemarin',
        'prevUnique' => $s['yesterday']['unique'],
    ],
    [
        'label' => 'Minggu Ini',
        'icon' => 'bi-calendar-week',
        'unique' => $s['week']['unique'],
        'total' => $s['week']['total'],
        'change' => percentChange($s['week']['unique'], $s['lastWeek']['unique']),
        'prevLabel' => 'Minggu Lalu',
        'prevUnique' => $s['lastWeek']['unique'],
    ],
    [
        'label' => 'Bulan Ini',
        'icon' => 'bi-calendar-month',
        'unique' => $s['month']['unique'],
        'total' => $s['month']['total'],
        'change' => percentChange($s['month']['unique'], $s['lastMonth']['unique']),
        'prevLabel' => 'Bulan Lalu',
        'prevUnique' => $s['lastMonth']['unique'],
    ],
    [
        'label' => 'Tahun Ini',
        'icon' => 'bi-calendar-event',
        'unique' => $s['year']['unique'],
        'total' => $s['year']['total'],
        'change' => percentChange($s['year']['unique'], $s['lastYear']['unique']),
        'prevLabel' => 'Tahun Lalu',
        'prevUnique' => $s['lastYear']['unique'],
    ],
    [
        'label' => 'Semua Waktu',
        'icon' => 'bi-globe',
        'unique' => $s['allTime']['unique'],
        'total' => $s['allTime']['total'],
        'change' => null,
        'prevLabel' => null,
        'prevUnique' => null,
    ],
];
?>

<section class="visitor-stats-section">
    <div class="container">

        <div class="visitor-header">
            <div class="visitor-header-icon">
                <i class="bi bi-activity"></i>
            </div>
            <div>
                <h1 class="visitor-title">Statistik Pengunjung</h1>
                <p class="visitor-subtitle">Data kunjungan Jaringan Dokumentasi dan Informasi Hukum Nasional</p>
            </div>
        </div>

        <div class="visitor-grid">
            <?php foreach ($cards as $card): ?>
            <div class="visitor-card">
                <div class="visitor-card-top">
                    <div class="visitor-card-icon">
                        <i class="bi <?= $card['icon'] ?>"></i>
                    </div>
                    <span class="visitor-card-label"><?= Html::encode($card['label']) ?></span>
                </div>
                <div class="visitor-card-value"><?= formatNumber($card['unique']) ?></div>
                <div class="visitor-card-meta">
                    <span class="visitor-card-meta-label">Pengunjung Unik</span>
                    <?php if ($card['change'] !== null): ?>
                        <?= changeIndicator($card['change']) ?>
                    <?php endif; ?>
                </div>
                <div class="visitor-card-divider"></div>
                <div class="visitor-card-row">
                    <span class="visitor-card-secondary-label">Total Kunjungan</span>
                    <span class="visitor-card-secondary-value"><?= formatNumber($card['total']) ?></span>
                </div>
                <?php if ($card['prevLabel'] !== null): ?>
                <div class="visitor-card-row visitor-card-row-muted">
                    <span><?= Html::encode($card['prevLabel']) ?></span>
                    <span><?= formatNumber($card['prevUnique']) ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="visitor-footer-note">
            <i class="bi bi-shield-check"></i>
            Data dikumpulkan secara otomatis dan diperbarui secara berkala melalui sistem pelacakan pengunjung ILDIS.
        </div>

    </div>
</section>

<style>
.visitor-stats-section {
    padding: 100px 0 60px;
    min-height: 60vh;
    background: #f0f4f8;
}

.visitor-header {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 40px;
}

.visitor-header-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    background: linear-gradient(135deg, #1a2752, #274685);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.visitor-header-icon i {
    font-size: 1.5rem;
    color: #ffc107;
}

.visitor-title {
    font-family: "Inter", sans-serif;
    font-size: 1.75rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
    letter-spacing: -0.02em;
    line-height: 1.2;
}

.visitor-subtitle {
    font-family: "Inter", sans-serif;
    font-size: 0.9rem;
    color: #64748b;
    margin: 4px 0 0;
    font-weight: 400;
}

.visitor-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.visitor-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 20px;
    border: 1px solid #e2e8f0;
    transition: box-shadow 0.2s ease, transform 0.2s ease;
}

.visitor-card:hover {
    box-shadow: 0 4px 24px rgba(15, 23, 42, 0.08);
    transform: translateY(-2px);
}

.visitor-card-top {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
}

.visitor-card-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #eef2ff;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.visitor-card-icon i {
    font-size: 0.85rem;
    color: #274685;
}

.visitor-card-label {
    font-family: "Inter", sans-serif;
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

.visitor-card-value {
    font-family: "Inter", sans-serif;
    font-size: 2rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1;
    margin-bottom: 6px;
    letter-spacing: -0.03em;
    font-variant-numeric: tabular-nums;
}

.visitor-card-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 14px;
}

.visitor-card-meta-label {
    font-family: "Inter", sans-serif;
    font-size: 0.72rem;
    color: #94a3b8;
}

.visitor-change {
    font-family: "Inter", sans-serif;
    font-size: 0.7rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 2px;
    padding: 2px 6px;
    border-radius: 4px;
    line-height: 1.4;
}

.visitor-change-up {
    background: #f0fdf4;
    color: #16a34a;
}

.visitor-change-down {
    background: #fef2f2;
    color: #dc2626;
}

.visitor-change-neutral {
    background: #f8fafc;
    color: #94a3b8;
}

.visitor-card-divider {
    height: 1px;
    background: #e2e8f0;
    margin: 10px 0;
}

.visitor-card-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-family: "Inter", sans-serif;
}

.visitor-card-secondary-label {
    font-size: 0.78rem;
    color: #64748b;
    font-weight: 500;
}

.visitor-card-secondary-value {
    font-size: 0.9rem;
    color: #334155;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
}

.visitor-card-row-muted {
    margin-top: 6px;
    font-size: 0.78rem;
    color: #94a3b8;
}

.visitor-card-row-muted span:last-child {
    font-variant-numeric: tabular-nums;
}

.visitor-footer-note {
    display: flex;
    align-items: center;
    gap: 8px;
    font-family: "Inter", sans-serif;
    font-size: 0.78rem;
    color: #94a3b8;
    padding: 14px 18px;
    background: #ffffff;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
}

.visitor-footer-note i {
    font-size: 1rem;
    color: #274685;
}

@media (max-width: 767px) {
    .visitor-stats-section {
        padding: 80px 0 40px;
    }

    .visitor-title {
        font-size: 1.35rem;
    }

    .visitor-grid {
        grid-template-columns: 1fr;
        gap: 14px;
    }

    .visitor-card-value {
        font-size: 1.6rem;
    }
}

@media (min-width: 768px) and (max-width: 1024px) {
    .visitor-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>