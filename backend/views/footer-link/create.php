<?php

use yii\helpers\Html;

$this->title = 'Tambah Link Footer';
$this->params['breadcrumbs'][] = ['label' => 'Link Footer', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="box-body no-padding">
    <?= $this->render('_form', ['model' => $model, 'sections' => $sections]) ?>
</div>