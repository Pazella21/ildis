<?php

use kartik\grid\GridView;
use yii\helpers\Html;
use yii\widgets\Pjax;

/* @var $searchModel backend\models\SurveyKepuasanSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $aggregate array */

$this->title = 'Survey Kepuasan Masyarakat';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="alert alert-info">
    Total responden: <strong><?= number_format($aggregate['total'] ?? 0) ?></strong> |
    Rata-rata: <strong><?= Html::encode((string) ($aggregate['average'] ?? 0)) ?>/5</strong> |
    Indeks IKM: <strong><?= Html::encode((string) ($aggregate['ikmIndex'] ?? 0)) ?></strong>
</div>

<?php Pjax::begin(); ?>
<?= GridView::widget([
    'panel' => [
        'type' => GridView::TYPE_PRIMARY,
        'heading' => '<h3 class="panel-title"><i class="glyphicon glyphicon-list-alt"></i> Data Survey IKM</h3>',
    ],
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    'columns' => [
        ['class' => 'yii\grid\SerialColumn'],
        'id',
        'tingkat_kepuasan',
        'isi:ntext',
        'ip_address',
        'created_at',
        [
            'class' => 'yii\grid\ActionColumn',
            'template' => '{view} {delete}',
        ],
    ],
]); ?>
<?php Pjax::end(); ?>
