<?php

use mdm\admin\components\Helper;
use yii\helpers\Html;
use kartik\grid\GridView;
use yii\widgets\Pjax;
use common\models\FooterSection;

$this->title = 'Link Footer';
$this->params['breadcrumbs'][] = $this->title;

Pjax::begin(['enablePushState' => false]);
?>
<div class="box-body table-responsive no-padding">
    <?= GridView::widget([
        'panel' => [
            'type' => GridView::TYPE_PRIMARY,
            'heading' => '<h3 class="panel-title"><i class="glyphicon glyphicon-link"></i> Link Footer</h3>',
        ],
        'toolbar' => [
            ['content' => Html::a('<i class="fa fa-plus-circle"></i> Tambah Link', ['create'], ['class' => 'btn btn-success'])],
            '{export}',
            '{toggleData}',
        ],
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'summary' => 'Ditampilkan {begin} - {end} dari {totalCount} Data',
        'layout' => "{items}\n{summary}\n{pager}",
        'columns' => [
            [
                'class' => 'yii\grid\SerialColumn',
                'contentOptions' => ['style' => 'width: 50px;', 'class' => 'text-center'],
                'header' => 'No',
                'headerOptions' => ['class' => 'text-center'],
            ],
            [
                'attribute' => 'section_id',
                'value' => function ($model) {
                    return $model->section ? $model->section->title : '-';
                },
                'filter' => \yii\helpers\ArrayHelper::map(FooterSection::find()->orderBy(['sort_order' => SORT_ASC])->all(), 'id', 'title'),
                'contentOptions' => ['style' => 'width: 200px;'],
            ],
            'label',
            [
                'attribute' => 'url',
                'format' => 'raw',
                'value' => function ($model) {
                    return Html::a(Html::encode($model->url), $model->url, ['target' => '_blank', 'class' => 'text-info']);
                },
            ],
            [
                'attribute' => 'icon_class',
                'format' => 'raw',
                'value' => function ($model) {
                    return $model->icon_class ? '<i class="' . Html::encode($model->icon_class) . '"></i> ' . Html::encode($model->icon_class) : '-';
                },
                'contentOptions' => ['style' => 'width: 200px;'],
            ],
            'sort_order',
            [
                'attribute' => 'status',
                'value' => function ($model) {
                    return $model->status ? 'Aktif' : 'Tidak Aktif';
                },
                'filter' => [1 => 'Aktif', 0 => 'Tidak Aktif'],
                'contentOptions' => ['style' => 'width: 120px;'],
            ],
            [
                'attribute' => 'open_in_new_tab',
                'value' => function ($model) {
                    return $model->open_in_new_tab ? 'Ya' : 'Tidak';
                },
                'filter' => [1 => 'Ya', 0 => 'Tidak'],
                'contentOptions' => ['style' => 'width: 120px;'],
                'label' => 'Tab Baru',
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'headerOptions' => ['style' => 'width: 150px;', 'class' => 'text-center'],
                'contentOptions' => ['style' => 'width: 150px;', 'class' => 'text-center'],
                'header' => 'Aksi',
                'template' => '{view} {update} {delete}',
                'buttons' => [
                    'view' => function ($url, $model) {
                        return Html::a('<span class="btn btn-sm btn-success"><b class="fa fa-search-plus"></b></span>', ['view', 'id' => $model->id], ['title' => 'Lihat']);
                    },
                    'update' => function ($url, $model) {
                        return Html::a('<span class="btn btn-sm btn-warning"><b class="fa fa-pencil"></b></span>', ['update', 'id' => $model->id], ['title' => 'Ubah']);
                    },
                    'delete' => function ($url, $model) {
                        return Html::a('<span class="btn btn-sm btn-danger"><b class="fa fa-trash"></b></span>', ['delete', 'id' => $model->id], [
                            'title' => 'Hapus',
                            'data' => ['confirm' => 'Yakin akan menghapus link ini?', 'method' => 'post'],
                        ]);
                    },
                ],
            ],
        ],
    ]); ?>
<?php Pjax::end(); ?>
</div>