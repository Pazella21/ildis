<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $model common\models\SurveyKepuasan */

$this->title = 'Survey #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Survey Kepuasan Masyarakat', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<?= DetailView::widget([
    'model' => $model,
    'attributes' => [
        'id',
        'tingkat_kepuasan',
        'isi:ntext',
        'ip_address',
        'created_at',
        'updated_at',
    ],
]) ?>

<p>
    <?= Html::a('Kembali', ['index'], ['class' => 'btn btn-default']) ?>
</p>
