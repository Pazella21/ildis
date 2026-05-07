<?php
use yii\helpers\Html;
?>

<div class='card'>
    <div class='card-header'>
        <h3 class='card-title'>Tren Kunjungan (30 Hari)</h3>
    </div>
    <div class='card-body'>
        <canvas id='visitorTrendChart'></canvas>
    </div>
</div>
<script src='https://cdn.jsdelivr.net/npm/chart.js'></script>
<script>
fetch('/visitor-report/ajax-chart')
    .then(res => res.json())
    .then(data => {
        new Chart(document.getElementById('visitorTrendChart'), {
            type: 'line',
            data: data,
            options: { responsive: true }
        });
    });
</script>
