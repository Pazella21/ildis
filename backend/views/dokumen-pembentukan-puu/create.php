<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\Peraturan */

$this->title = 'Tambah Data Dokumen Pembentukan PUU';
$this->params['breadcrumbs'][] = ['label' => 'Dokumen Pembentukan PUU', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="box-body no-padding">

    <?= $this->render('_form-create', [
        'model' => $model,
    ]) ?>
</div>