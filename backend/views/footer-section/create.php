<?php

use yii\helpers\Html;

$this->title = 'Tambah Bagian Footer';
$this->params['breadcrumbs'][] = ['label' => 'Bagian Footer', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="box-body no-padding">
    <?= $this->render('_form', ['model' => $model]) ?>
</div>