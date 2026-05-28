<?php

use mdm\admin\components\Helper;
use yii\helpers\Html;
use kartik\grid\GridView;
use yii\widgets\Pjax;
use common\models\FooterSection;

$this->title = 'Bagian Footer';
$this->params['breadcrumbs'][] = $this->title;

Pjax::begin(['enablePushState' => false]);
?>
<div class="box-body table-responsive no-padding">
    <?= GridView::widget([
        'panel' => [
            'type' => GridView::TYPE_PRIMARY,
            'heading' => '<h3 class="panel-title"><i class="glyphicon glyphicon-th-list"></i> Bagian Footer</h3>',
        ],
        'toolbar' => [
            ['content' => Html::a('<i class="fa fa-plus-circle"></i> Tambah Bagian', ['create'], ['class' => 'btn btn-success'])],
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
            'title',
            [
                'attribute' => 'type',
                'value' => function ($model) {
                    return $model->type === FooterSection::TYPE_NAV ? 'Navigasi' : 'Media Sosial';
                },
                'filter' => [FooterSection::TYPE_NAV => 'Navigasi', FooterSection::TYPE_SOCIAL => 'Media Sosial'],
                'contentOptions' => ['style' => 'width: 150px;'],
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
                'label' => 'Jumlah Link',
                'value' => function ($model) {
                    return count($model->links);
                },
                'contentOptions' => ['style' => 'width: 100px; text-align: center;'],
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'headerOptions' => ['style' => 'width: 200px;', 'class' => 'text-center'],
                'contentOptions' => ['style' => 'width: 200px;', 'class' => 'text-center'],
                'header' => 'Aksi',
                'template' => '{view} {links} {update} {delete}',
                'buttons' => [
                    'view' => function ($url, $model) {
                        return Html::a('<span class="btn btn-sm btn-success"><b class="fa fa-search-plus"></b></span>', ['view', 'id' => $model->id], ['title' => 'Lihat']);
                    },
                    'links' => function ($url, $model) {
                        return Html::a('<span class="btn btn-sm btn-info"><b class="fa fa-link"></b></span>', ['/footer-link/index', 'FooterLinkSearch[section_id]' => $model->id], ['title' => 'Kelola Link']);
                    },
                    'update' => function ($url, $model) {
                        return Html::a('<span class="btn btn-sm btn-warning"><b class="fa fa-pencil"></b></span>', ['update', 'id' => $model->id], ['title' => 'Ubah']);
                    },
                    'delete' => function ($url, $model) {
                        return Html::a('<span class="btn btn-sm btn-danger"><b class="fa fa-trash"></b></span>', ['delete', 'id' => $model->id], [
                            'title' => 'Hapus',
                            'data' => ['confirm' => 'Yakin akan menghapus bagian ini?', 'method' => 'post'],
                        ]);
                    },
                ],
            ],
        ],
    ]); ?>
<?php Pjax::end(); ?>
</div>