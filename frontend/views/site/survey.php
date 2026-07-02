<?php

use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\ActiveForm;
use common\models\MasterKepuasan;

/* @var $model frontend\models\SurveyKepuasanForm */
/* @var $aggregate array */
/* @var $options array<int, string> */

$this->title = 'Survey Kepuasan Masyarakat (IKM)';
$distribution = $aggregate['distribution'] ?? [];
$chartLabels = [];
$chartData = [];
foreach ($options as $id => $label) {
    $chartLabels[] = $label;
    $chartData[] = (int) ($distribution[$id] ?? 0);
}
?>

<div class="page-with-fixed-header" style="background-color: #f8fafc;">
<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-6">
            <h1 class="h3 mb-3"><?= Html::encode($this->title) ?></h1>
            <p class="text-muted">Mohon mengisi survey Indeks Kepuasan Masyarakat (IKM) sebagai masukan untuk penyajian website yang lebih baik.</p>

            <?php $form = ActiveForm::begin(['action' => ['site/survey-submit'], 'method' => 'post']); ?>
                <?= $form->field($model, 'tingkat_kepuasan')->radioList($options, [
                    'item' => function ($index, $label, $name, $checked, $value) {
                        return '<div class="form-check mb-2">' .
                            Html::radio($name, $checked, ['value' => $value, 'class' => 'form-check-input', 'id' => 'ikm-' . $value]) .
                            Html::label(Html::encode($label), 'ikm-' . $value, ['class' => 'form-check-label']) .
                            '</div>';
                    },
                ]) ?>
                <?= $form->field($model, 'isi')->textarea(['rows' => 4, 'placeholder' => 'Masukan Anda (opsional)']) ?>
                <div class="form-group">
                    <?= Html::submitButton('Kirim Survey', ['class' => 'btn btn-primary']) ?>
                </div>
            <?php ActiveForm::end(); ?>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">Hasil Agregat IKM</h2>
                    <ul class="list-unstyled mb-4">
                        <li>Total responden: <strong><?= number_format($aggregate['total'] ?? 0) ?></strong></li>
                        <li>Rata-rata kepuasan: <strong><?= Html::encode((string) ($aggregate['average'] ?? 0)) ?> / 5</strong></li>
                        <li>Indeks IKM: <strong><?= Html::encode((string) ($aggregate['ikmIndex'] ?? 0)) ?></strong></li>
                    </ul>
                    <canvas id="survey-distribution-chart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<?php
$this->registerJsFile('https://cdn.jsdelivr.net/npm/chart.js', ['depends' => [\frontend\assets\AppAsset::class]]);
$chartJson = Json::encode(['labels' => $chartLabels, 'data' => $chartData]);
$this->registerJs(<<<JS
(function () {
    const payload = {$chartJson};
    const el = document.getElementById('survey-distribution-chart');
    if (!el) return;
    new Chart(el, {
        type: 'bar',
        data: {
            labels: payload.labels,
            datasets: [{
                label: 'Responden',
                data: payload.data,
                backgroundColor: '#1e264c'
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
})();
JS);
?>
