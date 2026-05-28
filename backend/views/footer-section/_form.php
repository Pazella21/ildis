<?php

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use common\models\FooterSection;

$form = ActiveForm::begin([
    'layout' => 'horizontal',
    'fieldConfig' => [
        'template' => "{label}\n{beginWrapper}\n{input}\n{hint}\n{error}\n{endWrapper}",
        'horizontalCssClasses' => [
            'label' => 'col-sm-2',
            'offset' => 'col-sm-offset-4',
            'wrapper' => 'col-sm-8',
        ],
    ],
]);
?>
<div class="box box-primary box-solid">
    <div class="box-header with-border">
        <b><?= $model->isNewRecord ? 'Form Tambah Bagian Footer' : 'Form Ubah Bagian Footer' ?></b>
    </div>
    <div class="box-body">
        <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>
        <?= $form->field($model, 'type')->dropDownList(
            [FooterSection::TYPE_NAV => 'Navigasi', FooterSection::TYPE_SOCIAL => 'Media Sosial'],
            ['prompt' => '-- Pilih Tipe --']
        ) ?>
        <?= $form->field($model, 'sort_order')->textInput(['type' => 'number']) ?>
        <?= $form->field($model, 'status')->dropDownList(
            [1 => 'Aktif', 0 => 'Tidak Aktif'],
            ['prompt' => '-- Pilih Status --']
        ) ?>
    </div>
    <div class="box-footer">
        <?= Html::submitButton(
            '<i class="fa fa-save"></i> ' . ($model->isNewRecord ? 'Simpan' : 'Ubah'),
            ['class' => 'btn btn-success btn-flat']
        ) ?>
        <?= Html::a('<i class="fa fa-remove"></i> Batal', ['index'], ['class' => 'btn btn-danger btn-flat']) ?>
    </div>
</div>
<?php ActiveForm::end(); ?>