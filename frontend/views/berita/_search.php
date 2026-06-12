<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model frontend\models\search\BeritaSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="berita-search-widget">
    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => [
            'data-pjax' => 1,
            'class' => 'search-form'
        ],
    ]); ?>

    <?= $form->field($model, 'judul', [
        'options' => ['class' => 'form-group mb-3'],
    ])->textInput([
        'placeholder' => 'Cari berita...',
        'class' => 'form-control rounded-3 py-2 px-3 border-light-gray',
        'style' => 'background-color: #f8fafc;'
    ])->label(false) ?>

    <div class="d-grid">
        <?= Html::submitButton('<i class="bi bi-search"></i> Cari', ['class' => 'btn jdih-search-submit w-100 py-2']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<style>
.berita-search-widget .form-control:focus {
    background-color: #fff;
    border-color: #3b82f6;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}
.border-light-gray { border-color: #e2e8f0; }
</style>
