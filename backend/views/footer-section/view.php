<?php

use yii\helpers\Html;
use common\models\FooterSection;

$this->title = 'Detail Bagian Footer: ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Bagian Footer', 'url' => ['index']];
$this->params['breadcrumbs'][] = $model->title;
?>
<div class="box box-primary box-solid">
    <div class="box-header with-border">
        <b><?= Html::encode($model->title) ?></b>
    </div>
    <div class="box-body">
        <table class="table table-bordered">
            <tr><th style="width: 200px;">Judul</th><td><?= Html::encode($model->title) ?></td></tr>
            <tr><th>Tipe</th><td><?= $model->type === FooterSection::TYPE_NAV ? 'Navigasi' : 'Media Sosial' ?></td></tr>
            <tr><th>Urutan</th><td><?= $model->sort_order ?></td></tr>
            <tr><th>Status</th><td><?= $model->status ? '<span class="label label-success">Aktif</span>' : '<span class="label label-danger">Tidak Aktif</span>' ?></td></tr>
        </table>

        <h4 style="margin-top: 20px;">Link di Bagian Ini</h4>
        <?php if ($model->links): ?>
            <table class="table table-striped">
                <thead><tr><th>No</th><th>Label</th><th>URL</th><th>Ikon</th><th>Urutan</th><th>Status</th></tr></thead>
                <tbody>
                <?php $i = 1; foreach ($model->links as $link): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= Html::encode($link->label) ?></td>
                        <td><?= Html::encode($link->url) ?></td>
                        <td><?= $link->icon_class ? Html::encode($link->icon_class) : '-' ?></td>
                        <td><?= $link->sort_order ?></td>
                        <td><?= $link->status ? '<span class="label label-success">Aktif</span>' : '<span class="label label-danger">Tidak Aktif</span>' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted">Belum ada link di bagian ini.</p>
        <?php endif; ?>
    </div>
</div>