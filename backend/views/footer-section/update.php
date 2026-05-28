<?php

use yii\helpers\Html;

$this->title = 'Ubah Bagian Footer: ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Bagian Footer', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Ubah';
?>
<div class="box-body no-padding">
    <?= $this->render('_form', ['model' => $model]) ?>
</div>