<?php

use common\components\LazyImage;
use yii\helpers\Html;

/* @var $model frontend\models\Berita */
?>

<div class="news-item mb-3">
    <article class="card border-0 h-100 overflow-hidden news-list-card">
        <div class="row g-0 h-100">
            <div class="col-md-4">
                <div class="news-image-wrapper h-100 position-relative">
                    <?= Html::a(
                        LazyImage::img('@web/common/dokumen/' . $model->image, [
                            'class' => 'w-100 h-100 object-fit-cover position-absolute news-list-card__image',
                            'alt' => $model->judul,
                        ]),
                        ['view', 'id' => $model->id],
                        ['class' => 'd-block h-100']
                    ) ?>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card-body p-4 d-flex flex-column h-100">
                    <time class="news-list-card__date" datetime="<?= Html::encode($model->tanggal) ?>">
                        <?= \common\components\DateHelper::formatIndonesian($model->tanggal) ?>
                    </time>

                    <h3 class="news-list-card__title">
                        <?= Html::a(Html::encode($model->judul), ['view', 'id' => $model->id]) ?>
                    </h3>

                    <p class="news-list-card__excerpt flex-grow-1">
                        <?= strip_tags($model->isi) ?>
                    </p>

                    <?= Html::a(
                        'Baca selengkapnya →',
                        ['view', 'id' => $model->id],
                        ['class' => 'news-read-more mt-auto']
                    ) ?>
                </div>
            </div>
        </div>
    </article>
</div>
