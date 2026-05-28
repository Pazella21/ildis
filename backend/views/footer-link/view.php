<?php

use yii\helpers\Html;

$this->title = 'Detail Link Footer: ' . $model->label;
$this->params['breadcrumbs'][] = ['label' => 'Link Footer', 'url' => ['index']];
$this->params['breadcrumbs'][] = $model->label;
?>
<div class="box box-primary box-solid">
    <div class="box-header with-border">
        <b><?= Html::encode($model->label) ?></b>
    </div>
    <div class="box-body">
        <table class="table table-bordered">
            <tr><th style="width: 200px;">Bagian</th><td><?= $model->section ? Html::encode($model->section->title) : '-' ?></td></tr>
            <tr><th>Label</th><td><?= Html::encode($model->label) ?></td></tr>
            <tr><th>URL</th><td><?= Html::a(Html::encode($model->url), $model->url, ['target' => '_blank']) ?></td></tr>
            <tr><th>Ikon</th><td><?= $model->icon_class ? '<i class="' . Html::encode($model->icon_class) . '"></i> ' . Html::encode($model->icon_class) : '-' ?></td></tr>
            <tr><th>Urutan</th><td><?= $model->sort_order ?></td></tr>
            <tr><th>Status</th><td><?= $model->status ? '<span class="label label-success">Aktif</span>' : '<span class="label label-danger">Tidak Aktif</span>' ?></td></tr>
            <tr><th>Buka di Tab Baru</th><td><?= $model->open_in_new_tab ? 'Ya' : 'Tidak' ?></td></tr>
        </table>
    </div>
</div>