<?php
use yii\helpers\Html;

$this->title = 'Statistik Pengunjung';
$this->params['breadcrumbs'][] = $this->title;

$cardLabels = [
    'daily' => 'Hari Ini',
    'weekly' => 'Minggu Ini',
    'monthly' => 'Bulan Ini',
    'yearly' => 'Tahun Ini',
    'all_time' => 'Semua Waktu',
];
?>

<div class='visitor-report-index'>
    <h1>Statistik Pengunjung</h1>

    <div class='row'>
        <?php foreach ($cardLabels as $key => $label): ?>
        <div class='col-md-4'>
            <div class='card'>
                <div class='card-header'>
                    <h3 class='card-title'><?= Html::encode($label) ?></h3>
                </div>
                <div class='card-body'>
                    <dl class='row'>
                        <dt class='col-sm-6'>Unique Visits</dt>
                        <dd class='col-sm-6'><?= isset($cards[$key]['unique_visits']) ? Html::encode($cards[$key]['unique_visits']) : Html::encode($cards[$key]->unique_visits) ?></dd>
                        <dt class='col-sm-6'>Total Visits</dt>
                        <dd class='col-sm-6'><?= isset($cards[$key]['total_visits']) ? Html::encode($cards[$key]['total_visits']) : Html::encode($cards[$key]->total_visits) ?></dd>
                    </dl>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class='row'>
        <div class='col-md-12'>
            <div class='card'>
                <div class='card-header'>
                    <h3 class='card-title'>Bandingkan Periode</h3>
                </div>
                <div class='card-body'>
                    <table class='table table-bordered'>
                        <thead>
                            <tr>
                                <th>Periode</th>
                                <th>Saat Ini</th>
                                <th>Sebelumnya</th>
                                <th>Total Saat Ini</th>
                                <th>Total Sebelumnya</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($comparisons as $key => $item): ?>
                            <tr>
                                <td><?= Html::encode(str_replace('_', ' ', $key)) ?></td>
                                <td><?= Html::encode(isset($item['current']['unique_visits']) ? $item['current']['unique_visits'] : $item['current']->unique_visits) ?></td>
                                <td><?= Html::encode(isset($item['previous']['unique_visits']) ? $item['previous']['unique_visits'] : $item['previous']->unique_visits) ?></td>
                                <td><?= Html::encode(isset($item['current']['total_visits']) ? $item['current']['total_visits'] : $item['current']->total_visits) ?></td>
                                <td><?= Html::encode(isset($item['previous']['total_visits']) ? $item['previous']['total_visits'] : $item['previous']->total_visits) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class='row'>
        <div class='col-md-12'>
            <?= $this->render('_chart') ?>
        </div>
    </div>
</div>
