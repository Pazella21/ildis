<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model frontend\models\DokumenSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="dokumen-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => [
            'data-pjax' => 1,
            'class' => 'modern-search-form'
        ],
        'fieldConfig' => [
            'template' => "{label}\n{input}\n{error}",
            'labelOptions' => ['class' => 'form-label small fw-bold text-muted mb-1 text-uppercase', 'style' => 'letter-spacing: 0.5px;'],
            'inputOptions' => ['class' => 'form-control border-light-subtle rounded-3 mb-3', 'style' => 'padding: 0.6rem 1rem; background-color: #f8fafc;'],
        ],
    ]); ?>

    <?= $form->field($model, 'judul')->textInput(['placeholder' => 'Ketik judul...'])->label('Judul / Kata Kunci') ?>
    
    <?= $form->field($model, 'tipe_dokumen')->dropDownList(
        \yii\helpers\ArrayHelper::map(\frontend\models\DokumenHukum::find()->where(['parent_id' => 0])->asArray()->all(), 'id', 'name'),
        [
            'prompt' => 'Semua Tipe',
            'onchange' => '
                $.get( "' . Url::toRoute('/dokumen/jenis') . '", { id: $(this).val() } )
                    .done(function( data ) {
                        $( "#' . Html::getInputId($model, 'jenis_peraturan') . '" ).html( data );
                    }
                );'
        ]
    )->label('Tipe Pengelolaan') ?>

    <?= $form->field($model, 'jenis_peraturan')->dropDownList(
        \yii\helpers\ArrayHelper::map(\frontend\models\DokumenHukum::find()->where(['parent_id' => $model->tipe_dokumen])->asArray()->all(), 'name', 'name'),
        [
            'prompt' => 'Semua Jenis',
        ]
    )->label('Jenis Dokumen') ?>

    <div class="row g-2">
        <div class="col-md-7">
            <?= $form->field($model, 'nomor_peraturan')->textInput(['placeholder' => 'No...']) ?>
        </div>
        <div class="col-md-5">
            <?= $form->field($model, 'tahun_terbit')->textInput(['placeholder' => 'Tahun...']) ?>
        </div>
    </div>

    <?= $form->field($model, 'status_terakhir')->dropDownList(
        \yii\helpers\ArrayHelper::map(\frontend\models\Status::find()->where(['id' => ([2, 4, 6, 7, 8])])->asArray()->all(), 'status', 'status'), 
        ['prompt' => 'Semua Status']
    )->label('Status'); ?>
    
    <?= $form->field($model, 'subyek')->textInput(['placeholder' => 'Ketik subyek...']) ?>
    <?= $form->field($model, 'nama_pengarang')->textInput(['placeholder' => 'Ketik pengarang...']) ?>

    <div class="form-group mt-4 pt-2">
        <?= Html::submitButton('<i class="bi bi-search"></i> Terapkan Filter', ['class' => 'btn jdih-search-submit w-100 py-2']) ?>
        <?= Html::a('<i class="bi bi-arrow-counterclockwise"></i> Reset', ['index'], ['class' => 'btn btn-link w-100 text-muted text-decoration-none small mt-2']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>