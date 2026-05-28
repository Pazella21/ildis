<?php

use yii\helpers\Html;

$this->title = 'Ubah Link Footer: ' . $model->label;
$this->params['breadcrumbs'][] = ['label' => 'Link Footer', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->label, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Ubah';
?>
<div class="box-body no-padding">
    <?= $this->render('_form', ['model' => $model, 'sections' => $sections]) ?>
</div>