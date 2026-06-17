<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model frontend\models\DokumenSearch */
/* @var $form yii\widgets\ActiveForm */

$hasActiveFilters = false;
foreach ($model->attributes as $value) {
    if ($value !== '' && $value !== null) {
        $hasActiveFilters = true;
        break;
    }
}

$fieldConfig = [
    'template' => "{label}\n{input}\n{error}",
    'labelOptions' => ['class' => 'dokumen-search-label'],
    'inputOptions' => ['class' => 'form-control dokumen-search-input'],
    'options' => ['class' => 'dokumen-search-field'],
];
?>

<div class="dokumen-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => [
            'data-pjax' => 1,
            'class' => 'dokumen-search-form',
        ],
        'fieldConfig' => $fieldConfig,
    ]); ?>

    <div class="dokumen-search-section">
        <p class="dokumen-search-section__heading">Kata kunci</p>
        <?= $form->field($model, 'judul')->textInput(['placeholder' => 'Ketik judul atau kata kunci...'])->label('Judul') ?>
        <?= $form->field($model, 'subyek')->textInput(['placeholder' => 'Contoh: agraria, pidana...']) ?>
        <?= $form->field($model, 'nama_pengarang')->textInput(['placeholder' => 'Nama pengarang...'])->label('Pengarang') ?>
    </div>

    <div class="dokumen-search-section">
        <p class="dokumen-search-section__heading">Klasifikasi</p>
        <?= $form->field($model, 'tipe_dokumen')->dropDownList(
            \yii\helpers\ArrayHelper::map(
                \frontend\models\DokumenHukum::find()->where(['parent_id' => 0])->asArray()->all(),
                'id',
                'name'
            ),
            [
                'prompt' => 'Semua tipe',
                'class' => 'form-select dokumen-search-input dokumen-search-select',
                'onchange' => '
                    $.get("' . Url::toRoute('/dokumen/jenis') . '", { id: $(this).val() })
                        .done(function(data) {
                            $("#' . Html::getInputId($model, 'jenis_peraturan') . '").html(data);
                        });
                ',
            ]
        )->label('Tipe pengelolaan') ?>

        <?= $form->field($model, 'jenis_peraturan')->dropDownList(
            \yii\helpers\ArrayHelper::map(
                \frontend\models\DokumenHukum::find()->where(['parent_id' => $model->tipe_dokumen])->asArray()->all(),
                'name',
                'name'
            ),
            [
                'prompt' => 'Semua jenis',
                'class' => 'form-select dokumen-search-input dokumen-search-select',
            ]
        )->label('Jenis dokumen') ?>

        <?= $form->field($model, 'status_terakhir')->dropDownList(
            \yii\helpers\ArrayHelper::map(
                \frontend\models\Status::find()->where(['id' => [2, 4, 6, 7, 8]])->asArray()->all(),
                'status',
                'status'
            ),
            [
                'prompt' => 'Semua status',
                'class' => 'form-select dokumen-search-input dokumen-search-select',
            ]
        )->label('Status') ?>
    </div>

    <div class="dokumen-search-section">
        <p class="dokumen-search-section__heading">Identitas dokumen</p>
        <div class="row g-2 dokumen-search-row">
            <div class="col-7">
                <?= $form->field($model, 'nomor_peraturan')->textInput(['placeholder' => 'Nomor'])->label('Nomor') ?>
            </div>
            <div class="col-5">
                <?= $form->field($model, 'tahun_terbit')->textInput([
                    'placeholder' => 'Tahun',
                    'inputmode' => 'numeric',
                    'pattern' => '[0-9]*',
                ])->label('Tahun') ?>
            </div>
        </div>
    </div>

    <div class="dokumen-search-actions">
        <?= Html::submitButton('<i class="bi bi-search" aria-hidden="true"></i> Terapkan filter', [
            'class' => 'btn jdih-search-submit w-100',
        ]) ?>
        <?= Html::a(
            '<i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Reset filter',
            ['index'],
            [
                'class' => 'btn dokumen-search-reset w-100' . ($hasActiveFilters ? ' dokumen-search-reset--active' : ''),
            ]
        ) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

<?php
$this->registerJs(<<<'JS'
(function () {
    function syncSelectTitle(select) {
        var option = select.options[select.selectedIndex];
        select.title = option ? option.text.trim() : '';
    }

    document.querySelectorAll('.dokumen-search-select').forEach(function (select) {
        syncSelectTitle(select);
        select.addEventListener('change', function () {
            syncSelectTitle(select);
        });
    });
})();
JS
);
?>
