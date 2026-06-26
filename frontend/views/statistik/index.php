<?php

use yii\helpers\Html;
use yii\helpers\Json;

/* @var $stats array */
$this->title = 'Statistik Dokumen Hukum';

$labelsType = ['Peraturan', 'Monografi', 'Artikel', 'Putusan'];
$dataType = [
    $stats['byType']['peraturan'] ?? 0,
    $stats['byType']['monografi'] ?? 0,
    $stats['byType']['artikel'] ?? 0,
    $stats['byType']['putusan'] ?? 0,
];

$statusLabels = array_keys($stats['peraturanByStatus'] ?? []);
$statusData = array_values($stats['peraturanByStatus'] ?? []);

$jenisLabels = array_keys($stats['peraturanByJenis'] ?? []);
$jenisData = array_values($stats['peraturanByJenis'] ?? []);

$chartConfig = [
    'type' => [
        'labels' => $labelsType,
        'data' => $dataType,
    ],
    'status' => [
        'labels' => $statusLabels,
        'data' => $statusData,
    ],
    'jenis' => [
        'labels' => $jenisLabels,
        'data' => $jenisData,
    ],
];
?>

<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="h2 font-weight-bold">Statistik Dokumen Hukum</h1>
        <p class="text-muted mb-0">Ringkasan koleksi dan aktivitas pengunjung JDIH</p>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-muted small">Total Dokumen Hukum</div>
                    <div class="display-6 fw-bold text-primary"><?= number_format($stats['totalDokumen'] ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-muted small">Koleksi PUU</div>
                    <div class="display-6 fw-bold text-primary"><?= number_format($stats['totalPuu'] ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-muted small">Pengunjung Akses Dokumen</div>
                    <div class="display-6 fw-bold text-primary"><?= number_format($stats['documentVisitors'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">Dokumen per Jenis Koleksi</h2>
                    <canvas id="chart-type" height="220"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">Status Keberlakuan Peraturan</h2>
                    <canvas id="chart-status" height="220"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h5 mb-3">Peraturan per Jenis (Top 15)</h2>
                    <canvas id="chart-jenis" height="120"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-light border mt-4 mb-0">
        Total pengunjung situs (unik): <strong><?= number_format($stats['siteVisitors'] ?? 0) ?></strong>
    </div>
</div>

<?php
$this->registerJsFile('https://cdn.jsdelivr.net/npm/chart.js', ['depends' => [\frontend\assets\AppAsset::class]]);
$chartJson = Json::encode($chartConfig);
$this->registerJs(<<<JS
(function () {
    const config = {$chartJson};
    const palette = ['#1e264c', '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'];

    function barChart(id, labels, data) {
        const el = document.getElementById(id);
        if (!el || !labels.length) return;
        new Chart(el, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah',
                    data: data,
                    backgroundColor: palette
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }

    barChart('chart-type', config.type.labels, config.type.data);
    barChart('chart-status', config.status.labels, config.status.data);
    barChart('chart-jenis', config.jenis.labels, config.jenis.data);
})();
JS);
?>
