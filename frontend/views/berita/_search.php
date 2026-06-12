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
            'class' => 'berita-search-form',
        ],
    ]); ?>

    <div class="input-group">
        <?= Html::activeTextInput($model, 'judul', [
            'class' => 'form-control berita-search-form__input',
            'placeholder' => 'Cari berita...',
        ]) ?>
        <?= Html::submitButton('<i class="bi bi-search" aria-hidden="true"></i>', [
            'class' => 'berita-search-form__submit',
            'title' => 'Cari',
            'aria-label' => 'Cari berita',
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
